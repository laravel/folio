<?php

use Laravel\Folio\Folio;

beforeEach(function () {
    $_SERVER['__folio_users_middleware'] = false;
    $_SERVER['__folio_flights_middleware'] = false;
    $_SERVER['__folio_flights_inline_middleware'] = false;

    Folio::route(__DIR__.'/resources/views/pages', middleware: [
        '/users/*' => [
            function ($request, $next) {
                $_SERVER['__folio_users_middleware'] = true;

                return $next($request);
            },
        ],
        '/flights/*' => [
            function ($request, $next) {
                $_SERVER['__folio_flights_middleware'] = true;

                return $next($request);
            },
        ],
    ]);
});

test('pages can be rendered and middleware invoked', function () {
    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');

    $this->assertTrue($_SERVER['__folio_users_middleware']);
});

test('inline middleware are parsed from page and invoked', function () {
    $response = $this->get('/flights');

    $response->assertSee('Flight Index');

    $this->assertTrue($_SERVER['__folio_flights_middleware']);
    $this->assertTrue($_SERVER['__folio_flights_inline_middleware']);
});

test('middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/users/1');

    $this->assertCount(1, $middleware);
    $this->assertTrue($middleware[0] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});

test('inline middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/flights');

    $this->assertCount(2, $middleware);
    $this->assertTrue($middleware[0] instanceof Closure);
    $this->assertTrue($middleware[1] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});
