<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Tenant $tenant,
        private string $generatedPassword
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
        $loginUrl = TenantUrl::login($this->tenant, true);
        return (new MailMessage)
            ->subject('Your tenant account is ready')
            ->greeting('Hello ' . ($notifiable->name ?: 'there') . ',')
            ->line('Your account has been created successfully.')
            ->line('Tenant: ' . $this->tenant->name)
            ->line('Login page: ' . $loginUrl)
            ->line('Login email: ' . $notifiable->email)
            ->line('Username: ' . $notifiable->username)
            ->line('Temporary password: ' . $this->generatedPassword)
            ->action('Log in to your workspace', $loginUrl)
            ->line('You may kindly change this password later from your account settings.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'email' => $notifiable->email,
        ];
    }
}
