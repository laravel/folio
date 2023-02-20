<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class Folio
{
    /**
     * Mount the given paths as page based routing targets.
     */
    public static function mount(string $mountPath): void
    {
        Route::get('/{uri?}', function ($uri = '/') use ($mountPath) {
            $matchedView = (new Router([$mountPath]))->resolve($uri) ?? abort(404);

            return View::file($matchedView->path, $matchedView->data);
        })->where('uri', '.*');
    }
}
