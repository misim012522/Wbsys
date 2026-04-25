<?php
// Usage: php scripts/lookup_session.php "COOKIE_VALUE"
if ($argc < 2) {
    echo "Usage: php scripts/lookup_session.php \"COOKIE_VALUE\"\n";
    exit(1);
}
$id = $argv[1];
require __DIR__ . "/../vendor/autoload.php";
// Boot Laravel
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$row = DB::table(config('session.table', 'sessions'))
    ->where('id', $id)
    ->first();
if ($row) {
    print_r($row);
} else {
    echo "No session row found for id: $id\n";
}
