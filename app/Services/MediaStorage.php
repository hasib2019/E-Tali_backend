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
    public static function storeBase64(string $dataUrl, string $relativeDir, string $name): ?string
    {
        $ext = 'png';
        $payload = $dataUrl;
        if (preg_match('#^data:image/(\w+);base64,#', $dataUrl, $m)) {
            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            $payload = substr($dataUrl, strpos($dataUrl, ',') + 1);
        }
        $binary = base64_decode($payload, true);
        if ($binary === false) {
            return null;
        }
        $path = "{$relativeDir}/{$name}.{$ext}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
