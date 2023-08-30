<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Support\Collection;

class Metadata
{
    /**
     * The current global instance of the metadata, if any.
     */
    protected static ?self $instance = null;

    /**
     * Create a new metadata instance.
     */
    protected function __construct(
        public ?string $name = null,
        public Collection $middleware = new Collection,
        public bool $withTrashed = false,
        public ?Closure $renderUsing = null,
    ) {
        //
    }

    /**
     * Get the current metadata instance or create a new one.
     */
    public static function instance(): static
    {
        return static::$instance ??= new static;
    }

    /**
     * Flush the current global instance of the metadata.
     */
    public static function flush(): void
    {
        static::$instance = null;
    }
}
