<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;
use Laravel\Folio\Router;

class MatchWildcardViewsThatCaptureMultipleSegments
{
    use FindsWildcardViews;

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($path = $this->findWildcardMultiSegmentView($state->currentDirectory())) {
            Router::ensureNoDirectoryTraversal(
                $state->currentDirectory().'/'.$path, $state->mountPath
            );

            return new MatchedView($state->currentDirectory().'/'.$path, $state->withData(
                Str::of($path)
                    ->before('.blade.php')
                    ->match('/\[\.\.\.(.*)\]/')->value(),
                array_slice(
                    $state->segments,
                    $state->currentIndex,
                    $state->uriSegmentCount() - 1
                )
            )->data);
        }

        return $next($state);
    }
}
