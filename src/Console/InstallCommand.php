<?php

namespace Laravel\Folio\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'folio:install')]
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
        $this->components->info('Publishing Folio Service Provider.');

        $this->callSilent('vendor:publish', ['--tag' => 'folio-provider']);

        $this->registerFolioServiceProvider();

        $this->ensurePagesDirectoryExists();

        $this->components->info('Folio scaffolding installed successfully.');
    }

    /**
     * Ensure the pages directory exists.
     */
    protected function ensurePagesDirectoryExists(): void
    {
        if (! is_dir($directory = resource_path('views/pages'))) {
            File::ensureDirectoryExists($directory);

            File::put($directory.'/.gitkeep', '');
        }
    }

    /**
     * Register the Folio service provider in the application configuration file.
     */
    protected function registerFolioServiceProvider(): void
    {
        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile') &&
            ServiceProvider::addProviderToBootstrapFile(\App\Providers\FolioServiceProvider::class)) { // @phpstan-ignore-line
            return;
        }

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
