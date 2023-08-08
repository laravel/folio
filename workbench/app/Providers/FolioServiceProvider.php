<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::view('/', 'welcome');

        View::addLocation(__DIR__.'/../../../tests/Feature/resources/views');

        Folio::path(__DIR__.'/../../../tests/Feature/resources/views/pages')->middleware([
            '*' => [
                //
            ],
        ]);
    }
}
