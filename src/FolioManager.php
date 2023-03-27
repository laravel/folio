<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FolioManager
{
    /**
     * The mounted paths that have been registered.
     */
    protected array $mountPaths = [];

    /**
     * The callback that should be used to render mathced views.
     */
    protected ?Closure $renderUsing = null;

    /**
     * Register a route to handle page based routing at the given paths.
     */
    public function route(?string $path = null, ?string $uri = '/', array $middleware = []): static
    {
        $this->mountPaths[] = $mountPath = new MountPath(
            $path ? realpath($path) : config('view.paths')[0].'/pages', $uri, $middleware
        );

        if ($uri === '/') {
            Route::fallback($this->handler($mountPath))
                ->name($mountPath->routeName());
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $this->handler($mountPath)
            )->name($mountPath->routeName())->where('uri', '.*');
        }

        return $this;
    }

    /**
     * Get the Folio request handler function.
     */
    protected function handler(MountPath $mountPath): Closure
    {
        return function (Request $request, $uri = '/') use ($mountPath) {
            return (new RequestHandler($mountPath, $this->renderUsing))($request, $uri);
        };
    }

    /**
     * Get the middleware that should be applied to the Folio handled URI.
     */
    public function middlewareFor(string $uri): array
    {
        foreach ($this->mountPaths as $mountPath) {
            if (! $matchedView = (new Router($mountPath->path))->match($uri)) {
                continue;
            }

            return $mountPath->middleware->match($matchedView)->merge(
                $matchedView->inlineMiddleware()
            )->unique()->values()->all();
        }

        return [];
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
