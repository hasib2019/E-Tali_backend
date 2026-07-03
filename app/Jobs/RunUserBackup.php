<?php

namespace App\Jobs;

use App\Models\BackupHistory;
use App\Models\User;
use App\Services\BackupService;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunUserBackup implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $type = 'manual', // manual | scheduled
    ) {}

    /**
     * Export the user's ledger and upload it to their Google Drive, recording
     * the attempt in `backup_histories`. Failures are captured on the history
     * row rather than rethrown, so the record is always the source of truth.
     */
    public function handle(BackupService $backup, GoogleDriveService $drive): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $filename = 'talikhata-backup-'.now()->format('Ymd-His').'.json';

        $history = BackupHistory::create([
            'user_id' => $user->id,
            'file_name' => $filename,
            'status' => 'pending',
            'type' => $this->type,
        ]);

        try {
            $json = json_encode($backup->export($user), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $result = $drive->uploadJson($user, $filename, $json);

            $history->update([
                'status' => 'success',
                'drive_file_id' => $result['id'] ?? null,
                'size_bytes' => isset($result['size']) ? (int) $result['size'] : strlen($json),
            ]);

            $user->forceFill(['last_backup_at' => now()])->save();
        } catch (Throwable $e) {
            report($e);
            $history->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
