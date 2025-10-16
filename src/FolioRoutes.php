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
    public function get(string $name, mixed $arguments, bool $absolute): string
    {
        $this->ensureLoaded();

        if (! isset($this->routes[$name])) {
            throw new RouteNotFoundException("Route [{$name}] not found.");
        }

        // Normalize arguments to always be an array
        if (! is_array($arguments)) {
            $arguments = [$arguments];
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

        // Normalize positional parameters to match Laravel's route() helper behavior
        $parameters = $this->normalizeParameters($parameters, $uri);

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

    /**
     * Normalize positional parameters to match Laravel's route() helper behavior.
     *
     * This method handles cases where parameters are passed with numeric keys
     * (like route('name', $model) or route('name', [$model1, $model2]))
     * and assigns them to the appropriate route segments based on type matching.
     *
     * @param  \Illuminate\Support\Collection  $parameters
     * @param  string  $uri
     * @return \Illuminate\Support\Collection
     */
    protected function normalizeParameters($parameters, string $uri): \Illuminate\Support\Collection
    {
        // Extract all segments from the URI path
        $allSegments = collect(explode('/', $uri))
            ->filter(fn (string $segment) => Str::startsWith($segment, '['))
            ->values();

        // If no segments, return parameters as-is
        if ($allSegments->isEmpty()) {
            return $parameters;
        }

        // Find parameters with numeric keys (positional parameters)
        $positionalParameters = $parameters->filter(function ($value, $key) {
            return is_numeric($key);
        });

        // If no positional parameters, return parameters as-is
        if ($positionalParameters->isEmpty()) {
            return $parameters;
        }

        $normalizedParameters = $parameters->except($positionalParameters->keys());
        $unusedSegments = $allSegments->filter(function ($segment) use ($parameters) {
            $segmentObj = new PotentiallyBindablePathSegment($segment);
            $name = $segmentObj->variable();
            return ! $parameters->has($name);
        });

        // Assign positional parameters to unused segments
        $positionalParameters->each(function ($value, $key) use ($normalizedParameters, $unusedSegments) {
            if ($unusedSegments->isNotEmpty()) {
                $segment = $unusedSegments->first();
                $segmentObj = new PotentiallyBindablePathSegment($segment);

                // If the segment is bindable and the value is a model, use the model directly
                // Otherwise, convert the value to its route key
                if ($segmentObj->bindable() && is_object($value) && method_exists($value, 'getRouteKey')) {
                    $normalizedParameters->put($segmentObj->variable(), $value);
                } else {
                    // For non-bindable segments or scalar values, convert to route key
                    $routeKey = is_object($value) && method_exists($value, 'getRouteKey')
                        ? $value->getRouteKey()
                        : $value;
                    $normalizedParameters->put($segmentObj->variable(), $routeKey);
                }

                $unusedSegments->shift();
            }
        });

        return $normalizedParameters;
    }

    /**
     * Check if a parameter matches a bindable segment by type.
     *
     * @param  mixed  $parameter
     * @param  \Laravel\Folio\Pipeline\PotentiallyBindablePathSegment  $segment
     * @return bool
     */
    protected function parameterMatchesSegment($parameter, $segment): bool
    {
        if (! $segment->bindable()) {
            return false;
        }

        $expectedClass = $segment->class();

        // Check if parameter is an instance of the expected class
        if (is_object($parameter) && $parameter instanceof $expectedClass) {
            return true;
        }

        // Check if parameter is an array of the expected class
        if (is_array($parameter) && ! empty($parameter)) {
            $firstItem = reset($parameter);
            return is_object($firstItem) && $firstItem instanceof $expectedClass;
        }

        return false;
    }
}
