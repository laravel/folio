<?php

namespace Laravel\Folio\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Folio\Folio;
use Laravel\Folio\MountPath;
use Laravel\Folio\Support\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;

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
        $mountPath = $this->mountPath();

        return $mountPath.'/'.preg_replace_callback(
            '/(?:\[.*?\])|(\w+)/',
            fn (array $matches) => empty($matches[1]) ? $matches[0] : Str::lower($matches[1]),
            Str::finish($this->argument('name'), '.blade.php')
        );
    }

    /**
     * Resolve the mounted path where the page should be created.
     */
    protected function mountPath(): string
    {
        $mountPaths = collect(Folio::mountPaths())
            ->unique(fn (MountPath $mountPath) => $this->normalizePath($mountPath->path))
            ->values();

        $mount = $this->option('mount');

        if ($mountPaths->isEmpty()) {
            if ($mount !== null) {
                throw new InvalidArgumentException('The mount option may only be used when a Folio path is configured.');
            }

            return resource_path('views/pages');
        }

        if ($mount !== null) {
            $matches = $mountPaths->filter(fn (MountPath $mountPath) => in_array(
                $this->normalizePath($mount),
                [$this->normalizePath($mountPath->path), $this->normalizePath(Project::relativePathOf($mountPath->path))],
                true,
            ));

            if ($matches->count() !== 1) {
                throw new InvalidArgumentException(sprintf(
                    'The mount [%s] is not one of the configured Folio paths: %s.',
                    $mount,
                    $mountPaths->map(fn (MountPath $mountPath) => Project::relativePathOf($mountPath->path))->join(', '),
                ));
            }

            return $matches->first()->path;
        }

        if ($mountPaths->count() === 1 || ! $this->input->isInteractive()) {
            return $mountPaths->first()->path;
        }

        return select(
            label: 'Which Folio path should the page be created in?',
            options: $mountPaths->mapWithKeys(fn (MountPath $mountPath) => [
                $mountPath->path => $this->mountPathLabel($mountPath),
            ]),
            default: $mountPaths->first()->path,
        );
    }

    /**
     * Get the display label for a mounted path.
     */
    protected function mountPathLabel(MountPath $mountPath): string
    {
        $label = Project::relativePathOf($mountPath->path).' (URI: '.$mountPath->baseUri;

        if ($mountPath->domain) {
            $label .= ', Domain: '.$mountPath->domain;
        }

        return $label.')';
    }

    /**
     * Normalize a path for comparison.
     */
    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
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
            ['mount', null, InputOption::VALUE_REQUIRED, 'The configured Folio path where the page should be created'],
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
