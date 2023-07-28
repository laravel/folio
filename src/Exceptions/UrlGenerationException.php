<?php

namespace Laravel\Folio\Exceptions;

use Exception;

class UrlGenerationException extends Exception
{
    public static function forMissingParameter(string $path, string $parameter): static
    {
        return new static(sprintf(
            'Missing required parameter for [Path: %s] [Missing parameter: %s]',
            $path,
            $parameter
        ));
    }
}
