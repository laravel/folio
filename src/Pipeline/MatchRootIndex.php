<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class MatchRootIndex
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            return file_exists($path = $state->mountPath.'/index.blade.php')
                    ? new MatchedView($path, $state->data)
                    : new StopIterating;
        }

        return $next($state);
    }
}
