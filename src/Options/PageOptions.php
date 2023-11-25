<?php

namespace Laravel\Folio\Options;

use Closure;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Laravel\Folio\withTrashed;

class PageOptions
{
    /**
     * Specify the name of the current page.
     */
    public function name(string $name): static
    {
        return name($name);
    }

    /**
     * Add one or more middleware to the current page.
     */
    public function middleware(Closure|string|array $middleware = []): static
    {
        return middleware($middleware);
    }

    /**
     * Indicate that the current page should include trashed models.
     */
    public function withTrashed(bool $withTrashed = true): static
    {
        return withTrashed($withTrashed);
    }
}
