<?php

namespace App\Notifications;

use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = TenantUrl::forPath(
            $notifiable->tenant,
            '/reset-password/'.$this->token.'?email='.urlencode($notifiable->email)
        );

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your password')
            ->greeting('Hello ' . ($notifiable->name ?: 'there') . ',')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset password', $url)
            ->line("This password reset link will expire in {$expire} minutes.")
            ->line('If you did not request a password reset, no further action is required.');
    }
}
