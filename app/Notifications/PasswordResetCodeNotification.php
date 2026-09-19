<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Tali Khata password reset code')
            ->greeting('Password reset')
            ->line('Use this code to set a new password:')
            ->line("**{$this->code}**")
            ->line("The code expires in {$this->ttlMinutes} minutes.")
            ->line('If you did not ask for this, you can safely ignore this email — your password stays unchanged.');
    }
}
