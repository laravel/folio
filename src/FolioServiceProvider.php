<?php

namespace Laravel\Folio;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\UrlGenerator;
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

            with($this->app->make('events'), function (Dispatcher $events) {
                $events->listen(CommandStarting::class, function (CommandStarting $event) {
                    if ($event->command === 'route:clear') {
                        $this->app->make(FolioRoutes::class)->flush();
                    }
                });

                $events->listen(CommandFinished::class, function (CommandStarting $event) {
                    if ($event->command === 'route:cache') {
                        $this->app->make(FolioRoutes::class)->persist();
                    }
                });
            });
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
     * Register the URL generator decorator.
     */
    protected function registerUrlGenerator(): void
    {
        $this->app->extend('url', function (UrlGenerator $urlGenerator) {
            return new UrlGeneratorDecorator($urlGenerator, $this->app->make(FolioRoutes::class));
        });
    }

    /**
     * Register the package's terminating callback.
     */
    protected function registerTerminationCallback(): void
    {
        $this->app->terminating(fn (FolioManager $manager) => $manager->terminate());
    }
}
