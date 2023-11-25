<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Drivers\FolioDriverContract;

class MatchRootIndex
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            return file_exists($path = $state->mountPath.'/index'.app(FolioDriverContract::class)->extension())
                    ? new MatchedView($path, $state->data)
                    : new StopIterating;
        }

        return $next($state);
    }
}
