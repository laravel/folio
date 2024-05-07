<?php

namespace Laravel\Folio\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Laravel\Folio\Folio;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'folio:page', aliases: ['make:folio'])]
class MakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'folio:page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Folio page';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Page';

    /**
     * The console command name aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['make:folio'];

    /**
     * Get the destination view path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $mountPath = Folio::paths()[0] ?? resource_path('views/pages');

        return $mountPath.'/'.preg_replace_callback(
            '/(?:\[.*?\])|(\w+)/',
            fn (array $matches) => empty($matches[1]) ? $matches[0] : Str::lower($matches[1]),
            Str::finish($this->argument('name'), '.blade.php')
        );
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/folio-page.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/folio-page.stub';
    }

    /**
     * Get the console command arguments.
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the Folio page even if the page already exists'],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => class_exists(Application::class) && version_compare(Application::VERSION, '10.17.0', '>=')
                ? ['What should the page be named?', 'E.g. users/index, users/[User]']
                : 'What should the page be named?',
        ];
    }
}
