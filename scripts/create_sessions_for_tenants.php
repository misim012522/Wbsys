<?php
// Usage: php scripts/create_sessions_for_tenants.php
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Support\TenantDatabaseName;

echo "Fetching tenants from central DB...\n";
$tenants = DB::connection('central')->table('tenants')->whereNotNull('database_name')->get();
if ($tenants->isEmpty()) {
    echo "No tenants found.\n";
    exit(0);
}

$createSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

foreach ($tenants as $t) {
    $rawDbName = (string) $t->database_name;
    $dbName = trim($rawDbName);
    if ($dbName === '') {
        $dbName = TenantDatabaseName::normalize('', (string) ($t->name ?? 'tenant'));
    }
    // Legacy tenant names may be stored as sqlite filenames (e.g. foo_buksu_queueless.db).
    // For MySQL schemas, drop the extension and keep only safe identifier characters.
    if (str_ends_with($dbName, '.sqlite')) {
        $dbName = substr($dbName, 0, -7);
    }
    if (str_ends_with($dbName, '.db')) {
        $dbName = substr($dbName, 0, -3);
    }
    $dbName = preg_replace('/[^A-Za-z0-9_]/', '_', $dbName) ?: 'tenant';
    echo "Processing tenant DB: {$rawDbName} -> {$dbName}\n";
    try {
        DB::connection('central')->statement(
            'CREATE DATABASE IF NOT EXISTS `'.$dbName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        // Create a temporary connection using the same mysql settings but different database
        $conf = config('database.connections.mysql');
        $conf['database'] = $dbName;
        DB::purge("tenant_{$dbName}");
        config(["database.connections.tenant_{$dbName}" => $conf]);
        DB::connection("tenant_{$dbName}")->statement($createSql);
        echo " -> sessions table ensured for $dbName\n";
    } catch (Exception $e) {
        echo " -> ERROR for $dbName: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
