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
     * Intercept the inline middleware for the given matched view.
     */
    public function intercept(MatchedView $matchedView): Collection
    {
        try {
            $this->listen(function () use ($matchedView) {
                ob_start();

                $__path = $matchedView->path;

                (static function () use ($__path) {
                    require $__path;
                })();
            });
        } catch (MiddlewareIntercepted $e) {
            return collect($e->middleware);
        } finally {
            ob_get_clean();
        }

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
