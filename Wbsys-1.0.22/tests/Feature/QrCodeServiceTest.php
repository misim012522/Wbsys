<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');

    return;
}

use App\Models\Tenant;
use App\Services\QrCodeService;

test('qr code service builds tenant-aware office urls', function () {
    config()->set('app.url', 'http://lvh.me:8000');

    $tenant = new Tenant([
        'name' => 'Rose of Sharon',
        'subdomain' => 'roseofsharon',
    ]);

    $url = app(QrCodeService::class)->queueOfficeUrl('roseofsharon', $tenant);

    expect($url)->toBe('http://roseofsharon.lvh.me:8000/o/roseofsharon');
});
