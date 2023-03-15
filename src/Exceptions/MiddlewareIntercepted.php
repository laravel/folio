<?php

namespace Laravel\Folio\Exceptions;

use RuntimeException;

class MiddlewareIntercepted extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(public array $middleware)
    {
    }
}
