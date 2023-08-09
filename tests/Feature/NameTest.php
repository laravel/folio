<?php

use Laravel\Folio\Folio;
use Laravel\Folio\FolioRoutes;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

beforeEach(function () {
    Folio::route(__DIR__.'/resources/views/pages');
    Folio::path(__DIR__.'/resources/views/more-pages');

    app(FolioRoutes::class)->flush();
});

$dataset = [
    ['/users/Taylor', 'users.show', ['id' => 'Taylor']],
    ['/users/nuno', 'users.nuno', []],
    ['/dashboard', 'dashboard', []],
    ['/domain', 'my-domain', []],
    ['/', 'more-pages.index', []],
    ['/1/2/3/detail', 'more-pages.user.detail', ['users' => [1, 2, 3]]],
];

test('routes may have a name without cache', function (string $expected, $name, array $arguments = []) {
    $route = route($name, $arguments, false);

    expect($route)->toBe($expected);
})->with($dataset);

test('routes may have a name using cache', function (string $expected, $name, array $arguments = []) {
    app(FolioRoutes::class)->persist();

    app()->forgetInstance(FolioRoutes::class);

    $route = route($name, $arguments, false);

    expect($route)->toBe($expected);
})->with($dataset);

test('may be or not absolute', function () {
    $route = route('users.show', ['id' => 'Taylor'], false);
    $absoluteRoute = route('users.show', ['id' => 'Taylor'], true);

    expect($route)->toBe('/users/Taylor');
    expect($absoluteRoute)->toBe('http://localhost/users/Taylor');
});

test('routes may not have a name', function () {
    route('users.index');
})->throws(RouteNotFoundException::class, 'Route [users.index] not defined.');
