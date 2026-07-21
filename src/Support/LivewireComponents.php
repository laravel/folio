<?php

namespace Laravel\Folio\Support;

class LivewireComponents
{
    /**
     * Find a view at the given path, including Livewire's optional emoji prefix.
     */
    public static function findView(string $path): ?string
    {
        if (file_exists($path)) {
            return $path;
        }

        foreach (["⚡", "⚡\u{FE0E}", "⚡\u{FE0F}"] as $prefix) {
            $prefixedPath = dirname($path).DIRECTORY_SEPARATOR.$prefix.basename($path);

            if (file_exists($prefixedPath) && static::name($prefixedPath) !== null) {
                return $prefixedPath;
            }
        }

        return null;
    }

    /**
     * Get the Livewire component name for the given view path.
     */
    public static function name(string $path): ?string
    {
        if (! app()->bound('livewire') || ! static::hasValidSingleFileComponentSource($path)) {
            return null;
        }

        foreach (config('livewire.component_namespaces', []) as $namespace => $location) {
            if (($name = static::nameWithinLocation($path, $location)) !== null) {
                $component = $namespace.'::'.$name;

                if (app('livewire')->exists($component)) {
                    return $component;
                }
            }
        }

        foreach (config('livewire.component_locations', []) as $location) {
            if (($name = static::nameWithinLocation($path, $location)) !== null && app('livewire')->exists($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Strip Livewire's optional emoji prefix from path segments.
     */
    public static function stripEmojiPrefix(string $path): string
    {
        return (string) preg_replace('/(^|\/)\x{26A1}[\x{FE0E}\x{FE0F}]?/u', '$1', $path);
    }

    /**
     * Determine if the view contains a Livewire single-file component.
     */
    protected static function hasValidSingleFileComponentSource(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        return $contents !== false && preg_match('/\<\?php.*new\s+.*class/s', $contents) === 1;
    }

    /**
     * Get the component name for a view within the given location.
     */
    protected static function nameWithinLocation(string $path, mixed $location): ?string
    {
        if (! is_string($location) || ! $location = realpath($location)) {
            return null;
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path) ?: $path);
        $location = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $location), '/');

        if (! str_starts_with($path, $location.'/') || ! str_ends_with($path, '.blade.php')) {
            return null;
        }

        return str_replace('/', '.', static::stripEmojiPrefix(
            substr($path, strlen($location) + 1, -strlen('.blade.php'))
        ));
    }
}
