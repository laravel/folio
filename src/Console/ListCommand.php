<?php

namespace Laravel\Folio\Console;

use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Folio\FolioManager;
use Laravel\Folio\MountPath;
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
    protected $headers = ['Domain', 'Method', 'URI', 'View'];

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
        return $route['view'];
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

            $domain = $mountPath->domain;
            $mountPath = str_replace(DIRECTORY_SEPARATOR, '/', $mountPath->path);

            $path = '/'.ltrim($mountPath, '/');

            return collect($views)
                ->map(function (SplFileInfo $view) use ($domain, $mountPath) {
                    $viewPath = str_replace(DIRECTORY_SEPARATOR, '/', $view->getRealPath());
                    $uri = str_replace($mountPath, '', $viewPath);

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
                            } elseif (Str::startsWith($currentSegment, '[.')) {
                                $formattedSegment = '[';
                            } else {
                                return $currentSegment;
                            }

                            $lastPartOfSegment = str($currentSegment)->afterLast('.');

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
                        'name' => '',
                        'action' => $action,
                        'view' => $action,
                    ];
                });
        })->flatten(1)
            ->unique(fn (array $route) => $route['uri'])
            ->values();
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array<string, string>  $route
     * @return array<string, string>|null
     */
    protected function filterRoute(array $route): ?array
    {
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
            ['domain', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by domain'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Only show routes matching the given path pattern'],
            ['except-path', null, InputOption::VALUE_OPTIONAL, 'Do not display the routes matching the given path pattern'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (uri, view) to sort by', 'uri'],
        ];
    }
}
