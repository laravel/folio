<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Laravel\Folio\Drivers\FolioDriverContract;

class MatchDirectoryIndexViews
{
    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        $driver = app(FolioDriverContract::class);
        $extension = $driver->extension();
        $path = $state->currentUriSegmentDirectory().'/index'.$extension;

        return $state->onLastUriSegment() &&
            $state->currentUriSegmentIsDirectory() &&
            file_exists($path)
                ? new MatchedView($path, $state->data)
                : $next($state);
    }
}
