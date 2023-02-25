<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Laravel\Folio\Router;
use Tests\TestCase;

class FolioTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory(realpath(__DIR__.'/../fixtures/views'), preserve: true);
    }

    public function test_root_index_view_can_be_matched()
    {
        $this->views([
            '/index.blade.php',
        ]);

        $router = $this->router();

        $this->assertEquals($router->resolve('/')->path, realpath(__DIR__.'/../fixtures/views/index.blade.php'));
        $this->assertNull($router->resolve('/missing-view'));
    }

    public function test_directory_index_views_can_be_matched()
    {
        $this->views([
            '/users' => [
                '/index.blade.php',
            ],
        ]);

        $router = $this->router();

        $this->assertEquals(
            $router->resolve('/users')->path,
            realpath(__DIR__.'/../fixtures/views/users/index.blade.php')
        );
    }

    public function test_literal_view_can_be_matched()
    {
        $this->views([
            '/index.blade.php',
            '/profile.blade.php',
        ]);

        $router = $this->router();

        $this->assertEquals(
            $router->resolve('/profile')->path,
            realpath(__DIR__.'/../fixtures/views/profile.blade.php')
        );
    }

    public function test_wildcard_view_can_be_matched()
    {
        $this->views([
            '/index.blade.php',
            '/[id].blade.php',
        ]);

        $router = $this->router();

        $this->assertEquals(
            $router->resolve('/1')->path,
            realpath(__DIR__.'/../fixtures/views/[id].blade.php')
        );
    }

    public function test_literal_views_take_precendence_over_wildcard_views()
    {
        $this->views([
            '/index.blade.php',
            '/[id].blade.php',
            '/profile.blade.php',
        ]);

        $router = $this->router();

        $this->assertEquals(
            $router->resolve('/profile')->path,
            realpath(__DIR__.'/../fixtures/views/profile.blade.php')
        );
    }

    public function test_literal_views_may_be_in_directories()
    {
        $this->views([
            '/users' => [
                '/profile.blade.php',
            ],
        ]);

        $router = $this->router();

        $this->assertEquals(
            $router->resolve('/users/profile')->path,
            realpath(__DIR__.'/../fixtures/views/users/profile.blade.php')
        );
    }

    public function test_wildcard_views_may_be_in_directories()
    {
        $this->views([
            '/users' => [
                '/[id].blade.php',
            ],
        ]);

        $router = $this->router();

        $resolved = $router->resolve('/users/1');

        $this->assertEquals(
            $resolved->path,
            realpath(__DIR__.'/../fixtures/views/users/[id].blade.php')
        );

        $this->assertEquals(['id' => 1], $resolved->data);
    }

    public function test_multisegment_wildcard_views()
    {
        $this->views([
            '/[...id].blade.php',
        ]);

        $router = $this->router();

        $resolved = $router->resolve('/1/2/3');

        $this->assertEquals(
            $resolved->path,
            realpath(__DIR__.'/../fixtures/views/[...id].blade.php')
        );

        $this->assertEquals(['id' => [1, 2, 3]], $resolved->data);
    }

    public function test_multisegment_views_take_priority_over_further_directories()
    {
        $this->views([
            '/[...id].blade.php',
            '/users' => [
                '/profile.blade.php',
            ],
        ]);

        $router = $this->router();

        $resolved = $router->resolve('/1/2/3');

        $this->assertEquals(
            $resolved->path,
            realpath(__DIR__.'/../fixtures/views/[...id].blade.php')
        );

        $this->assertEquals(['id' => [1, 2, 3]], $resolved->data);
    }

    public function test_wildcard_directories()
    {
        $this->views([
            '/flights' => [
                '/[id]' => [
                    '/connections.blade.php',
                ],
            ],
        ]);

        $router = $this->router();

        $resolved = $router->resolve('/flights/1/connections');

        $this->assertEquals(
            $resolved->path,
            realpath(__DIR__.'/../fixtures/views/flights/[id]/connections.blade.php')
        );

        $this->assertEquals(['id' => 1], $resolved->data);
    }

    public function test_nested_wildcard_directories()
    {
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

        $this->assertEquals(
            $resolved->path,
            realpath(__DIR__.'/../fixtures/views/flights/[id]/connections/[connectionId]/map.blade.php')
        );

        $this->assertEquals(['id' => 1, 'connectionId' => 2], $resolved->data);
    }

    protected function router()
    {
        return new Router([realpath(__DIR__.'/../fixtures/views')]);
    }

    protected function views(array $views, $directory = null)
    {
        $directory ??= __DIR__.'/../fixtures/views';

        foreach ($views as $key => $value) {
            if (is_array($value)) {
                (new Filesystem)->makeDirectory($directory.$key);

                $this->views($value, $directory.$key);
            } else {
                touch($directory.$value);
            }
        }
    }
}
