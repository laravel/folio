<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array middlewareFor(string $uri)
 * @method static mixed|null data(string|null $key = null, mixed|null $default = null)
 * @method static \Laravel\Folio\FolioManager renderUsing(\Closure|null $callback = null)
 * @method static array mountPaths()
 * @method static array paths()
 * @method static \Laravel\Folio\PendingRoute route(string|null $path = null, string|null $uri = '/', array $middleware = [])
 * @method static \Laravel\Folio\PendingRoute path(string $path)
 * @method static \Laravel\Folio\PendingRoute uri(string $uri)
 * @method static \Laravel\Folio\PendingRoute domain(string $domain)
 * @method static \Laravel\Folio\PendingRoute middleware(array $middleware)
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
