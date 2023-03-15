<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class FolioManager
{
    /**
     * The mounted paths that have been registered.
     */
    protected array $mountedPaths = [];

    /**
     * The callback that should be used to render mathced views.
     */
    protected ?Closure $renderUsing = null;

    /**
     * Register the routes to handle page based routing at the given paths.
     */
    public function route(?string $to = null, ?string $uri = '/', array $middleware = []): self
    {
        $to = match (true) {
            isset($to) => realpath($to),
            default => config('view.paths')[0].'/pages',
        };

        $this->mountedPaths[] = $mountedPath = new MountedPath(
            $to, $uri, $middleware
        );

        if ($uri === '/') {
            Route::fallback($this->handler($mountedPath))
                ->name('folio-'.substr(sha1($uri), 0, 10));
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $this->handler($mountedPath)
            )->name('folio-'.substr(sha1($uri), 0, 10))->where('uri', '.*');
        }

        return $this;
    }

    /**
     * Get the Folio request handler function.
     */
    protected function handler(MountedPath $mountedPath): Closure
    {
        return function (Request $request, $uri = '/') use ($mountedPath) {
            return (new RequestHandler(
                $mountedPath, $this->renderUsing
            ))($request, $uri);
        };
    }

    /**
     * Get the middleware that should be applied to the Folio handled URI.
     */
    public function middlewareFor(string $uri): array
    {
        foreach ($this->mountedPaths as $mountedPath) {
            $matchedView = (new Router(Arr::wrap($mountedPath->mountPath)))->resolve($uri);

            if (! $matchedView) {
                continue;
            }

            return $mountedPath->middleware->match($matchedView)->merge(
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
