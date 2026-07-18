<?php

namespace App\Http\Controllers\Api;

use App\Models\EncryptedBackup;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The owner-hosting copy of a user's ENCRYPTED device backups. The server only
 * ever holds opaque ciphertext; decryption requires the owner master key, which
 * the app fetches from `key()`.
 */
class BackupVaultController extends ApiController
{
    /** How many most-recent backups to retain per user (older ones are pruned). */
    private const KEEP_BACKUPS = 5;

    /** The owner master key (base64) the app encrypts/decrypts backups with. */
    public function key(): JsonResponse
    {
        $key = config('services.backup_key');
        abort_if(empty($key), 503, 'Backup key is not configured.');

        return $this->ok(['key' => $key]);
    }

    /** Store an encrypted backup blob + track it (idempotent on backup_uuid). */
    public function upload(Request $request, GoogleDriveService $drive): JsonResponse
    {
        $data = $request->validate([
            'backup_uuid' => ['required', 'uuid'],
            'container' => ['required', 'string'],       // the ciphertext container
            'checksum' => ['nullable', 'string', 'max:64'],
            'drive_file_id' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:manual,auto'],
        ]);

        $user = $request->user();
        // Backup (owner-storage copy) is a premium feature the owner enables per package.
        $features = $user->loadMissing('package')->package?->features ?? [];
        abort_unless(in_array('backup', $features, true), 403, 'Backup is a premium feature. Please upgrade.');

        $path = "backups/{$user->id}/{$data['backup_uuid']}.json.enc";
        Storage::disk('local')->put($path, $data['container']);

        $record = EncryptedBackup::updateOrCreate(
            ['backup_uuid' => $data['backup_uuid']],
            [
                'user_id' => $user->id,
                'file_name' => $data['backup_uuid'].'.json.enc',
                'storage_path' => $path,
                'size_bytes' => strlen($data['container']),
                'checksum' => $data['checksum'] ?? null,
                'drive_file_id' => $data['drive_file_id'] ?? null,
                'source' => $data['source'] ?? 'manual',
            ],
        );

        // Best-effort: also push the same encrypted copy to the user's own Google
        // Drive (if connected). A Drive failure never fails the hosting backup.
        if (empty($record->drive_file_id) && $user->driveCredential()->exists()) {
            try {
                $result = $drive->uploadJson($user, $record->file_name, $data['container']);
                if (! empty($result['id'])) {
                    $record->update(['drive_file_id' => $result['id']]);
                }
            } catch (\Throwable $e) {
                report($e); // hosting copy already succeeded
            }
        }

        // Keep only the latest N backups per user — delete older rows AND their
        // stored ciphertext files so the vault never grows unbounded.
        $this->pruneOld($user, self::KEEP_BACKUPS);

        $user->forceFill(['last_backup_at' => now()])->saveQuietly();

        return $this->ok($this->present($record->fresh()), 'Backup stored.', 201);
    }

    /**
     * Retain only the {@see KEEP_BACKUPS} newest backups for a user; delete the
     * rest (row + file). Runs only inside a successful upload, so a failed upload
     * never prunes. The just-written record is always among the newest, so it is
     * never the one deleted.
     */
    private function pruneOld(\App\Models\User $user, int $keep): void
    {
        $keepIds = EncryptedBackup::where('user_id', $user->id)
            ->latest()
            ->limit($keep)
            ->pluck('id');

        EncryptedBackup::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function (EncryptedBackup $old): void {
                Storage::disk('local')->delete($old->storage_path);
                $old->delete();
            });
    }

    /** The user's tracked backups, newest first. */
    public function list(Request $request): JsonResponse
    {
        $items = EncryptedBackup::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (EncryptedBackup $b) => $this->present($b));

        return $this->ok($items);
    }

    /** Return the stored ciphertext for restore (owner-hosting copy). */
    public function download(Request $request, EncryptedBackup $encryptedBackup): JsonResponse
    {
        abort_unless($encryptedBackup->user_id === $request->user()->id, 403, 'Not your backup.');
        abort_unless(Storage::disk('local')->exists($encryptedBackup->storage_path), 404, 'Backup file missing.');

        return $this->ok([
            'backup_uuid' => $encryptedBackup->backup_uuid,
            'checksum' => $encryptedBackup->checksum,
            'container' => Storage::disk('local')->get($encryptedBackup->storage_path),
        ]);
    }

    private function present(EncryptedBackup $b): array
    {
        return [
            'id' => $b->id,
            'backup_uuid' => $b->backup_uuid,
            'file_name' => $b->file_name,
            'size_bytes' => $b->size_bytes,
            'checksum' => $b->checksum,
            'drive_file_id' => $b->drive_file_id,
            'source' => $b->source,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }
}
