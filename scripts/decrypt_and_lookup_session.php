<?php
// Usage: php scripts/decrypt_and_lookup_session.php "COOKIE_VALUE"
if ($argc < 2) {
    echo "Usage: php scripts/decrypt_and_lookup_session.php \"COOKIE_VALUE\"\n";
    exit(1);
}
$cookie = $argv[1];
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

try {
    $decrypted = Crypt::decryptString(urldecode($cookie));
} catch (Exception $e) {
    echo "Decrypt failed: " . $e->getMessage() . "\n";
    exit(2);
}
echo "Decrypted cookie: $decrypted\n";

$id = $decrypted;
$row = DB::table(config('session.table', 'sessions'))->where('id', $id)->first();
if ($row) {
    print_r($row);
} else {
    echo "No session row found for id: $id\n";
}
