<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;
use Laravel\Folio\Functions;

class MatchWildcardDirectories
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! $state->lastSegment() &&
            $possibleDirectory = Functions::findWildcardDirectory($state->absoluteDirectory())) {
            $state = $state->withData(
                Str::of($possibleDirectory)
                    ->basename()
                    ->match('/\[(.*)\]/')->value(),
                $state->currentSegment(),
            );

            return new ContinueIterating($state->replaceCurrentSegmentWith(
                Str::of($possibleDirectory)->basename()
            ));
        }

        return $next($state);
    }
}
