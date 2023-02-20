<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class ContinueIteratingIfDirectoryWithFurtherSegments
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (is_dir($state->absoluteDirectory().'/'.$state->currentSegment())) {
            return new ContinueIterating($state);
        }

        return $next($state);
    }
}
