<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laravel\Folio\FolioManager route(?string $path = null, ?string $uri = '/', array $middleware = [])
 */
class Folio extends Facade
{
    /**
     * {@inheritDoc}     .
     */
    public static function getFacadeAccessor()
    {
        return FolioManager::class;
    }
}
