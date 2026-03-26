<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantActivationStatusNotification extends Notification
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
        $workspaceUrl = TenantUrl::workspace($this->tenant);
        $statusLabel = $this->tenant->is_active ? 'activated' : 'deactivated';

        $message = (new MailMessage)
            ->subject("Tenant workspace {$statusLabel}")
            ->greeting('Hello '.($notifiable->name ?: 'there').',')
            ->line("Your tenant workspace for {$this->tenant->name} has been {$statusLabel} by the central administrator.")
            ->line('Workspace URL: '.$workspaceUrl);

        if ($this->tenant->is_active) {
            $message->action('Open workspace', $workspaceUrl)
                ->line('You may continue using your tenant workspace normally.');
        } else {
            $message->line('Access will remain unavailable until the workspace is activated again.');
        }

        return $message;
    }
}
