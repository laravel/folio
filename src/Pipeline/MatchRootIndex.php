<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;

class MatchRootIndex
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            return file_exists($path = $state->mountPath.'/index.blade.php')
                    ? View::file($path, $state->data)
                    : new StopIterating;
        }

        return $next($state);
    }
}
