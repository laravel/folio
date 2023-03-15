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


test('middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/users/1');

    $this->assertCount(1, $middleware);
    $this->assertTrue($middleware[0] instanceof Closure);
    $this->assertEmpty(Folio::middlewareFor('/flights/1'));
});
