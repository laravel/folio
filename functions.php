<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laravel\Folio\Exceptions\MetadataIntercepted;

function page(Closure|string|array $middleware = [], bool $withTrashed = false)
{
    if (Container::getInstance()->make(InlineMetadataInterceptor::class)->listening()) {
        throw new MetadataIntercepted(
            new Metadata(
                middleware: collect(Arr::wrap($middleware)),
                withTrashed: $withTrashed,
            )
        );
    }
}
