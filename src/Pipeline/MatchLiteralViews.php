<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Drivers\FolioDriverContract;

class MatchLiteralViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        return $state->onLastUriSegment() &&
            file_exists($path = $state->currentDirectory().'/'.$state->currentUriSegment().app(FolioDriverContract::class)->extension())
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
