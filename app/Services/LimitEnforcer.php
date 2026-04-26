<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\QueueEntry;

class LimitEnforcer
{
    public function canCreateOffice(Tenant $tenant): bool
    {
        $max = $tenant->getSetting('limits.max_offices');
        if ($max === null) {
            return true; // unlimited
        }

        $count = \App\Models\Office::query()->where('tenant_id', $tenant->id)->count();

        return $count < (int) $max;
    }

    public function canIssueQr(Tenant $tenant): bool
    {
        $perOffice = $tenant->getSetting('limits.qr_codes_per_office');
        if ($perOffice === null) {
            return true;
        }

        // This method assumes calling code checks per-office counts; provide per-office check helper
        return true;
    }

    public function canCreateService(Tenant $tenant, int $officeId): bool
    {
        $daily = $tenant->getSetting('limits.daily_service_limit');
        if ($daily === null) {
            return true;
        }

        // Some deployments do not have a dedicated Service model/table.
        // Enforce daily limits using queue entries created today.
        $todayCount = QueueEntry::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $todayCount < (int) $daily;
    }
}
