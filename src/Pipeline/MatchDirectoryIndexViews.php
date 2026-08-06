<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Support\LivewireComponents;

class MatchDirectoryIndexViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            $state->currentUriSegmentIsDirectory() &&
            ($path = LivewireComponents::findView($state->currentUriSegmentDirectory().'/index.blade.php'))
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
