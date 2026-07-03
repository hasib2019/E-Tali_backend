<?php

namespace App\Http\Controllers\Api;

use App\Jobs\RunUserBackup;
use App\Models\User;
use App\Services\BackupService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class DriveBackupController extends ApiController
{
    /**
     * Link the user's Google Drive by exchanging the OAuth authorization code
     * for tokens (stored encrypted, server-side).
     */
    public function connect(Request $request, GoogleDriveService $drive): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'code_verifier' => ['nullable', 'string'],
            'redirect_uri' => ['required', 'string'],
            'platform' => ['required', 'in:web,android,ios'],
        ]);

        try {
            $drive->exchangeCode(
                $request->user(),
                $data['code'],
                $data['code_verifier'] ?? null,
                $data['redirect_uri'],
                $data['platform'],
            );
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok($this->driveStatus($request->user()), 'Google Drive connected.');
    }

    public function status(Request $request): JsonResponse
    {
        return $this->ok($this->driveStatus($request->user()));
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->user()->driveCredential?->delete();

        return $this->ok($this->driveStatus($request->user()->refresh()), 'Google Drive disconnected.');
    }

    /**
     * Run a backup immediately (synchronously) and return the resulting history row.
     */
    public function backupNow(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->driveCredential) {
            return $this->fail('Google Drive is not connected.');
        }

        RunUserBackup::dispatchSync($user->id, 'manual');

        $history = $user->backupHistories()->latest()->first();
        $ok = $history?->status === 'success';

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Backup complete.' : ($history?->error ?? 'Backup failed.'),
            'data' => $history,
        ], $ok ? 200 : 422);
    }

    public function history(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()->backupHistories()->latest()->limit(50)->get(),
        );
    }

    /**
     * List the backup files currently in the user's Drive folder.
     */
    public function driveFiles(Request $request, GoogleDriveService $drive): JsonResponse
    {
        try {
            $files = $drive->listBackups($request->user());
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok($files);
    }

    /**
     * Restore/migrate the user's data from a backup — either a Drive file id or
     * an inline backup payload. Defaults to replacing existing data.
     */
    public function restore(Request $request, GoogleDriveService $drive, BackupService $backup): JsonResponse
    {
        $data = $request->validate([
            'drive_file_id' => ['nullable', 'string'],
            'backup' => ['nullable', 'array'],
            'mode' => ['nullable', 'in:replace,merge'],
        ]);

        $user = $request->user();
        $mode = $data['mode'] ?? 'replace';

        try {
            if (! empty($data['drive_file_id'])) {
                $raw = $drive->download($user, $data['drive_file_id']);
                $payload = json_decode($raw, true);
                if (! is_array($payload)) {
                    throw new RuntimeException('The backup file is not valid JSON.');
                }
            } elseif (! empty($data['backup'])) {
                $payload = $data['backup'];
            } else {
                return $this->fail('Provide a drive_file_id or a backup payload.');
            }

            $counts = $backup->import($user, $payload, $mode);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok($counts, 'Restore complete.');
    }

    /**
     * Update the automatic backup frequency (off | daily | weekly | monthly).
     */
    public function schedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'backup_frequency' => ['required', 'in:off,daily,weekly,monthly'],
        ]);

        $request->user()->forceFill(['backup_frequency' => $data['backup_frequency']])->save();

        return $this->ok($this->driveStatus($request->user()), 'Backup schedule updated.');
    }

    private function driveStatus(User $user): array
    {
        $cred = $user->driveCredential;

        return [
            'drive_connected' => (bool) $cred,
            'connected_at' => $cred?->connected_at?->toIso8601String(),
            'backup_frequency' => $user->backup_frequency,
            'last_backup_at' => $user->last_backup_at?->toIso8601String(),
        ];
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
