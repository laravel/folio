<?php

namespace Laravel\Folio;

use Laravel\Folio\Pipeline\MatchedView;

class InlineMetadataInterceptor
{
    /**
     * Indicates if the interceptor is listening for metadata.
     */
    protected bool $listening = false;

    /**
     * The cached path to metadata mappings.
     */
    protected array $cache = [];

    /**
     * Intercept the inline metadata for the given matched view.
     */
    public function intercept(MatchedView $matchedView): Metadata
    {
        if (array_key_exists($matchedView->path, $this->cache)) {
            return $this->cache[$matchedView->path];
        }

        try {
            $this->listen(function () use ($matchedView) {
                ob_start();

                [$__path, $__variables] = [
                    $matchedView->path,
                    $matchedView->data,
                ];

                (static function () use ($__path, $__variables) {
                    extract($__variables);

                    require $__path;
                })();
            });
        } finally {
            ob_get_clean();

            $metadata = tap(Metadata::instance(), fn () => Metadata::flush());
        }

        return $this->cache[$matchedView->path] = $metadata;
    }

    /**
     * Execute the callback while listening for metadata.
     */
    public function listen(callable $callback): void
    {
        $this->listening = true;

        try {
            $callback();
        } finally {
            $this->listening = false;
        }
    }

    /**
     * Execute the callback if the interceptor is listening for metadata.
     */
    public function whenListening(callable $callback): void
    {
        if ($this->listening) {
            $callback();
        }
    }
}
