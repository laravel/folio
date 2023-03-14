<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
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
    public static function route(?string $to = null, ?string $uri = '/'): void
    {
        $to ??= config('view.paths')[0];

        Route::get($uri === '/' ? '/{uri?}' : '/'.trim($uri, '/').'/{uri?}', function (Request $request, $uri = '/') use ($to) {
            $matchedView = (new Router([$to]))->resolve($uri) ?? abort(404);

            return (
                static::$renderUsing ??= fn ($m) => View::file($m->path, $m->data)
            )($matchedView);
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
