<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Functions;

class MatchDirectoryIndexViews
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! is_dir($state->absoluteDirectory().'/'.$state->currentSegment())) {
            return $next($state);
        }

        // Index view match (must also be last segment)...
        if ($state->lastSegment() &&
            file_exists($indexView = $state->absoluteDirectory().'/'.$state->currentSegment().'/index.blade.php')) {
            Functions::ensureNoDirectoryTraversal($indexView, $state->mountPath);

            return View::file($indexView, $state->data);
        }

        return $next($state);
    }
}
