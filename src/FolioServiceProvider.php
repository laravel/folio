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
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ListCommand::class,
                Console\InstallCommand::class,
            ]);
        }
    }

    /**
     * Register the package's publishable resources.
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/FolioServiceProvider.stub' => app_path('Providers/FolioServiceProvider.php'),
            ], 'folio-provider');
        }
    }
}
