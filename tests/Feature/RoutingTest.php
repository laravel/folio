<?php

use Illuminate\Filesystem\Filesystem;

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

    expect(realpath(__DIR__.'/../tmp/views/index.blade.php'))->toEqual($router->resolve('/')->path)
        ->and($router->resolve('/missing-view'))->toBeNull();
});

test('directory index views can be matched', function () {
    $this->views([
        '/users' => [
            '/index.blade.php',
        ],
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/users/index.blade.php'))->toEqual($router->resolve('/users')->path);
});

test('literal views can be matched', function () {
    $this->views([
        '/index.blade.php',
        '/profile.blade.php',
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/profile.blade.php'))->toEqual($router->resolve('/profile')->path);
});

test('wildcard views can be matched', function () {
    $this->views([
        '/index.blade.php',
        '/[id].blade.php',
    ]);

    $router = $this->router();

    $resolved = $router->resolve('/1');

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

    $resolved = $router->resolve('/profile');

    expect(realpath(__DIR__.'/../tmp/views/profile.blade.php'))->toEqual($resolved->path)
        ->and($resolved->data)->toEqual([])
        ->and($router->resolve('/profile/missing-view'))->toBeNull();
});

test('literal views may be in directories', function () {
    $this->views([
        '/users' => [
            '/profile.blade.php',
        ],
    ]);

    $router = $this->router();

    expect(realpath(__DIR__.'/../tmp/views/users/profile.blade.php'))->toEqual($router->resolve('/users/profile')->path);
});

test('wildcard views may be in directories', function () {
    $this->views([
        '/users' => [
            '/[id].blade.php',
        ],
    ]);

    $router = $this->router();

    $resolved = $router->resolve('/users/1');

    expect(realpath(__DIR__.'/../tmp/views/users/[id].blade.php'))->toEqual($resolved->path);

    expect($resolved->data)->toEqual(['id' => 1]);
});

test('multisegment wildcard views can be matched', function () {
    $this->views([
        '/[...id].blade.php',
    ]);

    $router = $this->router();

    $resolved = $router->resolve('/1/2/3');

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

    $resolved = $router->resolve('/1/2/3');

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

    $resolved = $router->resolve('/flights/1/connections');

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

    $resolved = $router->resolve('/flights/1/connections/2/map');

    expect(realpath(__DIR__.'/../tmp/views/flights/[id]/connections/[connectionId]/map.blade.php'))->toEqual($resolved->path)
        ->and($resolved->data)->toEqual(['id' => 1, 'connectionId' => 2]);
});
