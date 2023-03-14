<?php

use Laravel\Folio\Folio;

beforeEach(function () {
    $_SERVER['__folio_users_middleware'] = false;

    Folio::route(__DIR__.'/resources/views/pages', middleware: [
        '/users/*' => [
            function ($request, $next) {
                $_SERVER['__folio_users_middleware'] = true;

                return $next($request);
            }
        ],
    ]);
});

test('pages can be rendered and middleware invoked', function () {
    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');

    $this->assertTrue($_SERVER['__folio_users_middleware']);
});
