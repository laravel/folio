<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class MatchDirectoryIndexViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            $state->currentUriSegmentIsDirectory() &&
            file_exists($path = $state->currentUriSegmentDirectory().'/index.blade.php')
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
