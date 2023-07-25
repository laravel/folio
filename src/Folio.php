<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laravel\Folio\FolioManager route(?string $path = null, ?string $uri = '/', array $middleware = [])
 * @method static \Laravel\Folio\FolioManager paths()
 */
class Folio extends Facade
{
    /**
     * {@inheritDoc}     .
     */
    public static function getFacadeAccessor(): string
    {
        return FolioManager::class;
    }
}
