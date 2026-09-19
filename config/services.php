<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup master key (owner-escrow)
    |--------------------------------------------------------------------------
    | Base64-encoded 256-bit key the app uses to encrypt/decrypt device backups.
    | Generate: base64_encode(random_bytes(32)). Keep it secret + backed up — if
    | lost, existing encrypted backups become unrecoverable.
    */
    'backup_key' => env('BACKUP_MASTER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'qolek' => [
        // SMS OTP gateway (https://sms.qolek.com). Sandbox vs Live is toggled
        // on Qolek's dashboard for this key, not here.
        'base_url' => env('QOLEK_BASE_URL', 'https://sms.qolek.com/api/v1'),
        'api_key' => env('QOLEK_API_KEY'),
        'api_secret' => env('QOLEK_API_SECRET'),
        'sender_id' => env('QOLEK_SENDER_ID'),
        // Shared secret in the callback URL path (…/webhooks/sms/qolek/{this}).
        // Paste the full URL, with this value in place of {token}, into
        // Qolek's "Delivery Status Callback URL" field.
        'webhook_secret' => env('QOLEK_WEBHOOK_SECRET'),
    ],

    'google' => [
        // OAuth client IDs — a Google id_token's `aud` must match one of these.
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        'web_client_secret' => env('GOOGLE_WEB_CLIENT_SECRET'),
        'android_client_id' => env('GOOGLE_ANDROID_CLIENT_ID'),
        'ios_client_id' => env('GOOGLE_IOS_CLIENT_ID'),
        // Drive backup destination folder (created in the user's own Drive).
        'drive_folder_name' => env('GOOGLE_DRIVE_FOLDER_NAME', 'Tali Khata Backups'),
    ],

];
