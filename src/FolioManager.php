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
     * Register the routes to handle page based routing at the given paths.
     */
    public function route(?string $to = null, ?string $uri = '/'): self
    {
        if ($uri === '/') {
            Route::fallback($this->handler($to));
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $this->handler($to)
            )->where('uri', '.*');
        }

        return $this;
    }

    /**
     * Get the Folio request handler function.
     */
    protected function handler(?string $to): Closure
    {
        return function (Request $request, $uri = '/') use ($to) {
            $to = match (true) {
                isset($to) => $to,
                default => config('view.paths')[0].'/pages',
            };

            $matchedView = (new Router(Arr::wrap($to)))->resolve($uri) ?? abort(404);

            return (
                $this->renderUsing ??= fn ($m) => View::file($m->path, $m->data)
            )($matchedView);
        };
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
