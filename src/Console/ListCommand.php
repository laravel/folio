<?php

namespace Laravel\Folio\Console;

use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Laravel\Folio\FolioManager;
use Laravel\Folio\FolioRoutes;
use Laravel\Folio\MountPath;
use Laravel\Folio\Support\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'folio:list')]
class ListCommand extends RouteListCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'folio:list';

    /**
     * The table headers for the command.
     *
     * @var array<int, string>
     */
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'View'];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $mountPaths = $this->laravel->make(FolioManager::class)->mountPaths();

        $routes = $this->routesFromMountPaths($mountPaths);

        if ($routes->isEmpty()) {
            $this->components->error("Your application doesn't have any routes.");
        } else {
            $this->displayRoutes(
                $this->toDisplayableFormat($routes->all()),
            );
        }
    }

    /**
     * Get the formatted action for display on the CLI.
     *
     * @param  array<string, string>  $route
     */
    protected function formatActionForCli($route): string
    {
        $action = $route['view'];

        if (is_string($route['name'])) {
            $action = $route['name'].' › '.$action;
        }

        return $action;
    }

    /**
     * Compute the routes from the given mounted paths.
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    protected function routesFromMountPaths(array $mountPaths): Collection
    {
        return collect($mountPaths)->map(function (MountPath $mountPath) {
            $views = Finder::create()->in($mountPath->path)->name('*.blade.php')->files()->getIterator();

            $baseUri = rtrim($mountPath->baseUri, '/');
            $domain = $mountPath->domain;
            $mountPath = str_replace(DIRECTORY_SEPARATOR, '/', $mountPath->path);

            $path = '/'.ltrim($mountPath, '/');

            return collect($views)
                ->map(function (SplFileInfo $view) use ($baseUri, $domain, $mountPath) {
                    $viewPath = str_replace(DIRECTORY_SEPARATOR, '/', $view->getRealPath());
                    $uri = $baseUri.str_replace($mountPath, '', $viewPath);

                    if (count($this->laravel->make(FolioManager::class)->mountPaths()) === 1) {
                        $action = str_replace($mountPath.'/', '', $viewPath);
                    } else {
                        $basePath = str_replace(DIRECTORY_SEPARATOR, '/', base_path(DIRECTORY_SEPARATOR));

                        if (str_contains($basePath, '/vendor/orchestra/')) {
                            $basePath = Str::before($basePath, '/vendor/orchestra/').'/';
                        }

                        $action = str_replace($basePath, '', $viewPath);
                    }

                    $uri = str_replace('.blade.php', '', $uri);

                    $uri = collect(explode('/', $uri))
                        ->map(function (string $currentSegment) {
                            if (Str::startsWith($currentSegment, '[...')) {
                                $formattedSegment = '[...';
                            } elseif (Str::startsWith($currentSegment, '[')) {
                                $formattedSegment = '[';
                            } else {
                                return $currentSegment;
                            }

                            $lastPartOfSegment = str($currentSegment)->whenContains(
                                '.',
                                fn (Stringable $string) => $string->afterLast('.'),
                                fn (Stringable $string) => $string->afterLast('['),
                            );

                            return $formattedSegment.match (true) {
                                $lastPartOfSegment->contains(':') => $lastPartOfSegment->beforeLast(':')->camel()
                                    .':'.$lastPartOfSegment->afterLast(':'),
                                $lastPartOfSegment->contains('-') => $lastPartOfSegment->beforeLast('-')->camel()
                                    .':'.$lastPartOfSegment->afterLast('-'),
                                default => $lastPartOfSegment->camel(),
                            };
                        })
                        ->implode('/');

                    $uri = preg_replace_callback('/\[(.*?)\]/', function (array $matches) {
                        return '{'.Str::camel($matches[1]).'}';
                    }, $uri);

                    $uri = str_replace(['/index', '/index/'], ['', '/'], $uri);

                    return [
                        'method' => 'GET',
                        'domain' => $domain,
                        'uri' => $uri === '' ? '/' : $uri,
                        'name' => $this->routeName($mountPath, $viewPath),
                        'action' => $action,
                        'view' => $action,
                    ];
                });
        })->flatten(1)
            ->unique(fn (array $route) => $route['uri'])
            ->values();
    }

    /**
     * Get the route name for the given mount path and view path.
     */
    protected function routeName(string $mountPath, string $viewPath): ?string
    {
        return collect($this->laravel->make(FolioRoutes::class)->routes())->search(function (array $route) use ($mountPath, $viewPath) {
            ['mountPath' => $routeRelativeMountPath, 'path' => $routeRelativeViewPath] = $route;

            return $routeRelativeMountPath === Project::relativePathOf($mountPath)
                && $routeRelativeViewPath === Project::relativePathOf($viewPath);
        }) ?: null;
    }

    /**
     * Convert the given routes to JSON.
     *
     * @param  \Illuminate\Support\Collection<string, string>  $routes
     */
    protected function asJson($routes): string
    {
        return $routes->values()->toJson();
    }

    /**
     * Convert the given routes to regular CLI output.
     *
     * @param  \Illuminate\Support\Collection<string, string>  $routes
     * @return array<string, string>
     */
    protected function forCli($routes): array
    {
        return parent::forCli(collect($routes)->map(fn ($route) => array_merge([
            'middleware' => '',
        ], $route)));
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @param  array<string, string>  $routes
     * @return array<string, string>
     */
    protected function toDisplayableFormat(array $routes): array
    {
        $routes = collect($routes)->filter($this->filterRoute(...))->values()->all();

        if (($sort = $this->option('sort')) !== null) {
            $routes = $this->sortRoutes($sort, $routes);
        } else {
            $routes = $this->sortRoutes('uri', $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return $this->pluckColumns($routes);
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array<string, string>  $route
     * @return array<string, string>|null
     */
    protected function filterRoute(array $route): ?array
    {
        if ($this->option('name') && ! Str::contains((string) $route['name'], $this->option('name'))) {
            return null;
        }

        if (($this->option('path') && ! Str::contains($route['uri'], $this->option('path')))) {
            return null;
        }

        if (($this->option('domain') && ! Str::contains((string) $route['domain'], $this->option('domain')))) {
            return null;
        }

        if ($this->option('except-path')) {
            foreach (explode(',', $this->option('except-path')) as $path) {
                if (str_contains($route['uri'], $path)) {
                    return null;
                }
            }
        }

        return $route;
    }

    /**
     * Sort the routes by a given element.
     *
     * @param  string  $sort
     * @return array<string, string>
     */
    protected function sortRoutes($sort, array $routes): array
    {
        if ($sort !== 'uri') {
            return parent::sortRoutes($sort, $routes);
        }

        usort($routes, function (array $first, array $second) use ($sort) {
            $first = Str::of($first[$sort]);
            $second = Str::of($second[$sort]);

            if (
                $first->beforeLast('/') === $second->beforeLast('/')
                && $first->afterLast('/')->startsWith('{') && ! $second->afterLast('/')->startsWith('{')
            ) {
                return -1;
            }

            if ($second->startsWith($first)) {
                return $first->explode('/')->count() > $second->explode('/')->count() ? 1 : -1;
            }

            return $first->value() <=> $second->value();
        });

        return $routes;
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, int|string|null>>
     */
    protected function getOptions(): array
    {
        return [
            ['json', null, InputOption::VALUE_NONE, 'Output the route list as JSON'],
            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name'],
            ['domain', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by domain'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Only show routes matching the given path pattern'],
            ['except-path', null, InputOption::VALUE_OPTIONAL, 'Do not display the routes matching the given path pattern'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (domain, name, uri, view) to sort by', 'uri'],
        ];
    }
}
