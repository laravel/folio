<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laravel\Folio\Exceptions\MetadataIntercepted;

function folio(Closure|string|array $middleware)
{
    if (Container::getInstance()->make(InlineMetadataInterceptor::class)->listening()) {
        throw new MetadataIntercepted(
            new Metadata(middleware: collect(Arr::wrap($middleware)))
        );
    }
}
