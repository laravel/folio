<?php

namespace Laravel\Folio\Support;

use Illuminate\Support\Str;

class Project
{
    /**
     * Get the path to the given file relative to the project's base path.
     */
    public static function relativePathOf(string $path): string
    {
        return substr(str_replace(DIRECTORY_SEPARATOR, '/', $path), mb_strlen(static::basePath()));
    }

    /**
     * Get the project's base path.
     */
    public static function basePath(): string
    {
        $basePath = base_path();

        $basePath = str_replace(DIRECTORY_SEPARATOR, '/', $basePath);

        return str_contains($basePath, '/vendor/orchestra/')
            ? Str::before($basePath, '/vendor/orchestra/').'/'
            : $basePath;
    }
}
