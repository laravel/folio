<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Support\LivewireComponents;

class MatchLiteralViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            ($path = LivewireComponents::findView($state->currentDirectory().'/'.$state->currentUriSegment().'.blade.php'))
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
