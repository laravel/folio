<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Laravel\Folio\Pipeline\MatchedView;

class FolioManager
{
    /**
     * The mounted paths that have been registered.
     *
     * @var array<int, \Laravel\Folio\MountPath>
     */
    protected array $mountPaths = [];

    /**
     * The callback that should be used to render matched views.
     */
    protected ?Closure $renderUsing = null;

    /**
     * The callback that should be used when terminating the manager.
     */
    protected ?Closure $terminateUsing = null;

    /**
     * The view that was last matched by Folio.
     */
    protected ?MatchedView $lastMatchedView = null;

    /**
     * Register a route to handle page based routing at the given paths.
     *
     * @param  array<string, array<int, string>>  $middleware
     *
     * @throws \InvalidArgumentException
     */
    public function route(?string $path = null, ?string $uri = '/', array $middleware = []): PendingRoute
    {
        return new PendingRoute(
            $this,
            $path ? $path : config('view.paths')[0].'/pages',
            $uri,
            $middleware
        );
    }

    /**
     * Registers the given route.
     *
     * @param  array<string, array<int, string>>  $middleware
     */
    public function registerRoute(string $path, string $uri, array $middleware, ?string $domain): void
    {
        $path = realpath($path);
        $uri = '/'.ltrim($uri, '/');

        if (! is_dir($path)) {
            throw new InvalidArgumentException("The given path [{$path}] is not a directory.");
        }

        $this->mountPaths[] = $mountPath = new MountPath(
            $path,
            $uri,
            $middleware,
            $domain,
        );

        Route::fallback($this->handler())->name('laravel-folio');
    }

    /**
     * Get the Folio request handler function.
     */
    protected function handler(): Closure
    {
        return function (Request $request) {
            $this->terminateUsing = null;

            $mountPaths = collect($this->mountPaths)->filter(
                fn (MountPath $mountPath) => str_starts_with(mb_strtolower('/'.$request->path()), $mountPath->baseUri)
            )->all();

            return (new RequestHandler(
                $mountPaths,
                $this->renderUsing,
                fn (MatchedView $matchedView) => $this->lastMatchedView = $matchedView,
            ))($request);
        };
    }

    /**
     * Get the middleware that should be applied to the Folio handled URI.
     *
     * @return array<int, string>
     */
    public function middlewareFor(string $uri): array
    {
        foreach ($this->mountPaths as $mountPath) {
            if (! $matchedView = (new Router($mountPath))->match(new Request, $uri)) {
                continue;
            }

            return $mountPath->middleware->match($matchedView)->merge(
                $matchedView->inlineMiddleware()
            )->unique()->values()->all();
        }

        return [];
    }

    /**
     * Get a piece of data from the route / view that was last matched by Folio.
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->lastMatchedView?->data ?: [], $key, $default);
    }

    /**
     * Specify the callback that should be used to render matched views.
     */
    public function renderUsing(?Closure $callback = null): static
    {
        $this->renderUsing = $callback;

        return $this;
    }

    /**
     * Execute the pending termination callback.
     */
    public function terminate(): void
    {
        if ($this->terminateUsing) {
            try {
                ($this->terminateUsing)();
            } finally {
                $this->terminateUsing = null;
            }
        }
    }

    /**
     * Specify the callback that should be used when terminating the application.
     */
    public function terminateUsing(?Closure $callback = null): static
    {
        $this->terminateUsing = $callback;

        return $this;
    }

    /**
     * Get the array of mounted paths that have been registered.
     *
     * @return array<int, \Laravel\Folio\MountPath>
     */
    public function mountPaths(): array
    {
        return $this->mountPaths;
    }

    /**
     * Get the mounted directory paths as strings.
     *
     * @return array<int, string>
     */
    public function paths(): array
    {
        return collect($this->mountPaths)->map->path->all();
    }

    /**
     * Dynamically pass methods to a new pending route registration.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): PendingRoute
    {
        return $this->route()->$method(...$parameters);
    }
}
