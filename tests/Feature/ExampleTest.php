<?php

if (! extension_loaded('pdo_sqlite')) {
    test('skip-database-driver', function () {
        $this->assertTrue(true);
    })->skip('No pdo_sqlite driver available; tests require sqlite in-memory.');
    return;
}

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
