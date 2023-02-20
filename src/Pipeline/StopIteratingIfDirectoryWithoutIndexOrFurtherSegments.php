<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class StopIteratingIfDirectoryWithoutIndexOrFurtherSegments
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! is_dir($state->currentDirectory().'/'.$state->currentUriSegment())) {
            return $next($state);
        }

        return $state->onLastUriSegment()
                ? new StopIterating
                : $next($state);
    }
}
