<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Usage:
 *   php scripts/upgrade_assigned_staff_column.php single <subdomain-or-domain>
 *   php scripts/upgrade_assigned_staff_column.php all
 */

$mode = $argv[1] ?? null;
$target = $argv[2] ?? null;

if (! in_array($mode, ['single', 'all'], true)) {
    fwrite(STDERR, "Invalid mode. Use: single <tenant-key> | all\n");
    exit(1);
}

$manager = app(TenantDatabaseManager::class);

$upgradeTenant = static function (Tenant $tenant) use ($manager): array {
    try {
        $manager->initializeSchema($tenant);
        $manager->activate($tenant);

        $hasColumn = Schema::connection('tenant')->hasColumn('queue_entries', 'assigned_staff_user_id');

        return [
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'domain' => $tenant->domain,
            'ok' => true,
            'has_column' => $hasColumn,
            'error' => null,
        ];
    } catch (Throwable $e) {
        return [
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'domain' => $tenant->domain,
            'ok' => false,
            'has_column' => false,
            'error' => $e->getMessage(),
        ];
    }
};

if ($mode === 'single') {
    if (! $target) {
        fwrite(STDERR, "Missing tenant key for single mode.\n");
        exit(1);
    }

    $tenant = Tenant::query()
        ->where('subdomain', $target)
        ->orWhere('domain', $target)
        ->first();

    if (! $tenant) {
        fwrite(STDERR, "Tenant not found for key: {$target}\n");
        exit(1);
    }

    $result = $upgradeTenant($tenant);

    if (! $result['ok']) {
        fwrite(STDERR, "FAILED tenant_id={$result['tenant_id']} subdomain={$result['subdomain']} error={$result['error']}\n");
        exit(1);
    }

    fwrite(STDOUT, "OK tenant_id={$result['tenant_id']} subdomain={$result['subdomain']} has_column=".($result['has_column'] ? 'yes' : 'no')."\n");
    exit($result['has_column'] ? 0 : 2);
}

$tenants = Tenant::query()->orderBy('id')->get();
$failed = 0;
$missingColumn = 0;

foreach ($tenants as $tenant) {
    $result = $upgradeTenant($tenant);

    if (! $result['ok']) {
        $failed++;
        fwrite(STDERR, "FAILED tenant_id={$result['tenant_id']} subdomain={$result['subdomain']} error={$result['error']}\n");
        continue;
    }

    if (! $result['has_column']) {
        $missingColumn++;
        fwrite(STDOUT, "WARN tenant_id={$result['tenant_id']} subdomain={$result['subdomain']} has_column=no\n");
        continue;
    }

    fwrite(STDOUT, "OK tenant_id={$result['tenant_id']} subdomain={$result['subdomain']} has_column=yes\n");
}

fwrite(STDOUT, "SUMMARY tenants={$tenants->count()} failed={$failed} missing_column={$missingColumn}\n");

if ($failed > 0 || $missingColumn > 0) {
    exit(1);
}

exit(0);
