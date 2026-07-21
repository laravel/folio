<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Support\LivewireComponents;

class MatchRootIndex
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            return ($path = LivewireComponents::findView($state->mountPath.'/index.blade.php'))
                    ? new MatchedView($path, $state->data)
                    : new StopIterating;
        }

        return $next($state);
    }
}
