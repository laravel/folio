<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class StopIteratingIfDirectoryWithoutIndexOrFurtherSegments
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! is_dir($state->absoluteDirectory().'/'.$state->currentSegment())) {
            return $next($state);
        }

        // Is a directory, but no index view and no further segments available...
        if ($state->lastSegment()) {
            return new StopIterating;
        }

        return $next($state);
    }
}
