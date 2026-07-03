<?php

namespace App\Services;

use App\Models\GoogleDriveCredential;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Server-side Google Drive access on behalf of a user who linked their Drive.
 * Uses the raw OAuth token + Drive REST endpoints (drive.file scope only, so
 * the app can see only the files it created).
 */
class GoogleDriveService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const DRIVE_FILES = 'https://www.googleapis.com/drive/v3/files';

    private const DRIVE_UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    /** [clientId, clientSecret|null] for the platform that linked Drive. */
    private function clientFor(?string $platform): array
    {
        return match ($platform) {
            'android' => [config('services.google.android_client_id'), null],
            'ios' => [config('services.google.ios_client_id'), null],
            default => [config('services.google.web_client_id'), config('services.google.web_client_secret')],
        };
    }

    /**
     * Exchange an authorization code (from the app's OAuth consent) for tokens
     * and persist the encrypted refresh token. Returns the stored credential.
     */
    public function exchangeCode(User $user, string $code, ?string $codeVerifier, string $redirectUri, string $platform): GoogleDriveCredential
    {
        [$clientId, $secret] = $this->clientFor($platform);
        if (! $clientId) {
            throw new RuntimeException('Google Drive is not configured on the server.');
        }

        $params = array_filter([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $secret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ], fn ($v) => $v !== null && $v !== '');

        $resp = Http::asForm()->post(self::TOKEN_URL, $params);
        if ($resp->failed()) {
            throw new RuntimeException('Google token exchange failed: '.$resp->body());
        }
        $data = $resp->json();

        $cred = $user->driveCredential ?: new GoogleDriveCredential(['user_id' => $user->id]);

        // Google only returns a refresh_token on the first consent (we force
        // prompt=consent in the app, but keep the existing one just in case).
        $refresh = $data['refresh_token'] ?? ($cred->exists ? $cred->refresh_token : null);
        if (! $refresh) {
            throw new RuntimeException('Google did not return a refresh token. Please reconnect and allow offline access.');
        }

        $cred->fill([
            'user_id' => $user->id,
            'access_token' => $data['access_token'] ?? null,
            'refresh_token' => $refresh,
            'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : null,
            'scope' => $data['scope'] ?? null,
            'platform' => $platform,
            'connected_at' => now(),
        ]);
        $cred->save();

        // keep the relation fresh for subsequent calls in this request
        $user->setRelation('driveCredential', $cred);

        return $cred;
    }

    /**
     * A valid access token, refreshing via the refresh token when needed.
     * On a revoked grant the credential is deleted (user must reconnect).
     */
    public function freshAccessToken(User $user): string
    {
        $cred = $user->driveCredential;
        if (! $cred) {
            throw new RuntimeException('Google Drive is not connected.');
        }

        if ($cred->access_token && $cred->token_expires_at && $cred->token_expires_at->gt(now()->addSeconds(60))) {
            return $cred->access_token;
        }

        [$clientId, $secret] = $this->clientFor($cred->platform);

        $params = array_filter([
            'client_id' => $clientId,
            'client_secret' => $secret,
            'refresh_token' => $cred->refresh_token,
            'grant_type' => 'refresh_token',
        ], fn ($v) => $v !== null && $v !== '');

        $resp = Http::asForm()->post(self::TOKEN_URL, $params);
        if ($resp->failed()) {
            if (str_contains((string) $resp->body(), 'invalid_grant')) {
                $cred->delete();
                throw new RuntimeException('Google Drive access was revoked. Please reconnect.');
            }
            throw new RuntimeException('Google token refresh failed: '.$resp->body());
        }

        $data = $resp->json();
        $cred->update([
            'access_token' => $data['access_token'] ?? $cred->access_token,
            'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : null,
        ]);

        return $cred->access_token;
    }

    /**
     * Find (or create) the app's backup folder in the user's Drive; cache its id.
     */
    public function ensureAppFolder(User $user, string $accessToken): string
    {
        $cred = $user->driveCredential;
        if ($cred?->drive_folder_id) {
            return $cred->drive_folder_id;
        }

        $name = config('services.google.drive_folder_name');
        $escaped = str_replace("'", "\\'", $name);

        $resp = Http::withToken($accessToken)->get(self::DRIVE_FILES, [
            'q' => "mimeType='application/vnd.google-apps.folder' and name='{$escaped}' and trashed=false",
            'fields' => 'files(id,name)',
            'spaces' => 'drive',
        ]);
        $files = $resp->json('files', []);

        if (! empty($files)) {
            $folderId = $files[0]['id'];
        } else {
            $create = Http::withToken($accessToken)->post(self::DRIVE_FILES, [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);
            if ($create->failed()) {
                throw new RuntimeException('Could not create the Drive backup folder: '.$create->body());
            }
            $folderId = $create->json('id');
        }

        $cred->update(['drive_folder_id' => $folderId]);

        return $folderId;
    }

    /**
     * Upload a JSON string as a new file in the app's backup folder.
     * Returns Drive's file metadata (id, name, size).
     */
    public function uploadJson(User $user, string $filename, string $contents): array
    {
        $accessToken = $this->freshAccessToken($user);
        $folderId = $this->ensureAppFolder($user, $accessToken);

        $metadata = ['name' => $filename, 'parents' => [$folderId]];
        $boundary = 'talikhata'.bin2hex(random_bytes(8));

        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata)."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: application/json\r\n\r\n"
            .$contents."\r\n"
            ."--{$boundary}--";

        $resp = Http::withToken($accessToken)
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post(self::DRIVE_UPLOAD.'?uploadType=multipart&fields=id,name,size');

        if ($resp->failed()) {
            throw new RuntimeException('Drive upload failed: '.$resp->body());
        }

        return $resp->json();
    }

    /**
     * List the user's backup files (newest first).
     */
    public function listBackups(User $user): array
    {
        $accessToken = $this->freshAccessToken($user);
        $folderId = $this->ensureAppFolder($user, $accessToken);

        $resp = Http::withToken($accessToken)->get(self::DRIVE_FILES, [
            'q' => "'{$folderId}' in parents and trashed=false",
            'fields' => 'files(id,name,size,createdTime,modifiedTime)',
            'orderBy' => 'createdTime desc',
            'spaces' => 'drive',
        ]);

        return $resp->json('files', []);
    }

    /**
     * Download a backup file's raw JSON contents by Drive file id.
     */
    public function download(User $user, string $fileId): string
    {
        $accessToken = $this->freshAccessToken($user);

        $resp = Http::withToken($accessToken)->get(self::DRIVE_FILES."/{$fileId}", ['alt' => 'media']);
        if ($resp->failed()) {
            throw new RuntimeException('Drive download failed: '.$resp->body());
        }

        return $resp->body();
    }
}
