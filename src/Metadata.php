<?php

namespace Laravel\Folio;

use Illuminate\Support\Collection;

class Metadata
{
    /**
     * The middleware specified in the metadata.
     */
    public Collection $middleware;

    /**
     * Create a new metadata instance.
     */
    public function __construct(?Collection $middleware = null)
    {
        $this->middleware = $middleware ?? new Collection;
    }
}
