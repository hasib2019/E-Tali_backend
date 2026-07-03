<?php

namespace App\Console\Commands;

use App\Jobs\RunUserBackup;
use App\Models\User;
use Illuminate\Console\Command;

class RunScheduledBackups extends Command
{
    protected $signature = 'backups:run';

    protected $description = 'Dispatch Google Drive backups for users whose schedule is due';

    public function handle(): int
    {
        $users = User::where('backup_frequency', '!=', 'off')
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->whereHas('driveCredential')
            ->get();

        $dispatched = 0;

        foreach ($users as $user) {
            if ($this->isDue($user)) {
                RunUserBackup::dispatch($user->id, 'scheduled');
                $dispatched++;
            }
        }

        $this->info("Dispatched {$dispatched} scheduled backup(s).");

        return self::SUCCESS;
    }

    /**
     * A user is due when they've never been backed up, or the configured
     * interval has elapsed since their last backup.
     */
    private function isDue(User $user): bool
    {
        $last = $user->last_backup_at;
        if (! $last) {
            return true;
        }

        return match ($user->backup_frequency) {
            'daily' => $last->lte(now()->subDay()),
            'weekly' => $last->lte(now()->subWeek()),
            'monthly' => $last->lte(now()->subMonth()),
            default => false,
        };
    }
}
