<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;
use function Orchestra\Testbench\workbench_path;

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

        View::addLocation(workbench_path('/resources/views'));

        Folio::path(workbench_path('/resources/views/pages'))->middleware([
            '*' => [
                //
            ],
        ]);
    }
}
