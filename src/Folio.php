<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Facade;

/**
 * @method static static route(string|null $path = null, string|null $uri = '/', array $middleware = [])
 * @method static array middlewareFor(string $uri)
 * @method static mixed|null data(string|null $key = null, mixed|null $default = null)
 * @method static \Laravel\Folio\FolioManager renderUsing(\Closure|null $callback = null)
 * @method static array mountPaths()
 * @method static array paths()
 * @method static string extension(string|null $extension = null)
 * @method static \Laravel\Folio\FolioManager withExtension(string $extension)
 *
 * @see \Laravel\Folio\FolioManager
 */
class Folio extends Facade
{
    /**
     * {@inheritDoc}
     */
    public static function getFacadeAccessor(): string
    {
        return FolioManager::class;
    }
}
