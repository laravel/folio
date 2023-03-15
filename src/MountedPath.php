<?php

namespace Laravel\Folio;

class MountedPath
{
    /**
     * The path based middleware for the mounted path.
     */
    public PathBasedMiddlewareList $middleware;

    /**
     * Create a new mounted path instance.
     */
    public function __construct(public string $path,
                                public string $baseUri,
                                array $middleware = [])
    {
        $this->middleware = new PathBasedMiddlewareList($middleware);
    }

    /**
     * Determine if the mounted path uses a "fallback" route.
     */
    public function usesFallbackRoute(): bool
    {
        return $this->baseUri === '/';
    }
}
