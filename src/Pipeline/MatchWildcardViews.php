<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Folio\Functions;

class MatchWildcardViews
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($state->lastSegment() &&
            $possibleView = Functions::findWildcardView($state->absoluteDirectory())) {
            Functions::ensureNoDirectoryTraversal(
                $state->absoluteDirectory().'/'.$possibleView, $state->mountPath
            );

            $state = $state->withData(
                Str::of($possibleView)
                    ->before('.blade.php')
                    ->match('/\[(.*)\]/')->value(),
                $state->currentSegment(),
            );

            return View::file($state->absoluteDirectory().'/'.$possibleView, $state->data);
        }

        return $next($state);
    }
}
