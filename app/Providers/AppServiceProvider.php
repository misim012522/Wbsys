<?php

namespace App\Providers;

use App\Models\SupportThread;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            TenantSubscription::backfillMissingMonthlyEndDates();
            TenantSubscription::expirePastDue();
        } catch (\Throwable) {
            // Ignore expiry sync failures during boot so the app can still render.
        }

        View::composer('*', function ($view) {
            if (array_key_exists('tenantTheme', $view->getData())) {
                return;
            }

            // Skip framework-owned views that do not need tenant theme data.
            $currentView = $view->getName();
            if (
                str_contains($currentView, 'laravel-exceptions-renderer::')
                || str_starts_with($currentView, 'mail::')
                || str_starts_with($currentView, 'notifications::')
            ) {
                return;
            }

            try {
                $tenant = null;
                if (auth()->check() && auth()->user()->tenant_id) {
                    $tenant = auth()->user()->tenant;
                }
                if (! $tenant && app()->bound('current_tenant')) {
                    $tenant = app('current_tenant');
                }
                $tenantTheme = [
                    'primary_color' => '#059669',
                    'logo_url' => null,
                    'app_name' => config('app.name'),
                    'queue_label' => 'Queue',
                    'office_label' => 'Office',
                    'appointment_label' => 'Appointment',
                    'guest_queue_enabled' => true,
                    'appointments_enabled' => true,
                    'show_service_type' => true,
                    'show_purpose_field' => true,
                ];
                if ($tenant) {
                    $tenantTheme = [
                        'primary_color' => $tenant->getSetting('theme.primary_color', '#2563eb'),
                        'logo_url' => $tenant->getSetting('theme.logo_url'),
                        'app_name' => $tenant->getSetting('theme.app_name', config('app.name')),
                        'queue_label' => $tenant->getSetting('customization.labels.queue', 'Queue'),
                        'office_label' => $tenant->getSetting('customization.labels.office', 'Office'),
                        'appointment_label' => $tenant->getSetting('customization.labels.appointment', 'Appointment'),
                        'guest_queue_enabled' => $tenant->getSetting('customization.guest_queue', true),
                        'appointments_enabled' => $tenant->getSetting('customization.appointments', true),
                        'show_service_type' => $tenant->getSetting('customization.show_service_type', true),
                        'show_purpose_field' => $tenant->getSetting('customization.show_purpose_field', true),
                    ];
                }
                $view->with('tenantTheme', $tenantTheme);

                $supportWidget = [
                    'enabled' => false,
                    'ready' => false,
                    'threads' => collect(),
                    'activeThread' => null,
                    'unreadCount' => 0,
                    'open' => false,
                ];

                $user = auth()->user();
                $tenantUser = $tenant && $user && ! $user->isCentralUser();

                if ($tenantUser && SupportThread::supportTablesExist()) {
                    $threads = SupportThread::query()
                        ->where('tenant_id', $tenant->id)
                        ->with('tenant')
                        ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
                        ->get();

                    $selectedThreadId = request()->integer('support_thread')
                        ?: session('support_widget_thread_id');

                    $activeThread = $selectedThreadId
                        ? $threads->firstWhere('id', $selectedThreadId)
                        : $threads->first();

                    if ($activeThread) {
                        $activeThread->load('messages');
                    }

                    $supportWidget = [
                        'enabled' => true,
                        'ready' => true,
                        'threads' => $threads,
                        'activeThread' => $activeThread,
                        'unreadCount' => $threads->filter(fn (SupportThread $thread) => $thread->hasUnreadForTenant())->count(),
                        'open' => request()->boolean('support_open') || session('support_widget_open', false),
                    ];
                } elseif ($tenantUser) {
                    $supportWidget['enabled'] = true;
                }

                $view->with('tenantSupportWidget', $supportWidget);
            } catch (\Throwable $e) {
                // Prevent errors in view composer from causing infinite exception rendering loops
                // Set safe defaults instead
                $view->with('tenantTheme', [
                    'primary_color' => '#059669',
                    'logo_url' => null,
                    'app_name' => config('app.name'),
                    'queue_label' => 'Queue',
                    'office_label' => 'Office',
                    'appointment_label' => 'Appointment',
                    'guest_queue_enabled' => true,
                    'appointments_enabled' => true,
                    'show_service_type' => true,
                    'show_purpose_field' => true,
                ]);
                $view->with('tenantSupportWidget', [
                    'enabled' => false,
                    'ready' => false,
                    'threads' => collect(),
                    'activeThread' => null,
                    'unreadCount' => 0,
                    'open' => false,
                ]);
            }
        });
    }
}
