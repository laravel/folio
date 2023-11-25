<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Folio\Drivers\FolioDriverContract;

class MatchWildcardDirectories
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($directory = $this->findWildcardDirectory($state->currentDirectory())) {
            $currentState = $state->withData(
                Str::of($directory)
                    ->basename()
                    ->match('/\[(.*)\]/')->value(),
                $state->currentUriSegment(),
            )->replaceCurrentUriSegmentWith(
                Str::of($directory)->basename()
            );

            if (! $currentState->onLastUriSegment()) {
                return new ContinueIterating($currentState);
            }

            if (file_exists($path = $currentState->currentUriSegmentDirectory().'/index'.config('folio.extension'))) {
                return new MatchedView($path, $currentState->data);
            }
        }

        return $next($state);
    }

    /**
     * Attempt to find a wildcard directory within the given directory.
     */
    public function findWildcardDirectory(string $directory): ?string
    {
        return collect((new Filesystem)->directories($directory))
            ->first(function (string $directory) {
                $directory = Str::of($directory)->basename();

                return $directory->startsWith('[') &&
                       $directory->endsWith(']');
            });
    }
}
