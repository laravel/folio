<?php

namespace Laravel\Folio;

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
        public Collection $middleware = new Collection,
        public bool $withTrashed = false
    ) {
        //
    }

    /**
     * Get the current compile context instance or create a new one.
     */
    public static function instance(): static
    {
        return static::$instance ??= new static;
    }

    /**
     * Flush the current global instance of the compile context.
     */
    public static function flush(): void
    {
        static::$instance = null;
    }
}
