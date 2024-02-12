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
     * The current version of the persisted route cache.
     */
    protected static int $version = 1;

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
            '<?php return '.var_export([
                'version' => static::$version,
                'routes' => $this->routes,
            ], true).';',
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
            $cache = File::getRequire($this->cachedFolioRoutesPath);

            if (isset($cache['version']) && (int) $cache['version'] === static::$version) {
                $this->routes = $cache['routes'];

                $this->loaded = true;

                return;
            }
        }

        foreach ($this->manager->mountPaths() as $mountPath) {
            $views = Finder::create()->in($mountPath->path)->name('*.blade.php')->files()->getIterator();

            foreach ($views as $view) {
                $matchedView = new MatchedView($view->getRealPath(), [], $mountPath->path);

                if ($name = $matchedView->name()) {
                    $this->routes[$name] = [
                        'mountPath' => Project::relativePathOf($matchedView->mountPath),
                        'path' => Project::relativePathOf($matchedView->path),
                        'baseUri' => $mountPath->baseUri,
                        'domain' => $mountPath->domain,
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
     *
     * @thows  \Laravel\Folio\Exceptions\UrlGenerationException
     */
    public function get(string $name, array $arguments, bool $absolute): string
    {
        $this->ensureLoaded();

        if (! isset($this->routes[$name])) {
            throw new RouteNotFoundException("Route [{$name}] not found.");
        }

        [
            'mountPath' => $mountPath,
            'path' => $path,
            'baseUri' => $baseUri,
            'domain' => $domain,
        ] = $this->routes[$name];

        [$path, $remainingArguments] = $this->path($mountPath, $path, $arguments);

        $route = new Route(['GET'], '{__folio_path}', fn () => null);

        $route->name($name)->domain($domain);

        $uri = $baseUri === '/' ? $path : $baseUri.$path;

        try {
            return url()->toRoute($route, [...$remainingArguments, '__folio_path' => $uri], $absolute);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $e) {
            throw new UrlGenerationException(str_replace('{__folio_path}', $uri, $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * Get the relative route URL for the given route name and arguments.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{string, array<string, mixed>}
     */
    protected function path(string $mountPath, string $path, array $parameters): array
    {
        $uri = str_replace('.blade.php', '', $path);

        [$parameters, $usedParameters] = [collect($parameters), collect()];

        $uri = collect(explode('/', $uri))
            ->map(function (string $segment) use ($parameters, $uri, $usedParameters) {
                if (! Str::startsWith($segment, '[')) {
                    return $segment;
                }

                $segment = new PotentiallyBindablePathSegment($segment);

                $name = $segment->variable();

                $key = $parameters->search(function (mixed $value, string $key) use ($name) {
                    return Str::camel($key) === $name && $value !== null;
                });

                if ($key === false) {
                    throw UrlGenerationException::forMissingParameter($uri, $name);
                }

                $usedParameters->add($key);

                return $this->formatParameter(
                    $uri,
                    Str::camel($key),
                    $parameters->get($key),
                    $segment->field(),
                    $segment->capturesMultipleSegments()
                );
            })->implode('/');

        $uri = match (true) {
            str_ends_with($uri, '/index') => substr($uri, 0, -6),
            str_ends_with($uri, '/index/') => substr($uri, 0, -7),
            default => $uri,
        };

        return [
            '/'.ltrim(substr($uri, strlen($mountPath)), '/'),
            $parameters->except($usedParameters->all())->all(),
        ];
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
