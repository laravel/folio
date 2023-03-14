<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
     * Specify the path that contains pages.
     */
    public function to(string $to): self
    {
        $this->mountPaths = [$to];

        return $this;
    }

    /**
     * Mount the given paths as page based routing targets.
     */
    public function route(?string $to = null, ?string $uri = '/'): self
    {
        $to = match (true) {
            isset($to) => $to,
            count($this->mountPaths) > 0 => $this->mountPaths,
            default => config('view.paths')[0].'/pages',
        };

        Route::get($uri === '/' ? '/{uri?}' : '/'.trim($uri, '/').'/{uri?}', function (Request $request, $uri = '/') use ($to) {
            $matchedView = (new Router(Arr::wrap($to)))->resolve($uri) ?? abort(404);

            return (
                $this->renderUsing ??= fn ($m) => View::file($m->path, $m->data)
            )($matchedView);
        })->where('uri', '.*');

        return $this;
    }

    /**
     * Specify the middleware that should be applied to specific pages.
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Specify the callback that should be used to render matched views.
     */
    public function renderUsing(Closure $callback): self
    {
        $this->renderUsing = $callback;

        return $this;
    }
}
