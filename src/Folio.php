<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Route;

class Folio
{
    /**
     * Mount the given paths as page based routing targets.
     */
    public static function mount(string $mountPath): void
    {
        Route::get('/{uri?}', function ($uri = '/') use ($mountPath) {
            return (new Router([$mountPath]))->resolve($uri) ?? abort(404);
        })->where('uri', '.*');
    }
}
