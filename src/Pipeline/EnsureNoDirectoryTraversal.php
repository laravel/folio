<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;
use Laravel\Folio\Exceptions\PossibleDirectoryTraversal;

class EnsureNoDirectoryTraversal
{
    /**
     * Invoke the routing pipeline handler.
     *
     * @throws \Laravel\Folio\Exceptions\PossibleDirectoryTraversal
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! ($view = $next($state)) instanceof MatchedView) {
            return $view;
        }

        if (! Str::of(realpath($view->path))->startsWith($state->mountPath.DIRECTORY_SEPARATOR)) {
            throw new PossibleDirectoryTraversal;
        }

        return $view;
    }
}
