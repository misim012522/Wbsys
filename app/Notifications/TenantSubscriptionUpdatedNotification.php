<?php

namespace App\Notifications;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\TenantUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantSubscriptionUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Tenant $tenant,
        private Plan $plan,
        private TenantSubscription $subscription
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
        return (new MailMessage)
            ->subject('Your tenant subscription was updated')
            ->greeting('Hello '.($notifiable->name ?: 'there').',')
            ->line("The subscription for {$this->tenant->name} has been updated from the central dashboard.")
            ->line('Plan: '.$this->plan->name)
            ->line('Status: '.str($this->subscription->status)->replace('_', ' ')->title())
            ->line('Starts at: '.$this->subscription->starts_at?->format('M d, Y h:i A'))
            ->line('Ends at: '.($this->subscription->ends_at?->format('M d, Y h:i A') ?: 'No end date'))
            ->action('Open workspace', TenantUrl::workspace($this->tenant));
    }
}
