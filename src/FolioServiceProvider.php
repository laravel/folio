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
        //
    }
}
