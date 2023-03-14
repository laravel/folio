<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class FolioManager
{
    /**
     * The registered middleware.
     */
    protected array $middleware = [];

    /**
     * The callback that should be used to render mathced views.
     */
    protected ?Closure $renderUsing = null;

    /**
     * Mount the given paths as page based routing targets.
     */
    public function route(?string $to = null, ?string $uri = '/'): Folio
    {
        $to ??= config('view.paths')[0];

        Route::get($uri === '/' ? '/{uri?}' : '/'.trim($uri, '/').'/{uri?}', function (Request $request, $uri = '/') use ($to) {
            $matchedView = (new Router([$to]))->resolve($uri) ?? abort(404);

            return (
                $this->renderUsing ??= fn ($m) => View::file($m->path, $m->data)
            )($matchedView);
        })->where('uri', '.*');

        return $this;
    }

    /**
     * Specify the middleware that should be applied to specific pages.
     */
    public function middleware(array $middleware): Folio
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Specify the callback that should be used to render matched views.
     */
    public function renderUsing(Closure $callback): Folio
    {
        $this->renderUsing = $callback;

        return $this;
    }
}
