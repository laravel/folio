<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class StopIteratingIfDirectoryWithoutIndexOrFurtherSegments
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! $state->currentUriSegmentIsDirectory()) {
            return $next($state);
        }

        return $state->onLastUriSegment()
                ? new StopIterating
                : $next($state);
    }
}
