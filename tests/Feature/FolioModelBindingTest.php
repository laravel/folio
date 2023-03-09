<?php

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;

$purgeDirectories = function () {
    (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../fixtures/views'), preserve: true);

    touch(__DIR__.'/../fixtures/views/.gitkeep');
};

beforeEach($purgeDirectories);
afterEach($purgeDirectories);

test('basic implicit model binding', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1');

    $this->assertTrue(
        $view->data['folioModelBindingTestClass'] instanceof
        FolioModelBindingTestClass
    );

    $this->assertEquals(1, count($view->data));
});

test('basic implicit model bindings with more than one binding in path', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass|first]' => [
                '/posts' => [
                    '/[.FolioModelBindingTestClass|second].blade.php'
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1/posts/2');

    $this->assertTrue(
        $view->data['first'] instanceof
        FolioModelBindingTestClass
    );

    $this->assertTrue(
        $view->data['second'] instanceof
        FolioModelBindingTestClass
    );

    $this->assertEquals('1', $view->data['first']->value);
    $this->assertEquals('2', $view->data['second']->value);
    $this->assertNull($view->data['second']->childType);

    $this->assertEquals(2, count($view->data));
});

test('model binding can receive a custom binding field', function (string $pathString) {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1');

    $this->assertEquals(
        'slug',
        $view->data['folioModelBindingTestClass']->field
    );

    $this->assertEquals(1, count($view->data));
})->with(['-slug', ':slug']);

test('model binding can receive a custom binding variable', function (string $pathString, string $variable) {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1');

    $this->assertEquals(
        '1',
        $view->data[$variable]->value
    );

    $this->assertEquals(1, count($view->data));
})->with([
    ['-$foo', 'foo'],
    ['|foo', 'foo'],
]);

test('model binding can receive a custom binding field and custom binding variable at same time', function (string $pathString, string $field, string $variable) {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1');

    $this->assertEquals(
        $field,
        $view->data[$variable]->field
    );

    $this->assertEquals(1, count($view->data));
})->with([
    ['-slug-$foo', 'slug', 'foo'],
    [':slug|foo', 'slug', 'foo'],
]);

test('model binding can be resolved by explicit binding callback', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass].blade.php',
        ],
    ]);

    Route::bind('folioModelBindingTestClass', function ($value) {
        return new FolioModelBindingTestClass(strtoupper($value));
    });

    $router = $this->router();

    $view = $router->resolve('/users/abc');

    $this->assertEquals(
        'ABC',
        $view->data['folioModelBindingTestClass']->value
    );
});

test('model binding can span across multiple segments', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[...FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1/2/3');

    $this->assertTrue(is_array($view->data['folioModelBindingTestClasses']));
    $this->assertEquals('1', $view->data['folioModelBindingTestClasses'][0]->value);
    $this->assertEquals('2', $view->data['folioModelBindingTestClasses'][1]->value);
    $this->assertEquals('3', $view->data['folioModelBindingTestClasses'][2]->value);

    $this->assertEquals(1, count($view->data));
});

test('model binding can span across multiple segments with custom fields and variables', function (string $pathString, string $field, string $variable) {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[...FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1/2/3');

    $this->assertTrue(is_array($view->data[$variable]));

    $this->assertEquals('1', $view->data[$variable][0]->value);
    $this->assertEquals('2', $view->data[$variable][1]->value);
    $this->assertEquals('3', $view->data[$variable][2]->value);

    $this->assertEquals($field, $view->data[$variable][0]->field);
    $this->assertEquals($field, $view->data[$variable][1]->field);
    $this->assertEquals($field, $view->data[$variable][2]->field);
})->with([
    ['-slug-$foo', 'slug', 'foo'],
    [':slug|foo', 'slug', 'foo'],
]);

test('basic child model bindings are scoped to the parent', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass|first]' => [
                '/posts' => [
                    '/[.FolioModelBindingTestClass:slug|second].blade.php'
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1/posts/2');

    $this->assertEquals('1', $view->data['first']->value);

    $this->assertEquals(FolioModelBindingTestClass::class, $view->data['second']->childType);
    $this->assertEquals('slug', $view->data['second']->field);
    $this->assertEquals('2', $view->data['second']->value);

    $this->assertEquals(2, count($view->data));
});

class FolioModelBindingTestClass implements UrlRoutable
{
    public function __construct(public mixed $value = null, public mixed $field = null, public mixed $childType = null)
    {
    }

    public function getRouteKey()
    {
        return 1;
    }

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return new FolioModelBindingTestClass($value, $field);
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return new FolioModelBindingTestClass($value, $field, $childType);
    }
}
