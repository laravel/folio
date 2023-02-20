<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class ContinueIteratingIfDirectoryWithFurtherSegments
{
    public function __invoke(State $state, Closure $next): mixed
    {
        return is_dir($state->currentDirectory().'/'.$state->currentUriSegment())
                    ? new ContinueIterating($state)
                    : $next($state);
    }
}
