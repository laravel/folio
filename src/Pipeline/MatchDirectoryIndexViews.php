<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Router;

class MatchDirectoryIndexViews
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! is_dir($state->currentDirectory().'/'.$state->currentUriSegment())) {
            return $next($state);
        }

        // Index view match (must also be last segment)...
        if ($state->onLastUriSegment() &&
            file_exists($path = $state->currentDirectory().'/'.$state->currentUriSegment().'/index.blade.php')) {
            Router::ensureNoDirectoryTraversal($path, $state->mountPath);

            return View::file($path, $state->data);
        }

        return $next($state);
    }
}
