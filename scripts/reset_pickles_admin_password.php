<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) {
    echo "Tenant not found\n";
    exit(1);
}

echo "Tenant id: {$t->id}, database_name: {$t->database_name}\n";

try {
    $admin = User::on('tenant')
        ->where('tenant_id', $t->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->first();

    if (! $admin) {
        echo "No tenant admin found to reset.\n";
        exit(1);
    }

    $newPassword = 'ChangeMe123';
    $admin->password = Hash::make($newPassword);
    $admin->save();

    echo "Admin username={$admin->username}, id={$admin->id} password reset to: $newPassword\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
