<?php

namespace App\Support;

use App\Models\Tenant;

class TenantDashboardProfile
{
    /**
     * @return array<string, mixed>
     */
    public static function for(?Tenant $tenant): array
    {
        $profile = self::profileKey($tenant);

        $profiles = [
            'registrar' => [
                'key' => 'registrar',
                'name' => 'Registrar dashboard',
                'headline' => 'Track documents, applications, and student-facing requests.',
                'admin_focus' => 'Prioritize application review, registrar schedules, and release readiness.',
                'office_focus' => 'Monitor incoming applications, release requests, and queue traffic.',
                'admin_cards' => [
                    ['title' => 'Application review', 'body' => 'Keep pending requests moving and make sure office staff approvals do not block document processing.'],
                    ['title' => 'Release coordination', 'body' => 'Watch claim-ready items, queue load, and registrar turnaround across the day.'],
                ],
                'office_cards' => [
                    ['title' => 'Student request flow', 'body' => 'Handle walk-in applications, queue check-ins, and status updates from one queue board.'],
                    ['title' => 'Document release', 'body' => 'Use the activity and reports pages to confirm what was released, cancelled, or rescheduled.'],
                ],
            ],
            'cashier' => [
                'key' => 'cashier',
                'name' => 'Cashier dashboard',
                'headline' => 'Keep payment lines moving and balance in-person service with scheduled transactions.',
                'admin_focus' => 'Watch counter load, staffing, and payment-related service throughput.',
                'office_focus' => 'Serve payment queues quickly, reduce waiting time, and monitor peak transaction hours.',
                'admin_cards' => [
                    ['title' => 'Counter throughput', 'body' => 'Use daily counts and pending staff visibility to keep payment windows staffed during peak hours.'],
                    ['title' => 'Queue balancing', 'body' => 'Direct attention to bottlenecks before long cashier lines build up.'],
                ],
                'office_cards' => [
                    ['title' => 'Payment operations', 'body' => 'Process waiting transactions and keep the payment line moving.'],
                    ['title' => 'Daily cashier activity', 'body' => 'Review transaction-heavy periods and use reports to track service completion.'],
                ],
            ],
            'clinic' => [
                'key' => 'clinic',
                'name' => 'Clinic dashboard',
                'headline' => 'Coordinate walk-ins, consultations, and health-service queues.',
                'admin_focus' => 'Track consultation volume, staffing coverage, and queue flow for the clinic.',
                'office_focus' => 'Handle check-ins, monitor consultations, and reduce waiting time for health services.',
                'admin_cards' => [
                    ['title' => 'Consultation flow', 'body' => 'Watch queue load to keep the clinic responsive throughout the day.'],
                    ['title' => 'Health service coverage', 'body' => 'Use staffing and completion counts to spot where support is needed most.'],
                ],
                'office_cards' => [
                    ['title' => 'Patient-facing service', 'body' => 'Manage walk-ins and live queue updates in one place.'],
                    ['title' => 'Daily clinic review', 'body' => 'Track completed consultations with activity and reports.'],
                ],
            ],
            'guidance' => [
                'key' => 'guidance',
                'name' => 'Guidance dashboard',
                'headline' => 'Support counseling intake, scheduled sessions, and student follow-up work.',
                'admin_focus' => 'Monitor counseling demand, intake flow, and guidance staff availability.',
                'office_focus' => 'Handle counseling queues, session schedules, and daily guidance activity.',
                'admin_cards' => [
                    ['title' => 'Counseling load', 'body' => 'Watch how many sessions and walk-ins are building up so support stays available.'],
                    ['title' => 'Follow-up visibility', 'body' => 'Use reports and staffing insights to keep guidance work consistent across the week.'],
                ],
                'office_cards' => [
                    ['title' => 'Session handling', 'body' => 'Serve intake queues and keep daily counseling operations organized.'],
                    ['title' => 'Student support activity', 'body' => 'Review cancellations, completed sessions, and office-side updates from one workspace.'],
                ],
            ],
            'general' => [
                'key' => 'general',
                'name' => 'Tenant dashboard',
                'headline' => 'Manage tenant operations, incoming requests, and staff activity inside this workspace.',
                'admin_focus' => 'Watch approvals, queue pressure, and tenant-wide service activity.',
                'office_focus' => 'Handle daily queue operations for this tenant workspace.',
                'admin_cards' => [
                    ['title' => 'Workspace controls', 'body' => 'Keep the tenant staffed, review pending accounts, and monitor daily service demand.'],
                    ['title' => 'Operational visibility', 'body' => 'Use reports, activity, and quick actions to keep services moving.'],
                ],
                'office_cards' => [
                    ['title' => 'Daily operations', 'body' => 'Manage the live queue and service completion from one dashboard.'],
                    ['title' => 'Workspace follow-through', 'body' => 'Use reports and activity logs to review what happened during the day.'],
                ],
            ],
        ];

        return $profiles[$profile] ?? $profiles['general'];
    }

    public static function inferFromName(string $name): string
    {
        $normalized = strtolower($name);

        return match (true) {
            str_contains($normalized, 'registrar') => 'registrar',
            str_contains($normalized, 'cashier') => 'cashier',
            str_contains($normalized, 'clinic') || str_contains($normalized, 'health') => 'clinic',
            str_contains($normalized, 'guidance') || str_contains($normalized, 'counsel') => 'guidance',
            default => 'general',
        };
    }

    private static function profileKey(?Tenant $tenant): string
    {
        if (! $tenant) {
            return 'general';
        }

        return (string) $tenant->getSetting('dashboard.profile', self::inferFromName($tenant->name));
    }
}
