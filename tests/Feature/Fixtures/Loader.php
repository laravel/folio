<?php

namespace Tests\Feature\Fixtures;

use Closure;
use Illuminate\Support\Facades\Route;
use Laravel\Folio\MountPath;

class Loader
{
    /**
     * Invoke the route loader.
     */
    public function __invoke(string $uri = '/', MountPath $mountPath, Closure $handler): void
    {
        if ($uri === '/') {
            Route::fallback($handler)
                ->name('test-folio-custom-loader');
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $handler
            )->name('test-folio-custom-loader')->where('uri', '.*');
        }
    }
}