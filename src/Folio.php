<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class Folio
{
    /**
     * The callback that should be used to render mathced views.
     */
    protected static ?Closure $renderUsing = null;

    /**
     * Mount the given paths as page based routing targets.
     */
    public static function route(?string $mountPath = null, ?string $uri = '/'): void
    {
        $mountPath ??= config('view.paths')[0];

        Route::get($uri === '/' ? '/{uri?}' : '/'.trim($uri, '/').'/{uri?}', function ($uri = '/') use ($mountPath) {
            return (
                static::$renderUsing ??= fn ($m) => View::file($m->path, $m->data)
            )((new Router([$mountPath]))->resolve($uri) ?? abort(404));
        })->where('uri', '.*');
    }

    /**
     * Specify the callback that should be used to render matched views.
     */
    public static function renderUsing(Closure $callback): void
    {
        static::$renderUsing = $callback;
    }
}
