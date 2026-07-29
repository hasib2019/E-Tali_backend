<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Decode a base64 data URL and persist it on the public disk, returning its
 * relative path. Shared by VoucherController (manual voucher photo/signature
 * uploads) and LedgerSyncService (Diamond-tier voucher media arriving via
 * live sync) — both need the exact same on-disk contract so a voucher photo
 * reads back the same way regardless of which path wrote it.
 */
class MediaStorage
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    private const EXTENSIONS = [
        'gif' => 'gif',
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'webp' => 'webp',
    ];

    public static function storeBase64(string $dataUrl, string $relativeDir, string $name): ?string
    {
        if (! preg_match('#^data:image/(gif|jpe?g|png|webp);base64,#i', $dataUrl, $matches)) {
            return null;
        }

        $ext = self::EXTENSIONS[strtolower($matches[1])] ?? null;
        if ($ext === null) {
            return null;
        }

        $payload = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($payload, true);
        if ($binary === false || strlen($binary) > self::MAX_BYTES) {
            return null;
        }

        $path = "{$relativeDir}/{$name}.{$ext}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode($binary);
    }

    public static function delete(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
