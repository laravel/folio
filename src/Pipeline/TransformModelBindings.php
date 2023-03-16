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
        if (! ($view = $next($state)) instanceof MatchedView) {
            return $view;
        }

        [$uriSegments, $pathSegments] = [
            explode('/', $state->uri),
            $this->bindablePathSegments($view),
        ];

        foreach ($pathSegments as $index => $segment) {
            if (! ($segment = new PotentiallyBindablePathSegment($segment))->bindable()) {
                continue;
            }

            if ($segment->capturesMultipleSegments()) {
                return $view->replace(
                    $segment->trimmed(),
                    $segment->variable(),
                    collect(array_slice($uriSegments, $index))
                        ->map(fn ($value) => $segment->resolveOrFail(
                            $value, $parent ?? null, $view->allowsTrashedBindings()
                        ))
                        ->all(),
                );
            }

            $view = $view->replace(
                $segment->trimmed(),
                $segment->variable(),
                $segment->resolveOrFail(
                    $uriSegments[$index],
                    $parent ?? null,
                    $view->allowsTrashedBindings()
                ),
            );

            $parent = $segment;
        }

        return $view;
    }

    /**
     * Get the bindable path segments for the matched view.
     */
    protected function bindablePathSegments(MatchedView $view): array
    {
        return explode('/', (string) Str::of($view->path)
            ->replace($view->mountPath, '')
            ->beforeLast('.blade.php')
            ->trim('/'));
    }
}
