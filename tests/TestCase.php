<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Laravel\Folio\FolioServiceProvider;
use Laravel\Folio\Router;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get the package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            FolioServiceProvider::class,
        ];
    }

    /**
     * Create the given views.
     *
     * @param  array<string, array<string, string>|string>  $views
     */
    protected function views(array $views, $directory = null): void
    {
        $directory ??= __DIR__.'/tmp/views';

        foreach ($views as $key => $value) {
            if (is_array($value)) {
                (new Filesystem)->makeDirectory($directory.$key);

                $this->views($value, $directory.$key);
            } else {
                touch($directory.$value);
            }
        }
    }

    /**
     * Create a new router instance.
     */
    protected function router(): Router
    {
        return new Router([__DIR__.'/tmp/views']);
    }
}
