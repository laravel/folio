<?php

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Filesystem\Filesystem;

afterEach(function () {
    (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../fixtures/views'), preserve: true);

    touch(__DIR__.'/../fixtures/views/.gitkeep');
});

test('basic model binding', function () {
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

test('model binding can receive an explicit binding field', function () {
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
