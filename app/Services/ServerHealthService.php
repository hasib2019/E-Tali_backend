<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Snapshot of how the server is doing right now — database pressure, who is
 * using the app, and the host's own vitals.
 *
 * Every query here runs on a page that polls, so they are all either O(1)
 * server counters or indexed lookups on analytics_events (occurred_at, screen,
 * and (user_id, occurred_at) are all indexed).
 */
class ServerHealthService
{
    /** Users seen in the last few minutes count as "online right now". */
    private const ONLINE_MINUTES = 5;

    /** @return array<string, mixed> */
    public function database(): array
    {
        $connection = DB::connection();

        $pingMs = null;
        $reachable = true;
        try {
            $started = microtime(true);
            $connection->select('SELECT 1');
            $pingMs = round((microtime(true) - $started) * 1000, 2);
        } catch (Throwable $e) {
            report($e);
            $reachable = false;
        }

        $status = $this->mysqlStatus();
        $maxConnections = (int) ($this->mysqlVariable('max_connections') ?? 0);
        $threadsConnected = (int) ($status['Threads_connected'] ?? 0);
        $maxUsed = (int) ($status['Max_used_connections'] ?? 0);

        return [
            'reachable' => $reachable,
            'driver' => $connection->getDriverName(),
            'database' => $connection->getDatabaseName(),
            'version' => $this->serverVersion(),
            'ping_ms' => $pingMs,
            'max_connections' => $maxConnections,
            'threads_connected' => $threadsConnected,
            'threads_running' => (int) ($status['Threads_running'] ?? 0),
            'max_used_connections' => $maxUsed,
            // The number people actually care about: how close we are to the cap.
            'usage_percent' => $maxConnections > 0
                ? round($threadsConnected / $maxConnections * 100, 1)
                : null,
            'peak_percent' => $maxConnections > 0
                ? round($maxUsed / $maxConnections * 100, 1)
                : null,
            'aborted_connects' => (int) ($status['Aborted_connects'] ?? 0),
            'slow_queries' => (int) ($status['Slow_queries'] ?? 0),
            'queries' => (int) ($status['Queries'] ?? 0),
            'uptime_seconds' => (int) ($status['Uptime'] ?? 0),
            'queries_per_second' => ($status['Uptime'] ?? 0) > 0
                ? round(((int) ($status['Queries'] ?? 0)) / (int) $status['Uptime'], 1)
                : null,
            'size_mb' => $this->databaseSizeMb(),
        ];
    }

    /** The biggest tables, so growth is visible before it becomes a problem. */
    /** @return array<int, array{table: string, rows: int, size_mb: float}> */
    public function largestTables(int $limit = 8): array
    {
        try {
            $rows = DB::select(
                'SELECT table_name AS `table`, table_rows AS `rows`,
                        ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                   FROM information_schema.tables
                  WHERE table_schema = ?
                  ORDER BY (data_length + index_length) DESC
                  LIMIT '.(int) $limit,
                [DB::connection()->getDatabaseName()],
            );
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return array_map(fn ($r) => [
            'table' => (string) $r->table,
            'rows' => (int) $r->rows,
            'size_mb' => (float) $r->size_mb,
        ], $rows);
    }

    /** @return array<string, mixed> */
    public function users(): array
    {
        return [
            'total' => User::count(),
            'online_now' => User::where('last_active_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))->count(),
            'active_today' => User::where('last_active_at', '>=', now()->startOfDay())->count(),
            'active_7d' => User::where('last_active_at', '>=', now()->subDays(7))->count(),
            'new_today' => User::where('created_at', '>=', now()->startOfDay())->count(),
            'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'locked_out' => User::where('is_active', false)->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function activity(): array
    {
        return [
            'views_last_hour' => AnalyticsEvent::where('occurred_at', '>=', now()->subHour())->count(),
            'views_today' => AnalyticsEvent::where('occurred_at', '>=', now()->startOfDay())->count(),
            'views_7d' => AnalyticsEvent::where('occurred_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /** Screens people are opening most, over the given window. */
    /** @return array<int, array{screen: string, views: int, users: int}> */
    public function topScreens(int $hours = 24, int $limit = 10): array
    {
        return AnalyticsEvent::query()
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->selectRaw('screen, COUNT(*) as views, COUNT(DISTINCT user_id) as users')
            ->groupBy('screen')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'screen' => (string) $r->screen,
                'views' => (int) $r->views,
                'users' => (int) $r->users,
            ])
            ->all();
    }

    /** The live feed: what users opened most recently. */
    /** @return array<int, array{screen: string, user: string, platform: ?string, at: ?string}> */
    public function recentViews(int $limit = 15): array
    {
        return AnalyticsEvent::query()
            ->with('user:id,name,email')
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn (AnalyticsEvent $e) => [
                'screen' => (string) $e->screen,
                'user' => $e->user?->name ?? 'Unknown',
                'platform' => $e->platform,
                'at' => $e->occurred_at?->diffForHumans(),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    public function system(): array
    {
        $load = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: []) : [];
        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => PHP_OS_FAMILY,
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'load_1m' => $load[0] ?? null,
            'load_5m' => $load[1] ?? null,
            'load_15m' => $load[2] ?? null,
            'memory_used_mb' => round(memory_get_usage(true) / 1048576, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'memory_limit' => ini_get('memory_limit'),
            'disk_free_gb' => $diskFree ? round($diskFree / 1073741824, 1) : null,
            'disk_total_gb' => $diskTotal ? round($diskTotal / 1073741824, 1) : null,
            'disk_used_percent' => ($diskFree && $diskTotal)
                ? round(($diskTotal - $diskFree) / $diskTotal * 100, 1)
                : null,
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'mail_mailer' => config('mail.default'),
        ];
    }

    /** Background work that would otherwise fail silently. */
    /** @return array<string, mixed> */
    public function jobs(): array
    {
        return [
            'failed' => $this->tableCount('failed_jobs'),
            'pending' => $this->tableCount('jobs'),
        ];
    }

    private function tableCount(string $table): ?int
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable) {
            return null; // table only exists when that driver is in use
        }
    }

    /** @return array<string, string> */
    private function mysqlStatus(): array
    {
        try {
            $rows = DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN
                ('Threads_connected','Threads_running','Max_used_connections','Aborted_connects','Slow_queries','Queries','Uptime')");
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $out[$row['Variable_name'] ?? ''] = $row['Value'] ?? null;
        }

        return $out;
    }

    private function mysqlVariable(string $name): ?string
    {
        try {
            $row = DB::select('SHOW VARIABLES LIKE ?', [$name]);
            $row = $row ? (array) $row[0] : [];

            return $row['Value'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private function serverVersion(): ?string
    {
        try {
            $pdo = DB::connection()->getPdo();

            return (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            return null;
        }
    }

    private function databaseSizeMb(): ?float
    {
        try {
            $row = DB::select(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb
                   FROM information_schema.tables WHERE table_schema = ?',
                [DB::connection()->getDatabaseName()],
            );

            return isset($row[0]->mb) ? (float) $row[0]->mb : null;
        } catch (Throwable) {
            return null;
        }
    }
}
