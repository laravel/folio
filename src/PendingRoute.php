<?php

namespace Laravel\Folio;

class PendingRoute
{
    /**
     * Create a new pending route instance.
     *
     * @param  array<string, array<int, string>>  $middleware
     */
    public function __construct(
        protected FolioManager $manager,
        protected string $path,
        protected string $uri,
        protected array $middleware,
        protected ?string $domain = null,
    ) {}

    /**
     * Set the domain for the route.
     */
    public function domain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Set the path for the route.
     */
    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the middleware for the route.
     */
    public function middleware(array $middleware): static
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Set the URI for the route.
     */
    public function uri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Register the route upon instance destruction.
     */
    public function __destruct()
    {
        $this->manager->registerRoute(
            $this->path,
            $this->uri,
            $this->middleware,
            $this->domain,
        );
    }
}
