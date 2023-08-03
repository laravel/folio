<?php

use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Dependency;
use Tests\Feature\Fixtures\Http\Middleware\WithTerminableMiddleware;

beforeEach(function () {
    $_SERVER['__folio_*_middleware'] = false;
    $_SERVER['__folio_users_middleware'] = false;
    $_SERVER['__folio_flights_middleware'] = false;
    $_SERVER['__folio_flights_inline_middleware'] = false;
    $_SERVER['__folio_*_middleware.terminate'] = 0;
    $_SERVER['__folio_*_middleware.terminate.should_fail'] = false;
    $_SERVER['__folio_*_middleware.terminate.dependency'] = false;

    Folio::route(__DIR__.'/resources/views/pages', middleware: [
        '*' => [
            WithTerminableMiddleware::class,
        ],
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

afterEach(function () {
    unset(
        $_SERVER['__folio_*_middleware'],
        $_SERVER['__folio_*_middleware.terminate'],
        $_SERVER['__folio_users_middleware'],
        $_SERVER['__folio_flights_middleware'],
        $_SERVER['__folio_flights_inline_middleware'],
    );
});

test('pages can be rendered and middleware invoked', function () {
    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');

    $this->assertTrue($_SERVER['__folio_*_middleware']);
    $this->assertTrue($_SERVER['__folio_users_middleware']);
    $this->assertSame(1, $_SERVER['__folio_*_middleware.terminate']);
});

test('inline middleware are parsed from page and invoked', function () {
    $response = $this->get('/flights');

    $response->assertSee('Flight Index');

    $this->assertTrue($_SERVER['__folio_*_middleware']);
    $this->assertTrue($_SERVER['__folio_flights_middleware']);
    $this->assertTrue($_SERVER['__folio_flights_inline_middleware']);
    $this->assertSame(1, $_SERVER['__folio_*_middleware.terminate']);
});

test('middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/users/1');

    $this->assertCount(2, $middleware);

    $this->assertTrue($middleware[0] === WithTerminableMiddleware::class);
    $this->assertTrue($middleware[1] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});

test('inline middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/flights');

    $this->assertCount(3, $middleware);
    $this->assertTrue($middleware[0] === WithTerminableMiddleware::class);
    $this->assertTrue($middleware[1] instanceof Closure);
    $this->assertTrue($middleware[2] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});

test('terminate middleware is invoked after response is sent', function () {
    $this->get('/users/Taylor');

    $this->assertSame(1, $_SERVER['__folio_*_middleware.terminate']);

    $this->get('/users/Taylor');

    $this->assertSame(2, $_SERVER['__folio_*_middleware.terminate']);

    $this->get('/non-existent');

    $this->assertSame(2, $_SERVER['__folio_*_middleware.terminate']);

    $this->get('/users/Taylor');

    $this->assertSame(3, $_SERVER['__folio_*_middleware.terminate']);
});

test('terminate method is invoked using application container', function () {
    $this->app->bind(Dependency::class, fn () => new Dependency());

    $this->get('/users/Taylor');

    $this->assertInstanceOf(Dependency::class, $firstDependency = $_SERVER['__folio_*_middleware.terminate.dependency']);

    $_SERVER['__folio_*_middleware.terminate.dependency'] = false;

    $this->get('/non-existent');

    $this->assertFalse($_SERVER['__folio_*_middleware.terminate.dependency']);

    $this->get('/users/Taylor');

    $this->assertInstanceOf(Dependency::class, $secondDependency = $_SERVER['__folio_*_middleware.terminate.dependency']);

    $this->assertNotSame($firstDependency, $secondDependency);
});

test('terminate callback not invoked on non matched routes if previous terminate callback throws an exception', function () {
    $_SERVER['__folio_*_middleware.terminate.should_fail'] = true;

    expect(fn () => $this->get('/users/Taylor'))->toThrow(Exception::class);

    $this->assertSame(1, $_SERVER['__folio_*_middleware.terminate']);

    $this->get('/non-existent');

    $this->assertSame(1, $_SERVER['__folio_*_middleware.terminate']);
});
