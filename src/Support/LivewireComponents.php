<?php

namespace Laravel\Folio\Support;

use Laravel\Folio\FolioManager;

class LivewireComponents
{
    /**
     * Register a resolver for component names that represent Folio wildcard files.
     */
    public static function registerMissingComponentResolver(mixed $livewire): void
    {
        $livewire->resolveMissingComponent(function ($name) {
            if (! is_string($name) || ! $path = static::pathFromFolioComponentName($name)) {
                return null;
            }

            return app('livewire.compiler')->compile($path);
        });
    }

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

                return static::registerFolioComponent($path);
            }
        }

        foreach (config('livewire.component_locations', []) as $location) {
            if (($name = static::nameWithinLocation($path, $location)) !== null) {
                if (app('livewire')->exists($name)) {
                    return $name;
                }

                return static::registerFolioComponent($path);
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

    /**
     * Register a Folio view under a stable Livewire component name.
     */
    protected static function registerFolioComponent(string $path): ?string
    {
        $path = realpath($path);

        foreach (app(FolioManager::class)->mountPaths() as $index => $mountPath) {
            $mountPath = realpath($mountPath->path);

            if (! $path || ! $mountPath || ! str_starts_with($path, $mountPath.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relativePath = substr($path, strlen($mountPath) + 1);
            $component = 'folio-'.$index.'-'.static::base64UrlEncode($relativePath);

            app('livewire')->addComponent($component, viewPath: $path);

            return $component;
        }

        return null;
    }

    /**
     * Resolve a Folio component name back to its configured Livewire view.
     */
    protected static function pathFromFolioComponentName(string $name): ?string
    {
        if (preg_match('/^folio-(\d+)-([A-Za-z0-9_-]+)$/', $name, $matches) !== 1) {
            return null;
        }

        $mountPath = app(FolioManager::class)->mountPaths()[(int) $matches[1]] ?? null;
        $relativePath = static::base64UrlDecode($matches[2]);

        if (! $mountPath || $relativePath === null || str_contains($relativePath, "\0")) {
            return null;
        }

        $mountPath = realpath($mountPath->path);
        $path = $mountPath ? realpath($mountPath.DIRECTORY_SEPARATOR.$relativePath) : false;

        if (! $path || ! str_starts_with($path, $mountPath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        foreach ([...array_values(config('livewire.component_namespaces', [])), ...config('livewire.component_locations', [])] as $location) {
            if (static::nameWithinLocation($path, $location) !== null && static::hasValidSingleFileComponentSource($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Encode a relative path for use in a Livewire component name.
     */
    protected static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Decode a relative path from a Livewire component name.
     */
    protected static function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
