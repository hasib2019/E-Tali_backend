<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Base64 image storage + retrieval shared by vouchers and cashbook entries.
 * Stored files are downscaled + returned as base64 data URIs so they render
 * anywhere (no storage:link/host dependency) and stay light for PDFs.
 */
trait HandlesMedia
{
    /** Store a base64 data URL under {folder}/{id}/{name}.{ext}; returns the path. */
    protected function storeBase64(string $dataUrl, string $folder, int $id, string $name): ?string
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
        $path = "{$folder}/{$id}/{$name}.{$ext}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** Read a stored image, downscale + compress it, and return a base64 data URI. */
    protected function mediaDataUri(?string $path, int $maxWidth = 1000): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        $jpeg = $this->downscaleToJpeg($binary, $maxWidth);
        if ($jpeg !== null) {
            return 'data:image/jpeg;base64,'.base64_encode($jpeg);
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode($binary);
    }

    /** Delete stored media files for the given paths. */
    protected function deleteMedia(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /** Downscale image bytes onto white and return JPEG bytes (null if GD can't). */
    private function downscaleToJpeg(string $binary, int $maxWidth): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $nw = $w > $maxWidth ? $maxWidth : $w;
        $nh = (int) round($h * ($nw / $w));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, true);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, 72);
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $out !== '' ? $out : null;
    }
}
