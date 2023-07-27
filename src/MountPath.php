<?php

namespace Laravel\Folio;

class MountPath
{
    /**
     * The path based middleware for the mounted path.
     */
    public PathBasedMiddlewareList $middleware;

     /**
     * The file extension to use while routing.
     */
    public string $pathExtension;

    /**
     * Create a new mounted path instance.
     */
    public function __construct(
        public string $path,
        public string $baseUri,
        array $middleware = [],
    ) {
        $this->middleware = new PathBasedMiddlewareList($middleware);
    }

    /**
     * Get the route name assigned to the route at this mount path.
     */
    public function routeName(): string
    {
        return 'folio-'.substr(sha1($this->baseUri), 0, 10);
    }

    /**
     * Register the file extension used while routing.
     */
    public function extension(string|null $extension='.blade.php'): string
    {
        return $this->pathExtension ??= $extension;
    }
}
