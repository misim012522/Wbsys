<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$id = urldecode('eyJpdiI6ImhveXRQODdCTmMwOWFqbVp4YnE0TGc9PSIsInZhbHVlIjoiYlByTWpNMHNvNDFhRGdoS0NMMURyNWpDU0w2QVNVWXVzUE92aExmc3I4blBSWHRCQ1hPenp3MFZFcUZCUW9vZ2NGcVZUMEdhWlRiRm9HMUk3aUtyNVNTY3VMdDFpN0hjWFdRd2NkMjlKUzlTRzdHRHlwWHM0bVdvSzRtQTQvczkiLCJtYWMiOiI1YmI3NDRkZWQ3ZjQzZTYxNmE0MWEyZmU0ZDZlODhiNjRlZDk5MmJjNzg4M2UzNjFhZTAxMWRlOGM0NzZlNTQ2IiwidGFnIjoiIn0=');

$session = DB::table('sessions')->where('id', $id)->first();
if (! $session) {
    echo "Session not found with id (decoded).\n";
    // try raw value
    $session = DB::table('sessions')->where('id', urldecode($id))->first();
}

if (! $session) {
    echo "Session not found. Trying raw cookie string...\n";
    $session = DB::table('sessions')->where('id', 'eyJpdiI6ImhveXRQODdCTmMwOWFqbVp4YnE0TGc9PSIsInZhbHVlIjoiYlByTWpNMHNvNDFhRGdoS0NMMURyNWpDU0w2QVNVWXVzUE92aExmc3I4blBSWHRCQ1hPenp3MFZFcUZCUW9vZ2NGcVZUMEdhWlRiRm9HMUk3aUtyNVNTY3VMdDFpN0hjWFdRd2NkMjlKUzlTRzdHRHlwWHM0bVdvSzRtQTQvczkiLCJtYWMiOiI1YmI3NDRkZWQ3ZjQzZTYxNmE0MWEyZmU0ZDZlODhiNjRlZDk5MmJjNzg4M2UzNjFhZTAxMWRlOGM0NzZlNTQ2IiwidGFnIjoiIn0=')->first();
}

if (! $session) {
    echo "No session found in central DB. Now checking tenant DB...\n";
    try {
        config(['database.connections.tenant.database' => 'pickles_buksu_queueless']);
        $t = DB::connection('tenant')->table('sessions')->where('id', $id)->first();
        if ($t) {
            echo "Found session in tenant DB:\n"; print_r($t); exit(0);
        }
        echo "No session found in tenant DB either.\n";
        exit(0);
    } catch (\Throwable $e) {
        echo "Error checking tenant DB: " . $e->getMessage() . "\n";
        exit(1);
    }
}

print_r($session);

// if payload exists, show unserialized data
if (isset($session->payload)) {
    echo "\nRaw payload: ".$session->payload."\n";
}
