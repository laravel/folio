<?php

namespace Laravel\Folio;

use Illuminate\Support\Collection;

class Metadata
{
    /**
     * Create a new metadata instance.
     */
    public function __construct(
        public Collection $middleware = new Collection,
        public bool $withTrashed = false
    ) {
        //
    }
}
