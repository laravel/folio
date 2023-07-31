<?php

namespace Laravel\Folio\Exceptions;

use Exception;

class UrlGenerationException extends Exception
{
    /**
     * Create a new exception for a missing URL parameter.
     */
    public static function forMissingParameter(string $path, string $parameter): static
    {
        return new static(sprintf(
            'Missing required parameter for [Path: %s] [Missing parameter: %s].',
            $path,
            $parameter,
        ));
    }
}
