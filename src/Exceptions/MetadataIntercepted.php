<?php

namespace Laravel\Folio\Exceptions;

use Laravel\Folio\Metadata;
use RuntimeException;

class MetadataIntercepted extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(public Metadata $metadata)
    {
    }
}
