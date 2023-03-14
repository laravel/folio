<?php

namespace Laravel\Folio;

class MountedPath
{
    /**
     * Create a new mounted path instance.
     */
    public function __construct(public string $mountPath,
                                public string $baseUri,
                                public array $middleware = [])
    {
    }

    /**
     * Determine if the mounted path uses a "fallback" route.
     */
    public function usesFallbackRoute(): bool
    {
        return $this->baseUri === '/';
    }
}
