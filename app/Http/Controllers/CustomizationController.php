<?php

namespace App\Http\Controllers;

use App\Support\TenantDashboardProfile;
use Illuminate\Http\Request;

/**
 * Customization (design + functions) per tenant: branding, feature toggles, labels.
 */
class CustomizationController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()?->tenant;
        if (! $tenant) {
            return redirect()->route('admin.dashboard')->with('info', 'No tenant assigned.');
        }

        return view('admin.customization.index', [
            'tenant' => $tenant,
            'primaryColor' => $tenant->getSetting('theme.primary_color', '#2563eb'),
            'logoUrl' => $tenant->getSetting('theme.logo_url'),
            'supportUrl' => $tenant->support_url,
            'appName' => $tenant->getSetting('theme.app_name', config('app.name')),
            'dashboardProfile' => $tenant->getSetting('dashboard.profile', TenantDashboardProfile::inferFromName($tenant->name)),
            'dashboardProfiles' => [
                'general' => 'General',
                'registrar' => 'Registrar',
                'cashier' => 'Cashier',
                'clinic' => 'Clinic',
                'guidance' => 'Guidance',
            ],
            'guestQueueEnabled' => $tenant->getSetting('customization.guest_queue', true),
            'appointmentsEnabled' => $tenant->getSetting('customization.appointments', true),
            'showServiceType' => $tenant->getSetting('customization.show_service_type', true),
            'showPurposeField' => $tenant->getSetting('customization.show_purpose_field', true),
            'queueLabel' => $tenant->getSetting('customization.labels.queue', 'Queue'),
            'officeLabel' => $tenant->getSetting('customization.labels.office', 'Office'),
            'appointmentLabel' => $tenant->getSetting('customization.labels.appointment', 'Appointment'),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()?->tenant;
        if (! $tenant) {
            return back()->with('error', 'No tenant assigned.');
        }

        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'support_url' => ['nullable', 'url', 'max:500'],
            'app_name' => ['nullable', 'string', 'max:64'],
            'guest_queue' => ['nullable', 'boolean'],
            'appointments' => ['nullable', 'boolean'],
            'show_service_type' => ['nullable', 'boolean'],
            'show_purpose_field' => ['nullable', 'boolean'],
            'dashboard_profile' => ['nullable', 'in:general,registrar,cashier,clinic,guidance'],
            'label_queue' => ['nullable', 'string', 'max:32'],
            'label_office' => ['nullable', 'string', 'max:32'],
            'label_appointment' => ['nullable', 'string', 'max:32'],
        ]);

        if (! empty($validated['primary_color'])) {
            $tenant->setSetting('theme.primary_color', $validated['primary_color']);
        }
        $tenant->setSetting('theme.logo_url', $validated['logo_url'] ?? null);
        $tenant->update(['support_url' => $validated['support_url'] ?? null]);
        $tenant->setSetting('theme.app_name', $validated['app_name'] ?? config('app.name'));

        $tenant->setSetting('customization.guest_queue', $request->boolean('guest_queue'));
        $tenant->setSetting('customization.appointments', $request->boolean('appointments'));
        $tenant->setSetting('customization.show_service_type', $request->boolean('show_service_type'));
        $tenant->setSetting('customization.show_purpose_field', $request->boolean('show_purpose_field'));
        $tenant->setSetting('dashboard.profile', $validated['dashboard_profile'] ?? TenantDashboardProfile::inferFromName($tenant->name));

        $labels = $tenant->getSetting('customization.labels', []);
        $labels['queue'] = $validated['label_queue'] ?? 'Queue';
        $labels['office'] = $validated['label_office'] ?? 'Office';
        $labels['appointment'] = $validated['label_appointment'] ?? 'Appointment';
        $tenant->setSetting('customization.labels', $labels);

        return redirect()->route('admin.customization.index')->with('success', 'Customization saved.');
    }
}
