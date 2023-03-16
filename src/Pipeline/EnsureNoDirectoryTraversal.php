<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;
use Laravel\Folio\Exceptions\PossibleDirectoryTraversal;

class EnsureNoDirectoryTraversal
{
    /**
     * Create a new pipeline class instance.
     */
    public function __construct(public string $mountPath)
    {
    }

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        $view = $next($state);

        if (! $view instanceof MatchedView) {
            return $view;
        }

        $view = $view->withMountPath($this->mountPath);

        if (! Str::of(realpath($view->path))->startsWith($this->mountPath.'/')) {
            throw new PossibleDirectoryTraversal;
        }

        return $view;
    }
}
