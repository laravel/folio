<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Folio\Functions;

class MatchWildcardViewsThatCaptureMultipleSegments
{
    public function __invoke(State $state, Closure $next): mixed
    {
        $possibleView = Functions::findWildcardMultiSegmentView($state->absoluteDirectory());

        if ($possibleView) {
            $state = $state->withData(
                Str::of($possibleView)
                    ->before('.blade.php')
                    ->match('/\[\.\.\.(.*)\]/')->value(),
                array_slice($state->segments, $state->currentIndex, $state->segmentCount() - 1)
            );

            Functions::ensureNoDirectoryTraversal(
                $state->absoluteDirectory().'/'.$possibleView, $state->mountPath
            );

            return View::file($state->absoluteDirectory().'/'.$possibleView, $state->data);
        }

        return $next($state);
    }
}
