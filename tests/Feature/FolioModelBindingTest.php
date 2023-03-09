<?php

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../fixtures/views'), preserve: true);

    touch(__DIR__.'/../fixtures/views/.gitkeep');
});

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
});

test('model binding can receive a custom binding field', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass-slug].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1');

    $this->assertEquals(
        'slug',
        $view->data['folioModelBindingTestClass']->field
    );
});

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
            '/[....FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->resolve('/users/1/2/3');

    $this->assertTrue(is_array($view->data['folioModelBindingTestClass']));
    $this->assertEquals('1', $view->data['folioModelBindingTestClass'][0]->value);
    $this->assertEquals('2', $view->data['folioModelBindingTestClass'][1]->value);
    $this->assertEquals('3', $view->data['folioModelBindingTestClass'][2]->value);
});

class FolioModelBindingTestClass implements UrlRoutable
{
    public function __construct(public mixed $value = null, public mixed $field = null)
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
        //
    }
}
