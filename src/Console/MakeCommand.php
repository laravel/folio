<?php

namespace Laravel\Folio\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Folio\Folio;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'folio:make')]
class MakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'folio:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Folio route';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Folio route';

    /**
     * Get the destination view path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $mountPath = Folio::paths()[0] ?? resource_path('views/pages');

        $name = $this->argument('name');

        if (! Str::endsWith($name, '.blade.php')) {
            $name .= '.blade.php';
        }

        return $mountPath.'/'.preg_replace_callback('/(?:\[.*?\])|(\w+)/', function (array $matches) {
            return empty($matches[1]) ? $matches[0] : Str::lower($matches[1]);
        }, $name);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/folio-route.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/folio-route.stub';
    }

    /**
     * Get the console command arguments.
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the Folio route even if the route already exists'],
        ];
    }
}
