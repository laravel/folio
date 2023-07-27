<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Folio;

class MatchDirectoryIndexViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            $state->currentUriSegmentIsDirectory() &&
            file_exists($path = $state->currentUriSegmentDirectory().'/index'.Folio::extension())
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
