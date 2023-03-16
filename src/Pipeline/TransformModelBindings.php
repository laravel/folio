<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;

class TransformModelBindings
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        $view = $next($state);

        if (! $view instanceof MatchedView) {
            return $view;
        }

        $path = (string) Str::of($view->path)
            ->replace($state->mountPath, '')
            ->beforeLast('.blade.php')
            ->trim('/');

        [$parent, $uriSegments, $pathSegments] = [
            null, explode('/', $state->uri), explode('/', $path),
        ];

        foreach ($pathSegments as $index => $segment) {
            $segment = new PotentiallyBindablePathSegment($segment);

            if (! $segment->bindable()) {
                continue;
            }

            if ($segment->capturesMultipleSegments()) {
                return $view->replace(
                    $segment->trimmed(),
                    $segment->variable(),
                    collect(array_slice($uriSegments, $index))
                        ->map(fn ($value) => $segment->resolveOrFail($value, $parent))
                        ->all(),
                );
            }

            // TODO: withTrashed support...

            $parent = $segment;

            $view = $view->replace(
                $segment->trimmed(),
                $segment->variable(),
                $segment->resolveOrFail($uriSegments[$index], $parent),
            );
        }

        return $view;
    }
}
