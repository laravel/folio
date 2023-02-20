<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Folio\Router;

class MatchWildcardViews
{
    use FindsWildcardViews;

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($state->onLastUriSegment() &&
            $path = $this->findWildcardView($state->currentDirectory())) {
            Router::ensureNoDirectoryTraversal(
                $state->currentDirectory().'/'.$path, $state->mountPath
            );

            return View::file($state->currentDirectory().'/'.$path, $state->withData(
                Str::of($path)
                    ->before('.blade.php')
                    ->match('/\[(.*)\]/')->value(),
                $state->currentUriSegment(),
            )->data);
        }

        return $next($state);
    }
}
