<?php

it('serves Blade shells without embedding merchant data', function () {
    $merchantName = 'Server-only merchant name';
    $routes = [
        '/login',
        '/register',
        '/dashboard',
        '/merchant',
        '/plans',
        '/customers',
        '/customers/999',
    ];

    foreach ($routes as $route) {
        $response = $this->get($route);

        $response->assertOk();
        $response->assertDontSee($merchantName);
        $response->assertDontSee('merchant_id');
    }
});
