<?php

namespace Laravel\Folio\Support;

use Illuminate\Support\Str;

class Project
{
    /**
     * Get the project's base path.
     */
    public static function basePath(): string
    {
        $basePath = base_path();

        $basePath = str_replace(DIRECTORY_SEPARATOR, '/', $basePath);

        if (str_contains($basePath, '/vendor/orchestra/')) {
            $basePath = Str::before($basePath, '/vendor/orchestra/').'/';
        }

        return $basePath;
    }

    /**
     * Get the relative path to the given path.
     */
    public static function relativePathOf(string $path): string
    {
        $basePath = static::basePath();

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        return substr($path, mb_strlen($basePath));
    }
}
