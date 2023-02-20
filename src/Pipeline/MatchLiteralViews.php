<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Functions;

class MatchLiteralViews
{
    public function __invoke(State $state, Closure $next): mixed
    {
        // Literal view match... must also be last segment...
        if ($state->lastSegment() && file_exists($possibleView = $state->absoluteDirectory().'/'.$state->currentSegment().'.blade.php')) {
            Functions::ensureNoDirectoryTraversal($possibleView, $state->mountPath);

            return View::file($possibleView, $state->data);
        }

        return $next($state);
    }
}
