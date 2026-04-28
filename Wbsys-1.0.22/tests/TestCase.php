<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            config([
                'database.connections.central' => config('database.connections.sqlite'),
                'database.connections.tenant' => config('database.connections.sqlite'),
            ]);
        }

        config([
            'recaptcha.secret_key' => null,
            'recaptcha.site_key' => null,
        ]);
    }
}
