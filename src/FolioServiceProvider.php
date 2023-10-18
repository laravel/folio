<?php

namespace Laravel\Folio;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register the package's services.
     */
    public function register(): void
    {
        $this->app->singleton(FolioManager::class);
        $this->app->singleton(InlineMetadataInterceptor::class);
        $this->app->singleton(FolioRoutes::class);

        $this->app->when(FolioRoutes::class)
            ->needs('$cachedFolioRoutesPath')
            ->give(fn () => dirname($this->app->getCachedRoutesPath()).DIRECTORY_SEPARATOR.'folio-routes.php');
    }

    /**
     * Bootstrap the package's services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerPublishing();
        $this->registerUrlGenerator();
        $this->registerTerminationCallback();
        $this->cacheFolioRoutesOnRouteCache();
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\ListCommand::class,
                Console\MakeCommand::class,
            ]);
        }
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/FolioServiceProvider.stub' => app_path('Providers/FolioServiceProvider.php'),
            ], 'folio-provider');
        }
    }

    /**
     * Register the URL generator route resolver.
     */
    protected function registerUrlGenerator(): void
    {
        $this->callAfterResolving(UrlGenerator::class, function ($url) {
            $url->resolveMissingNamedRoutesUsing(function ($name, $parameters, $absolute) {
                $routes = app(FolioRoutes::class);

                if ($routes->has($name)) {
                    return $routes->get($name, $parameters, $absolute);
                }
            });
        });
    }

    /**
     * Register the package's terminating callback.
     */
    protected function registerTerminationCallback(): void
    {
        $this->app->terminating(fn (FolioManager $manager) => $manager->terminate());
    }

    /**
     * Cache Folio's routes when the route:cache and route:clear commands are run.
     */
    protected function cacheFolioRoutesOnRouteCache(): void
    {
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->command === 'route:cache') {
                $this->app->make(FolioRoutes::class)->persist();
            }
        });

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command === 'route:clear') {
                $this->app->make(FolioRoutes::class)->flush();
            }
        });
    }
}
