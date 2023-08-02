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

    /**
     * Get the route name assigned to the route at this mount path.
     */
    public function routeName(): string
    {
        return 'folio-'.substr(sha1($this->baseUri), 0, 10);
    }
}
