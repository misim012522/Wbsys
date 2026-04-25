<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;

$tenants = Tenant::orderByDesc('id')->limit(50)->get(['id','name','slug','database_name','subdomain','domain','approved_at','is_active','created_at'])->toArray();
print_r($tenants);
