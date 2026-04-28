<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantWorkspaceAccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Tenant $tenant
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = TenantUrl::login($this->tenant);

        return (new MailMessage)
            ->subject('Your tenant workspace access details')
            ->greeting('Hello '.($notifiable->name ?: 'there').',')
            ->line('Your tenant workspace access details were resent from the central dashboard.')
            ->line('Tenant: '.$this->tenant->name)
            ->line('Workspace URL: '.TenantUrl::workspace($this->tenant))
            ->line('Login page: '.$loginUrl)
            ->line('Login email: '.$notifiable->email)
            ->line('Username: '.$notifiable->username)
            ->action('Open login page', $loginUrl)
            ->line('If you no longer know the password, use the Forgot Password link on the login page.');
    }
}
