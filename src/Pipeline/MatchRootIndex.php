<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;

class MatchRootIndex
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            return file_exists($indexView = $state->mountPath.'/index.blade.php')
                    ? View::file($indexView, $state->data)
                    : new StopIterating;
        }

        return $next($state);
    }
}
