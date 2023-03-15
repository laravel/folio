<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laravel\Folio\Exceptions\MiddlewareIntercepted;

function middleware(Closure|string|array $middleware)
{
    if (Container::getInstance()->make(InlineMiddlewareInterceptor::class)->listening()) {
        throw new MiddlewareIntercepted(Arr::wrap($middleware));
    }
}
