<?php
// Usage: php scripts/check_plan_settings.php <tenant_id>
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenantId = $argv[1] ?? null;
if (! $tenantId) {
    echo "Usage: php scripts/check_plan_settings.php <tenant_id>\n";
    exit(1);
}

$tenant = App\Models\Tenant::find((int) $tenantId);
if (! $tenant) {
    echo "Tenant with id {$tenantId} not found.\n";
    exit(1);
}

$mgr = new App\Services\TenantDatabaseManager();
$mgr->applyPlanSettings($tenant);

echo "limits:\n";
var_export($tenant->getSetting('limits'));
echo "\n\nsupport:\n";
var_export($tenant->getSetting('support'));
echo "\n";
