<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountConfirmedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $notifiable->tenant;
        $loginUrl = TenantUrl::login($tenant);
        $roleLabel = match ($notifiable->role) {
            User::ROLE_OFFICE_STAFF => 'office staff account',
            default => 'account',
        };

        $usageLine = match ($notifiable->role) {
            User::ROLE_OFFICE_STAFF => 'You can now log in to your office dashboard and manage queue work for your assigned office.',
            default => 'You can now log in and manage your office queue.',
        };

        $mail = (new MailMessage)
            ->subject('Your account has been confirmed')
            ->greeting('Hello '.($notifiable->name ?: 'there').',')
            ->line("Your {$roleLabel} has been confirmed by an administrator.")
            ->line($usageLine)
            ->action('Log in', $loginUrl)
            ->line('If you did not request an account, you can ignore this email.');

        if ($tenant) {
            $mail->line('Tenant: '.$tenant->name);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
