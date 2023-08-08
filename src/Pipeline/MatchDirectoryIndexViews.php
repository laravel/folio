<?php

namespace Laravel\Folio\Pipeline;

use Closure;

class MatchDirectoryIndexViews
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
        if ($state->onLastUriSegment() &&
            $state->currentUriSegmentIsDirectory()) {
            foreach ($this->extensions as $extension) {
                if (file_exists($path = $state->currentUriSegmentDirectory() . '/index' . $extension)) {
                    return new MatchedView($path, $state->data);
                }
            }
        }
        return $next($state);
    }
}
