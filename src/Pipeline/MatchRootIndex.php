<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class MatchRootIndex
{

    /**
     * Create a new pipeline step instance.
     */
    public function __construct(protected array $extensions)
    {
    }

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if (trim($state->uri) === '/') {
            foreach ($this->extensions as $extension) {
                if (file_exists($path = $state->mountPath . '/index' . $extension)) {
                    return new MatchedView($path, $state->data);
                }
            }
            return new StopIterating;
        }

        return $next($state);
    }
}
