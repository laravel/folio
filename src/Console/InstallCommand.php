<?php

namespace Laravel\Folio\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folio:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Folio resources';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->comment('Publishing Folio Service Provider...');

        $this->callSilent('vendor:publish', ['--tag' => 'folio-provider']);

        $this->registerFolioServiceProvider();

        $this->info('Folio scaffolding installed successfully.');
    }

    /**
     * Register the Folio service provider in the application configuration file.
     */
    protected function registerFolioServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\FolioServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\FolioServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/FolioServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/FolioServiceProvider.php'))
        ));
    }
}
