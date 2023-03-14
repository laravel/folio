<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Facade;

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
