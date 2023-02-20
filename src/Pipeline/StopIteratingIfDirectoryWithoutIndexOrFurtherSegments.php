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
        return $state->onLastUriSegment() && $state->currentUriSegmentIsDirectory()
                ? new StopIterating
                : $next($state);
    }
}
