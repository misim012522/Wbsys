<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) { echo "Tenant not found\n"; exit(1); }

echo "tenant id: {$t->id}\n";
echo "database_name: {$t->database_name}\n";

try {
    $admins = User::on('tenant')->where('tenant_id', $t->id)->where('role', User::ROLE_TENANT_ADMIN)->get();
    if ($admins->isEmpty()) {
        echo "No tenant admin users found.\n";
    } else {
        foreach ($admins as $a) {
            echo "admin id: {$a->id}, username: {$a->username}, email: {$a->email}, password_hash: {$a->password}\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error querying tenant DB: " . $e->getMessage() . "\n";
}
