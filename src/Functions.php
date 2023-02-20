<?php

namespace Laravel\Folio;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Functions
{
    public static function findWildcardDirectory(string $directory): ?string
    {
        return static::findDirectoryWith($directory, '[', ']');
    }

    public static function findDirectoryWith(string $directory, string $startsWith, string $endsWith): ?string
    {
        $directories = (new Filesystem)->directories($directory);

        return collect($directories)->first(function ($directory) use ($startsWith, $endsWith) {
            $directory = Str::of($directory)->basename();

            return $directory->startsWith($startsWith) &&
                   $directory->endsWith($endsWith);
        });
    }

    public static function findWildcardMultiSegmentView(string $directory): ?string
    {
        return static::findViewWith($directory, '[...', ']');
    }

    public static function findWildcardView(string $directory): ?string
    {
        return static::findViewWith($directory, '[', ']');
    }

    protected static function findViewWith(string $directory, $startsWith, $endsWith): ?string
    {
        $files = (new Filesystem)->files($directory);

        return collect($files)->first(function ($file) use ($startsWith, $endsWith) {
            $filename = Str::of($file->getFilename());

            if (! $filename->endsWith('.blade.php')) {
                return;
            }

            $filename = $filename->before('.blade.php');

            return $filename->startsWith($startsWith) &&
                   $filename->endsWith($endsWith);
        })?->getFilename();
    }

    public static function ensureNoDirectoryTraversal(string $path, string $mountPath): void
    {
        if (! Str::of(realpath($path))->startsWith($mountPath.'/')) {
            abort(404);
        }
    }
}
