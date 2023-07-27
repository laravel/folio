<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Support\Facades\Route;

class Loader
{
    /**
     * Invoke the route loader.
     */
    public function __invoke(string $uri = '/', MountPath $mountPath, Closure $handler): void
    {
        if ($uri === '/') {
            Route::fallback($handler)
                ->name($mountPath->routeName());
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $handler
            )->name($mountPath->routeName())->where('uri', '.*');
        }
    }
}