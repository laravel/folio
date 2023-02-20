<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Router;

class MatchDirectoryIndexViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! $state->currentUriSegmentIsDirectory()) {
            return $next($state);
        }

        // Index view match (must also be last segment)...
        if ($state->onLastUriSegment() &&
            file_exists($path = $state->currentUriSegmentDirectory().'/index.blade.php')) {
            Router::ensureNoDirectoryTraversal($path, $state->mountPath);

            return new MatchedView($path, $state->data);
        }

        return $next($state);
    }
}
