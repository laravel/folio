<?php

namespace Laravel\Folio;

use BackedEnum;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\Pipeline\MatchedView;
use Laravel\Folio\Pipeline\PotentiallyBindablePathSegment;
use Laravel\Folio\Support\Project;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class FolioRoutes
{
    /**
     * Create a new Folio routes instance.
     *
     * @param  array<string, array{string, string}>  $routes
     */
    public function __construct(
        protected FolioManager $manager,
        protected string $cachedFolioRoutesPath,
        protected array $routes = [],
        protected bool $loaded = false,
    ) {
        $this->cachedFolioRoutesPath = str_replace(DIRECTORY_SEPARATOR, '/', $this->cachedFolioRoutesPath);
    }

    /**
     * Persist the loaded routes into the cache.
     */
    public function persist(): void
    {
        $this->flush();

        $this->ensureLoaded();

        File::put(
            $this->cachedFolioRoutesPath,
            '<?php return '.var_export($this->routes, true).';',
        );
    }

    /**
     * Ensure the routes have been loaded into memory.
     */
    protected function ensureLoaded(): void
    {
        if (! $this->loaded) {
            $this->load();
        }

        $this->loaded = true;
    }

    /**
     * Load the routes into memory.
     */
    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (File::exists($this->cachedFolioRoutesPath)) {
            $this->routes = File::getRequire($this->cachedFolioRoutesPath);

            return;
        }

        foreach ($this->manager->mountPaths() as $mountPath) {
            $views = Finder::create()->in($mountPath->path)->name('*.blade.php')->files()->getIterator();

            foreach ($views as $view) {
                $matchedView = new MatchedView($view->getRealPath(), [], $mountPath->path);

                if ($name = $matchedView->name()) {
                    $this->routes[$name] = [
                        Project::relativePathOf($matchedView->mountPath),
                        Project::relativePathOf($matchedView->path),
                    ];
                }
            }
        }

        $this->loaded = true;
    }

    /**
     * Determine if a route with the given name exists.
     */
    public function has(string $name): bool
    {
        $this->ensureLoaded();

        return isset($this->routes[$name]);
    }

    /**
     * Get the route URL for the given route name and arguments.
     */
    public function get(string $name, array $arguments, bool $absolute): string
    {
        $this->ensureLoaded();

        if (! isset($this->routes[$name])) {
            throw new RouteNotFoundException("Route [{$name}] not found.");
        }

        [$mountPath, $path] = $this->routes[$name];

        return with($this->path($mountPath, $path, $arguments), function (string $path) use ($absolute) {
            return $absolute ? url($path) : $path;
        });
    }

    /**
     * Get the relative route URL for the given route name and arguments.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function path(string $mountPath, string $path, array $parameters): string
    {
        $uri = str_replace('.blade.php', '', $path);

        $uri = collect(explode('/', $uri))
            ->map(function (string $segment) use ($parameters, $uri) {
                if (! Str::startsWith($segment, '[')) {
                    return $segment;
                }

                $segment = new PotentiallyBindablePathSegment($segment);

                $parameters = collect($parameters)->mapWithKeys(function (mixed $value, string $key) {
                    return [Str::camel($key) => $value];
                })->all();

                if (! isset($parameters[$name = $segment->variable()]) || $parameters[$name] === null) {
                    throw UrlGenerationException::forMissingParameter($uri, $name);
                }

                return $this->formatParameter(
                    $uri,
                    $name,
                    $parameters[$name],
                    $segment->field(),
                    $segment->capturesMultipleSegments()
                );
            })->implode('/');

        $uri = str_replace(['/index', '/index/'], ['', '/'], $uri);

        return '/'.ltrim(substr($uri, strlen($mountPath)), '/');
    }

    /**
     * Format the given parameter for placement in the route URL.
     *
     * @throws \Laravel\Folio\Exceptions\UrlGenerationException
     */
    protected function formatParameter(string $uri, string $name, mixed $parameter, string|bool $field, bool $variadic): mixed
    {
        $value = match (true) {
            $parameter instanceof UrlRoutable && $field !== false => $parameter->{$field},
            $parameter instanceof UrlRoutable => $parameter->getRouteKey(),
            $parameter instanceof BackedEnum => $parameter->value,
            $variadic => implode(
                '/',
                collect($parameter)
                    ->map(fn (mixed $value) => $this->formatParameter($uri, $name, $value, $field, false))
                    ->all()
            ),
            default => $parameter,
        };

        if (is_null($value)) {
            throw UrlGenerationException::forMissingParameter($uri, $name);
        }

        return $value;
    }

    /**
     * Get all of the registered routes.
     */
    public function routes(): array
    {
        $this->ensureLoaded();

        return $this->routes;
    }

    /**
     * Flush the cached routes.
     */
    public function flush(): void
    {
        File::delete($this->cachedFolioRoutesPath);

        $this->loaded = false;
    }
}
