<?php

use App\Models\Tenant;
use App\Support\TenantUrl;

test('tenant url helper builds a login url from the tenant subdomain', function () {
    config()->set('app.url', 'http://localhost');

    $tenant = new Tenant([
        'name' => 'Registrar',
        'subdomain' => 'registrar',
    ]);

    expect(TenantUrl::login($tenant))->toBe('http://registrar.localhost/login');
    expect(TenantUrl::passwordReset($tenant))->toBe('http://registrar.localhost/forgot-password');
});

test('tenant url helper prefers a custom tenant domain when present', function () {
    config()->set('app.url', 'https://central.example.com');

    $tenant = new Tenant([
        'name' => 'Registrar',
        'domain' => 'portal.registrar.example.com',
        'subdomain' => 'registrar',
    ]);

    expect(TenantUrl::login($tenant))->toBe('https://portal.registrar.example.com/login');
});

test('tenant url helper falls back to the app url when no tenant is provided', function () {
    config()->set('app.url', 'https://central.example.com');

    expect(TenantUrl::login(null))->toBe('https://central.example.com/login');
});
