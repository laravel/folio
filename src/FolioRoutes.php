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
     * Create a new route names instance.
     */
    public function __construct(
        protected FolioManager $manager,
        protected string $cachedFolioRoutesPath,
        protected array $routes = [],
        protected bool $loaded = false,
    ) {
    }

    /**
     * Whether the given route name exists.
     */
    public function has(string $name): bool
    {
        $this->ensureLoaded();

        return isset($this->routes[$name]);
    }

    /**
     * Get the path to the given route name.
     */
    public function get(string $name, array $arguments, bool $absolute): string
    {
        $this->ensureLoaded();

        if (! isset($this->routes[$name])) {
            throw new RouteNotFoundException("Route [{$name}] not found.");
        }

        [$mountPath, $path] = $this->routes[$name];

        return with($this->path($mountPath, $path, $arguments, $absolute), function (string $path) use ($absolute) {
            return $absolute ? url($path) : $path;
        });
    }

    /**
     * Flushes the routes from the cache.
     */
    public function flush(): void
    {
        File::delete($this->cachedFolioRoutesPath);

        $this->loaded = false;
    }

    /**
     * Persists the routes to the cache.
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
     * Loads the routes from the manager's mount paths.
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
     * Determine if the routes have been loaded.
     */
    protected function ensureLoaded(): void
    {
        if (! $this->loaded) {
            $this->load();
        }

        $this->loaded = true;
    }

    /**
     * Get the path to the given route.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function path(string $mountPath, string $path, array $parameters, bool $absolute): string
    {
        $uri = str_replace('.blade.php', '', $path);

        $uri = collect(explode('/', $uri))
            ->map(function (string $segment) use ($parameters, $uri) {
                if (! Str::startsWith($segment, '[')) {
                    return $segment;
                }

                $segment = new PotentiallyBindablePathSegment($segment);
                $name = $segment->variable();

                if (! isset($parameters[$name])) {
                    throw UrlGenerationException::forMissingParameter($uri, $name);
                }

                return $this->formatParameter($parameters[$name], $segment->field(), $segment->capturesMultipleSegments());
            })->implode('/');

        $uri = str_replace(['/index', '/index/'], ['', '/'], $uri);

        // if

        return '/'.ltrim(substr($uri, strlen($mountPath)), '/');
    }

    /**
     * Formats the given URL parameter.
     */
    protected function formatParameter(mixed $parameter, string|bool $field, bool $variadic): mixed
    {
        if ($parameter instanceof UrlRoutable && $field !== false) {
            return $parameter->{$field};
        }

        if ($parameter instanceof UrlRoutable) {
            return $parameter->getRouteKey();
        }

        if ($parameter instanceof BackedEnum) {
            return $parameter->value;
        }

        if ($variadic) {
            return implode(
                '/',
                collect($parameter)
                    ->map(fn (mixed $value) => $this->formatParameter($value, $field, false))
                    ->all()
            );
        }

        return $parameter;
    }
}
