<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifySubscriptionDueSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify-due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify tenants 3 days before their subscription is due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threeDaysFromNow = Carbon::now()->addDays(3);
        $tomorrow = Carbon::now()->addDay();

        $subscriptionsDue = TenantSubscription::with(['tenant', 'plan'])
            ->whereIn('status', [TenantSubscription::STATUS_ACTIVE, TenantSubscription::STATUS_TRIALING])
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$tomorrow, $threeDaysFromNow])
            ->whereDoesntHave('tenant', function ($query) {
                $query->where('email_notification_sent_at', '>=', Carbon::now()->subDays(3));
            })
            ->get();

        $count = 0;

        foreach ($subscriptionsDue as $subscription) {
            $tenant = $subscription->tenant;
            $plan = $subscription->plan;

            if (! $tenant || ! $tenant->email) {
                $this->warn("Skipping subscription {$subscription->id}: No tenant or email");
                continue;
            }

            try {
                Mail::raw(
                    sprintf(
                        "Hi %s,\n\nYour subscription for %s will renew in 3 days on %s.\n\nPlan: %s\nAmount: %s\n\nIf you wish to cancel or make changes, please contact support.\n\nBest regards,\n%s Team",
                        $tenant->name,
                        $tenant->name,
                        $subscription->ends_at->format('F j, Y'),
                        $plan->name,
                        $plan->monthly_price_cents / 100,
                        config('app.name')
                    ),
                    function ($message) use ($tenant) {
                        $message->to($tenant->email)
                            ->subject('Subscription Renewal Reminder - Due in 3 Days');
                    }
                );

                $tenant->update(['email_notification_sent_at' => Carbon::now()]);

                $this->info("Sent notification to {$tenant->email} for subscription {$subscription->id}");
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to send notification to {$tenant->email}: {$e->getMessage()}");
            }
        }

        $this->info("Completed. Sent {$count} notifications.");
    }
}
