<?php

namespace Laravel\Folio;

use Illuminate\Support\ServiceProvider;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register the package's services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FolioManager::class);
        $this->app->singleton(InlineMiddlewareInterceptor::class);
    }

    /**
     * Bootstrap the package's services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
