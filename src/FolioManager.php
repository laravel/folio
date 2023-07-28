<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Folio\Exceptions\NameNotFoundException;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\Pipeline\MatchedView;

class FolioManager
{
    /**
     * The mounted paths that have been registered.
     *
     * @var  array<int, \Laravel\Folio\MountPath>
     */
    protected array $mountPaths = [];

    /**
     * The callback that should be used to render matched views.
     */
    protected ?Closure $renderUsing = null;

    /**
     * The view that was last matched by Folio.
     */
    protected ?MatchedView $lastMatchedView = null;

    public function __construct(
        protected UrlGenerator $urlGenerator,
    ) {}

    /**
     * Register a route to handle page based routing at the given paths.
     *
     * @throws \InvalidArgumentException
     */
    public function route(string $path = null, ?string $uri = '/', array $middleware = [], array $names = []): static
    {
        $path = $path ? realpath($path) : config('view.paths')[0].'/pages';

        if (! is_dir($path)) {
            throw new InvalidArgumentException("The given path [{$path}] is not a directory.");
        }

        $this->mountPaths[] = $mountPath = new MountPath($path, $uri, $middleware, $names);

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
        return function (Request $request, string $uri = '/') use ($mountPath) {
            return (new RequestHandler(
                $mountPath,
                $this->renderUsing,
                fn (MatchedView $matchedView) => $this->lastMatchedView = $matchedView,
            ))($request, $uri);
        };
    }

    /**
     * Get the middleware that should be applied to the Folio handled URI.
     */
    public function middlewareFor(string $uri): array
    {
        foreach ($this->mountPaths as $mountPath) {
            if (! $matchedView = (new Router($mountPath->path))->match(new Request, $uri)) {
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
    public function data(string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->lastMatchedView?->data ?: [], $key, $default);
    }

    /**
     * Generate the URL to a Folio page.
     *
     * @throws \Laravel\Folio\Exceptions\NameNotFoundException
     * @throws \Laravel\Folio\Exceptions\UrlGenerationException
     */
    public function url(string $name, array $parameters = []): string
    {
        foreach ($this->mountPaths as $mountPath) {
            if (! array_key_exists($name, $mountPath->names)) {
                continue;
            }

            return $this->urlGenerator->path($mountPath, $mountPath->names[$name], $parameters);
        }

        throw new NameNotFoundException("Page [{$name}] not found.");
    }

    /**
     * Specify the callback that should be used to render matched views.
     */
    public function renderUsing(Closure $callback = null): self
    {
        $this->renderUsing = $callback;

        return $this;
    }

    /**
     * Get the array of mounted paths that have been registered.
     */
    public function mountPaths(): array
    {
        return $this->mountPaths;
    }

    /**
     * Get the mounted directory paths as strings.
     *
     * @return  array<int, string>
     */
    public function paths(): array
    {
        return collect($this->mountPaths)->map->path->all();
    }
}
