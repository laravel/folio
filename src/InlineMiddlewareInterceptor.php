<?php

namespace Laravel\Folio;

use Illuminate\Support\Collection;
use Laravel\Folio\Exceptions\MiddlewareIntercepted;
use Laravel\Folio\Pipeline\MatchedView;

class InlineMiddlewareInterceptor
{
    /**
     * Indicates if the interceptor is listening for definitions.
     */
    protected bool $listening = false;

    /**
     * The cached path to middleware mappings.
     */
    protected array $cache = [];

    /**
     * Intercept the inline middleware for the given matched view.
     */
    public function intercept(MatchedView $matchedView): Collection
    {
        if (array_key_exists($matchedView->path, $this->cache)) {
            return collect($this->cache[$matchedView->path]);
        }

        try {
            $this->listen(function () use ($matchedView) {
                ob_start();

                $__path = $matchedView->path;

                (static function () use ($__path) {
                    require $__path;
                })();
            });
        } catch (MiddlewareIntercepted $e) {
            $this->cache[$matchedView->path] = $e->middleware;

            return collect($e->middleware);
        } finally {
            ob_get_clean();
        }

        $this->cache[$matchedView->path] = [];

        return new Collection;
    }

    /**
     * Execute the callback while listening for middleware.
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
     * Determine if the interceptor is listening for middleware.
     */
    public function listening(): bool
    {
        return $this->listening;
    }
}
