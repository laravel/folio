<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class SetMountPathOnMatchedView
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! ($view = $next($state)) instanceof MatchedView) {
            return $view;
        }

        return $view->withMountPath($state->mountPath);
    }
}
