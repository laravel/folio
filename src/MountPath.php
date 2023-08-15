<?php

namespace Laravel\Folio;

class MountPath
{
    /**
     * The path based middleware for the mounted path.
     */
    public PathBasedMiddlewareList $middleware;

    /**
     * Create a new mounted path instance.
     */
    public function __construct(
        public string $path,
        public string $baseUri,
        array $middleware,
        public ?string $domain,
    ) {
        $this->path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->middleware = new PathBasedMiddlewareList($middleware);
    }
}
