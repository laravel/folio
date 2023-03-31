<?php

use Illuminate\Filesystem\Filesystem;
use Laravel\Folio\Exceptions\PossibleDirectoryTraversal;

$purgeDirectories = function () {
    (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../tmp/views'), preserve: true);

    touch(__DIR__.'/../tmp/views/.gitkeep');
};

beforeEach($purgeDirectories);
afterEach($purgeDirectories);

test('root index view can be matched', function () {
    $this->views([
        '/index.blade.php',
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/index.blade.php'))->toEqual($router->match('/')->path)
        ->and($router->match('/missing-view'))->toBeNull();
});

test('directory index views can be matched', function () {
    $this->views([
        '/users' => [
            '/index.blade.php',
        ],
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/users/index.blade.php'))->toEqual($router->match('/users')->path);
});

test('literal views can be matched', function () {
    $this->views([
        '/index.blade.php',
        '/profile.blade.php',
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/profile.blade.php'))->toEqual($router->match('/profile')->path);
});

test('wildcard views can be matched', function () {
    $this->views([
        '/index.blade.php',
        '/[id].blade.php',
    ]);

    $router = $this->router();

    $resolved = $router->match('/1');

    expect(realpath(__DIR__.'/../tmp/views/[id].blade.php'))->toEqual($resolved->path)
        ->and($resolved->data)->toEqual(['id' => 1]);
});

test('literal views take precendence over wildcard views', function () {
    $this->views([
        '/index.blade.php',
        '/[id].blade.php',
        '/profile.blade.php',
    ]);

    $router = $this->router();

    $resolved = $router->match('/profile');

    expect(realpath(__DIR__.'/../tmp/views/profile.blade.php'))->toEqual($resolved->path)
        ->and($resolved->data)->toEqual([])
        ->and($router->match('/profile/missing-view'))->toBeNull();
});

test('literal views may be in directories', function () {
    $this->views([
        '/users' => [
            '/profile.blade.php',
        ],
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/users/profile.blade.php'))->toEqual($router->match('/users/profile')->path);
});

test('wildcard views may be in directories', function () {
    $this->views([
        '/users' => [
            '/[id].blade.php',
        ],
    ]);

    $router = $this->router();

    $resolved = $router->match('/users/1');

    expect(realpath(__DIR__.'/../tmp/views/users/[id].blade.php'))->toEqual($resolved->path);

    expect($resolved->data)->toEqual(['id' => 1]);
});

test('wildcard views must be blade files', function () {
    $this->views([
        '/users' => [
            '/[id].php',
        ],
    ]);

    $router = $this->router();

    expect($router->match('/users/1'))->toBeNull();
});

test('multisegment wildcard views can be matched', function () {
    $this->views([
        '/[...id].blade.php',
    ]);

    $router = $this->router();

    $resolved = $router->match('/1/2/3');

    expect(realpath(__DIR__.'/../tmp/views/[...id].blade.php'))->toEqual($resolved->path);

    expect($resolved->data)->toEqual(['id' => [1, 2, 3]]);
});

test('multisegment views take priority over further directories', function () {
    $this->views([
        '/[...id].blade.php',
        '/users' => [
            '/profile.blade.php',
        ],
    ]);

    $router = $this->router();

    $resolved = $router->match('/1/2/3');

    expect(realpath(__DIR__.'/../tmp/views/[...id].blade.php'))->toEqual($resolved->path);

    expect($resolved->data)->toEqual(['id' => [1, 2, 3]]);
});

test('wildcard directories are properly handled', function () {
    $this->views([
        '/flights' => [
            '/[id]' => [
                '/connections.blade.php',
            ],
        ],
    ]);

    $router = $this->router();

    $resolved = $router->match('/flights/1/connections');

    expect(realpath(__DIR__.'/../tmp/views/flights/[id]/connections.blade.php'))->toEqual($resolved->path);

    expect($resolved->data)->toEqual(['id' => 1]);
});

test('nested wildcard directories are properly handled', function () {
    $this->views([
        '/flights' => [
            '/[id]' => [
                '/connections' => [
                    '/[connectionId]' => [
                        '/map.blade.php',
                    ],
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $resolved = $router->match('/flights/1/connections/2/map');

    expect(realpath(__DIR__.'/../tmp/views/flights/[id]/connections/[connectionId]/map.blade.php'))->toEqual($resolved->path)
        ->and($resolved->data)->toEqual(['id' => 1, 'connectionId' => 2]);
});

it('ensures directory traversal is not possible', function () {
    $router = $this->router();

    $router->match('/../');
})->throws(PossibleDirectoryTraversal::class);
