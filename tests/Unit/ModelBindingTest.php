<?php

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$purgeDirectories = function () {
    (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../tmp/views'), preserve: true);

    touch(__DIR__.'/../tmp/views/.gitkeep');
};

beforeEach($purgeDirectories);
afterEach($purgeDirectories);

test('implicit model binding', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1');

    $this->assertTrue(
        $view->data['folioModelBindingTestClass'] instanceof FolioModelBindingTestClass
    );

    $this->assertEquals(1, count($view->data));
});

test('missing models trigger model not found exception', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $router->match(new Request, '/users/_missing');
})->throws(ModelNotFoundException::class);

test('implicit model bindings with more than one binding in path', function ($first, $second) {

    if (windows_os() && $first === '|first') {
        $this->markTestSkipped();
    }


    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'. $first . ']' => [
                '/posts' => [
                    '/[.FolioModelBindingTestClass'.$second.'].blade.php',
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1/posts/2');

    $this->assertTrue(
        $view->data['first'] instanceof FolioModelBindingTestClass
    );

    $this->assertTrue(
        $view->data['second'] instanceof FolioModelBindingTestClass
    );

    $this->assertEquals('1', $view->data['first']->value);
    $this->assertEquals('2', $view->data['second']->value);
    $this->assertNull($view->data['second']->childType);

    $this->assertEquals(2, count($view->data));
})->with([
    ['|first', '|second'],
    ['-$first', '-$second']
]);

test('model bindings can receive a custom binding field with', function (string $pathString) {

    if (windows_os() && $pathString === ':slug') {
        $this->markTestSkipped();
    }


    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1');

    $this->assertEquals(
        'slug',
        $view->data['folioModelBindingTestClass']->field
    );

    $this->assertEquals(1, count($view->data));
})->with(['-slug', ':slug']);

test('model bindings can receive a custom binding variable', function (string $pathString, string $variable) {


    if (windows_os() && $pathString === '|foo') {
        $this->markTestSkipped();
    }

    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1');

    $this->assertEquals(
        '1',
        $view->data[$variable]->value
    );

    $this->assertEquals(1, count($view->data));
})->with([
    ['-$foo', 'foo'],
    ['|foo', 'foo'],
]);

test('model bindings can receive a custom binding field and custom binding variable at same time', function (string $pathString, string $field, string $variable) {

    if (windows_os() && $pathString === ':slug|foo') {
        $this->markTestSkipped();
    }

    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1');

    $this->assertEquals(
        $field,
        $view->data[$variable]->field
    );

    $this->assertEquals(1, count($view->data));
})->with([
    ['-slug-$foo', 'slug', 'foo'],
    [':slug|foo', 'slug', 'foo'],
]);

test('model bindings can be resolved by explicit binding callback', function () {
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

    $view = $router->match(new Request, '/users/abc');

    $this->assertEquals(
        'ABC',
        $view->data['folioModelBindingTestClass']->value
    );
});

test('model bindings can span across multiple segments', function () {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[...FolioModelBindingTestClass].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1/2/3');

    $this->assertTrue(is_array($view->data['folioModelBindingTestClasses']));
    $this->assertEquals('1', $view->data['folioModelBindingTestClasses'][0]->value);
    $this->assertEquals('2', $view->data['folioModelBindingTestClasses'][1]->value);
    $this->assertEquals('3', $view->data['folioModelBindingTestClasses'][2]->value);

    $this->assertEquals(1, count($view->data));
})->skip(windows_os());

test('model bindings can span across multiple segments with custom fields and variables', function (string $pathString, string $field, string $variable) {
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[...FolioModelBindingTestClass'.$pathString.'].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1/2/3');

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
])->skip(windows_os());

test('child model bindings are scoped to the parent when field is present on child', function ($first, $second) {

    if (windows_os() && $first === '|first') {
        $this->markTestSkipped();
    }

    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$first.']' => [
                '/posts' => [
                    '/[.FolioModelBindingTestChildClass'.$second.'].blade.php',
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/users/1/posts/2');

    $this->assertEquals('1', $view->data['first']->value);

    $this->assertEquals('second', $view->data['second']->childType);
    $this->assertInstanceOf(FolioModelBindingTestChildClass::class, $view->data['second']);
    $this->assertEquals('slug', $view->data['second']->field);
    $this->assertEquals('2', $view->data['second']->value);

    $this->assertEquals(2, count($view->data));
})->with([
    ['|first', ':slug|second'],
    ['-$first', '-slug-$second'],
]);

test('explicit model bindings take precedence over implicit scoped child bindings', function ($first, $second) {

    if (windows_os() && $first === '|first') {
        $this->markTestSkipped();
    }

    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$first.']' => [
                '/posts' => [
                    '/[.FolioModelBindingTestClass'.$second.'].blade.php',
                ],
            ],
        ],
    ]);

    Route::bind('first', function ($value) {
        return new FolioModelBindingTestClass(strtoupper($value));
    });

    Route::bind('second', function ($value) {
        return new FolioModelBindingTestClass(strtoupper($value));
    });

    $router = $this->router();

    $view = $router->match(new Request, '/users/abc/posts/def');

    $this->assertEquals('ABC', $view->data['first']->value);
    $this->assertEquals('DEF', $view->data['second']->value);

    $this->assertEquals(2, count($view->data));
})->with([
    ['|first', ':slug|second'],
    ['-$first', '-slug-$second'],
]);

test('scoped child model bindings trigger model not found exception if they do not exist', function ($first, $second) {
    
    if (windows_os() && $first === '|first') {
        $this->markTestSkipped();
    }
    
    $this->views([
        '/index.blade.php',
        '/users' => [
            '/[.FolioModelBindingTestClass'.$first.']' => [
                '/posts' => [
                    '/[.FolioModelBindingTestClass'.$second.'].blade.php',
                ],
            ],
        ],
    ]);

    $router = $this->router();

    $router->match(new Request, '/users/1/posts/_missing');
})->with([
    ['|first', ':slug|second'],
    ['-$first', '-slug-$second'],
])->throws(ModelNotFoundException::class);

test('model bindings can be enums', function () {
    $this->views([
        '/categories' => [
            '/[.Tests.Feature.Fixtures.Category].blade.php',
        ],
    ]);

    $router = $this->router();

    $view = $router->match(new Request, '/categories/posts');

    $this->assertEquals('posts', $view->data['category']->value);

    $this->assertEquals(1, count($view->data));
});

class FolioModelBindingTestClass implements UrlRoutable
{
    public $trashed = false;

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
        if ($value === '_missing') {
            return null;
        }

        return new FolioModelBindingTestClass($value, $field);
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        if ($value === '_missing') {
            return null;
        }

        return new FolioModelBindingTestClass($value, $field);
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($value === '_missing') {
            return null;
        }

        return new FolioModelBindingTestChildClass($value, $field, $childType);
    }
}

class FolioModelBindingTestChildClass extends FolioModelBindingTestClass
{
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        throw new Exception('Model does not have children.');
    }
}
