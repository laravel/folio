<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Folio\Functions;

class MatchWildcardDirectories
{
    public function __invoke(State $state, Closure $next): mixed
    {
        if (! $state->onLastUriSegment() &&
            $directory = $this->findWildcardDirectory($state->currentDirectory())) {
            $state = $state->withData(
                Str::of($directory)
                    ->basename()
                    ->match('/\[(.*)\]/')->value(),
                $state->currentUriSegment(),
            );

            return new ContinueIterating($state->replaceCurrentUriSegmentWith(
                Str::of($directory)->basename()
            ));
        }

        return $next($state);
    }

    /**
     * Attempt to find a wildcard directory within the given directory.
     */
    public function findWildcardDirectory(string $directory): ?string
    {
        return collect((new Filesystem)->directories($directory))
            ->first(function ($directory) {
                $directory = Str::of($directory)->basename();

                return $directory->startsWith('[') &&
                       $directory->endsWith(']');
            });
    }
}
