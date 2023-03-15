<?php

namespace Laravel\Folio\Pipeline;

use Illuminate\Support\Collection;
use Laravel\Folio\InlineMiddlewareInterceptor;

class MatchedView
{
    /**
     * The full path to the matched view file.
     */
    public string $path;

    /**
     * The mount path that the view was located in.
     */
    public ?string $mountPath;

    /**
     * The data that should be given to the view.
     */
    public array $data;

    /**
     * Create a new matched view instance.
     */
    public function __construct(string $path, array $data, ?string $mountPath = null)
    {
        $this->path = realpath($path) ?: $path;
        $this->data = $data;
        $this->mountPath = $mountPath;
    }

    /**
     * Set the mount path on the matched view, returning a new instance.
     */
    public function withMountPath(string $mountPath): MatchedView
    {
        return new static(mountPath: $mountPath, path: $this->path, data: $this->data);
    }

    /**
     * Get the matched view's inline middleware.
     */
    public function inlineMiddleware(): Collection
    {
        return app(InlineMiddlewareInterceptor::class)->intercept($this);
    }

    /**
     * Get the path to the matched view relative to the mount path.
     */
    public function relativePath(): string
    {
        $path = str_replace($this->mountPath, '', $this->path);

        return '/'.trim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '/');
    }

    /**
     * Replace the given piece of a data with a new piece of data.
     */
    public function replace(string $keyBeingReplaced, string $newKey, mixed $value): MatchedView
    {
        $data = $this->data;

        unset($data[$keyBeingReplaced]);

        $data[$newKey] = $value;

        return new static($this->path, $data);
    }

    /**
     * Create a new matched view instance with the given data.
     */
    public function withData(array $data): MatchedView
    {
        return new static($this->path, $data);
    }
}
