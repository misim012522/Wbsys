<?php

namespace App\Providers;

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
        View::composer('*', function ($view) {
            if (array_key_exists('tenantTheme', $view->getData())) {
                return;
            }
            
            // Skip view composer for exception renderer views to prevent infinite loops
            $currentView = $view->getName();
            if (str_contains($currentView, 'laravel-exceptions-renderer::')) {
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
            }
        });
    }
}
