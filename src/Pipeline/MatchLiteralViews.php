<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class MatchLiteralViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            file_exists($path = $state->currentDirectory().'/'.$state->currentUriSegment().'.blade.php')
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
