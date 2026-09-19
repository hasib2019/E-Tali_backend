@php
    $health = $this->getHealth();
    $db = $health['database'];
    $users = $health['users'];
    $activity = $health['activity'];
    $system = $health['system'];
    $jobs = $health['jobs'];

    // One shared rule for "is this number fine, worth a look, or bad?".
    $tone = function (?float $percent): string {
        if ($percent === null) return 'gray';
        if ($percent >= 90) return 'danger';
        if ($percent >= 70) return 'warning';
        return 'success';
    };
    $dbTone = $tone($db['usage_percent']);
    $diskTone = $tone($system['disk_used_percent']);
@endphp

<x-filament-panels::page>
    <div @if ($this->getPollInterval()) wire:poll.{{ $this->getPollInterval() }} @endif class="space-y-6">

        {{-- Header: overall state + refresh control --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span @class([
                        'inline-flex h-3 w-3 rounded-full',
                        'bg-success-500' => $db['reachable'],
                        'bg-danger-500' => ! $db['reachable'],
                    ])></span>
                    <div>
                        <div class="text-lg font-bold">
                            {{ $db['reachable'] ? 'All systems responding' : 'Database unreachable' }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Checked {{ $health['checked_at'] }}
                            @if ($db['ping_ms'] !== null) · DB ping {{ $db['ping_ms'] }} ms @endif
                            · {{ $system['environment'] }}
                            @if ($system['debug']) · <span class="text-warning-600 font-semibold">debug on</span> @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::badge :color="$this->getPollInterval() ? 'success' : 'gray'">
                        {{ $this->getPollInterval() ? 'Live · every '.$this->getPollInterval() : 'Paused' }}
                    </x-filament::badge>
                    <x-filament::button wire:click="togglePolling" size="sm" color="gray">
                        {{ $this->getPollInterval() ? 'Pause' : 'Resume' }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Database pressure --}}
        <x-filament::section>
            <x-slot name="heading">Database pressure</x-slot>
            <x-slot name="description">
                {{ $db['driver'] }} {{ $db['version'] }} · {{ $db['database'] }}
                @if ($db['size_mb'] !== null) · {{ number_format($db['size_mb'], 1) }} MB @endif
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Connections in use', $db['threads_connected'].' / '.$db['max_connections'], $dbTone,
                        $db['usage_percent'] !== null ? $db['usage_percent'].'% of the cap' : null],
                    ['Running queries', $db['threads_running'], 'gray', 'Actively executing now'],
                    ['Peak connections', $db['max_used_connections'], $tone($db['peak_percent']),
                        $db['peak_percent'] !== null ? $db['peak_percent'].'% of the cap, since restart' : null],
                    ['Queries / second', $db['queries_per_second'] ?? '—', 'gray',
                        number_format($db['queries']).' total'],
                ] as [$label, $value, $color, $sub])
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div @class([
                            'mt-1 text-2xl font-bold tabular-nums',
                            'text-success-600 dark:text-success-400' => $color === 'success',
                            'text-warning-600 dark:text-warning-400' => $color === 'warning',
                            'text-danger-600 dark:text-danger-400' => $color === 'danger',
                        ])>{{ $value }}</div>
                        @if ($sub)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($db['usage_percent'] !== null)
                <div class="mt-4">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                        <div @class([
                            'h-full rounded-full',
                            'bg-success-500' => $dbTone === 'success',
                            'bg-warning-500' => $dbTone === 'warning',
                            'bg-danger-500' => $dbTone === 'danger',
                        ]) style="width: {{ min(100, max(2, $db['usage_percent'])) }}%"></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Connection cap usage — the number that decides whether new requests get a connection.
                    </div>
                </div>
            @endif

            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Slow queries</span>
                    <span @class(['ml-2 font-semibold tabular-nums', 'text-warning-600' => $db['slow_queries'] > 0])>
                        {{ number_format($db['slow_queries']) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Aborted connects</span>
                    <span @class(['ml-2 font-semibold tabular-nums', 'text-warning-600' => $db['aborted_connects'] > 0])>
                        {{ number_format($db['aborted_connects']) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">MySQL uptime</span>
                    <span class="ml-2 font-semibold tabular-nums">
                        {{ $db['uptime_seconds'] ? round($db['uptime_seconds'] / 3600, 1).' h' : '—' }}
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- Users --}}
        <x-filament::section>
            <x-slot name="heading">Users</x-slot>

            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Online now', $users['online_now'], 'Active in the last 5 min'],
                    ['Active today', $users['active_today'], null],
                    ['Active (7 days)', $users['active_7d'], null],
                    ['New today', $users['new_today'], null],
                    ['New this month', $users['new_this_month'], null],
                    ['Registered total', $users['total'], $users['locked_out'].' deactivated'],
                ] as [$label, $value, $sub])
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($value) }}</div>
                        @if ($sub)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Screen activity --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Most-opened screens (24 h)</x-slot>
                <x-slot name="description">
                    {{ number_format($activity['views_last_hour']) }} views in the last hour ·
                    {{ number_format($activity['views_today']) }} today ·
                    {{ number_format($activity['views_7d']) }} in 7 days
                </x-slot>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">Screen</th>
                            <th class="py-1 text-right">Views</th>
                            <th class="py-1 text-right">Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($health['top_screens'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-1 font-medium">{{ $row['screen'] }}</td>
                                <td class="py-1 text-right tabular-nums">{{ number_format($row['views']) }}</td>
                                <td class="py-1 text-right tabular-nums">{{ number_format($row['users']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-500">No screen views in the last 24 hours.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Live screen views</x-slot>
                <x-slot name="description">What users opened most recently</x-slot>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">User</th>
                            <th class="py-1">Screen</th>
                            <th class="py-1 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($health['recent_views'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-1 font-medium">{{ $row['user'] }}</td>
                                <td class="py-1">
                                    {{ $row['screen'] }}
                                    @if ($row['platform'])
                                        <span class="text-xs text-gray-500">({{ $row['platform'] }})</span>
                                    @endif
                                </td>
                                <td class="py-1 text-right text-gray-500 dark:text-gray-400">{{ $row['at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-500">Nothing recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- Host + storage --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Server</x-slot>

                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        ['Load average', collect([$system['load_1m'], $system['load_5m'], $system['load_15m']])
                            ->map(fn ($l) => $l === null ? '—' : number_format($l, 2))->implode('  ·  ')],
                        ['PHP', $system['php_version'].' · Laravel '.$system['laravel_version']],
                        ['PHP memory', $system['memory_used_mb'].' MB used · peak '.$system['memory_peak_mb'].' MB · limit '.$system['memory_limit']],
                        ['OS', $system['os']],
                        ['Queue / cache', $system['queue_driver'].' / '.$system['cache_driver']],
                        ['Mailer', $system['mail_mailer']],
                    ] as [$label, $value])
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $label }}</div>
                            <div class="font-semibold">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                @if ($system['disk_used_percent'] !== null)
                    <div class="mt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Disk</span>
                            <span class="font-semibold tabular-nums">
                                {{ $system['disk_used_percent'] }}% used ·
                                {{ $system['disk_free_gb'] }} GB free of {{ $system['disk_total_gb'] }} GB
                            </span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                            <div @class([
                                'h-full rounded-full',
                                'bg-success-500' => $diskTone === 'success',
                                'bg-warning-500' => $diskTone === 'warning',
                                'bg-danger-500' => $diskTone === 'danger',
                            ]) style="width: {{ min(100, max(2, $system['disk_used_percent'])) }}%"></div>
                        </div>
                    </div>
                @endif

                <div class="mt-4 flex gap-6 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Failed jobs</span>
                        <span @class(['ml-2 font-semibold tabular-nums', 'text-danger-600' => ($jobs['failed'] ?? 0) > 0])>
                            {{ $jobs['failed'] === null ? '—' : number_format($jobs['failed']) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Queued jobs</span>
                        <span class="ml-2 font-semibold tabular-nums">
                            {{ $jobs['pending'] === null ? '—' : number_format($jobs['pending']) }}
                        </span>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Largest tables</x-slot>
                <x-slot name="description">Where the database size is going</x-slot>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">Table</th>
                            <th class="py-1 text-right">Rows</th>
                            <th class="py-1 text-right">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($health['tables'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-1 font-medium">{{ $row['table'] }}</td>
                                <td class="py-1 text-right tabular-nums text-gray-500">~{{ number_format($row['rows']) }}</td>
                                <td class="py-1 text-right tabular-nums">{{ number_format($row['size_mb'], 2) }} MB</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-500">Table sizes unavailable.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
