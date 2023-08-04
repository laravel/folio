<?php

namespace Laravel\Folio;

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
    }

    /**
     * Bootstrap the package's services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerPublishing();
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
     * Register the package's terminating callback.
     */
    protected function registerTerminationCallback(): void
    {
        $this->app->terminating(fn (FolioManager $manager) => $manager->terminate());
    }
}
