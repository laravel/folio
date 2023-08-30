<?php

namespace Laravel\Folio\Pipeline;

use Illuminate\Support\Collection;
use Laravel\Folio\InlineMetadataInterceptor;

class MatchedView
{
    /**
     * Create a new matched view instance.
     */
    public function __construct(
        public string $path,
        public array $data,
        public ?string $mountPath = null,
    ) {
        $this->path = realpath($path) ?: $path;
    }

    /**
     * Set the mount path on the matched view, returning a new instance.
     */
    public function withMountPath(string $mountPath): MatchedView
    {
        return new static(mountPath: $mountPath, path: $this->path, data: $this->data);
    }

    /**
     * Get the matched view's name, if any.
     */
    public function name(): ?string
    {
        return app(InlineMetadataInterceptor::class)->intercept($this)->name;
    }

    /**
     * Get the matched view's inline middleware.
     */
    public function inlineMiddleware(): Collection
    {
        return app(InlineMetadataInterceptor::class)->intercept($this)->middleware;
    }

    /**
     * Determine if the matched view resolves soft deleted model bindings.
     */
    public function allowsTrashedBindings(): bool
    {
        return app(InlineMetadataInterceptor::class)->intercept($this)->withTrashed;
    }

    /**
     * Get the matched view's render callback.
     */
    public function renderUsing(): callable
    {
        return app(InlineMetadataInterceptor::class)->intercept($this)->renderUsing ?? fn ($view) => $view;
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

        return $this->withData($data);
    }

    /**
     * Create a new matched view instance with the given data.
     */
    public function withData(array $data): MatchedView
    {
        return new static($this->path, $data, $this->mountPath);
    }
}
