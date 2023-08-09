<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laravel\Folio\Options\PageOptions;

/**
 * Adds one or more middleware to the current page.
 */
function middleware(Closure|string|array $middleware = []): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->middleware = Metadata::instance()->middleware->merge(Arr::wrap($middleware)),
    );

    return new PageOptions;
}

/**
 * Sets the name of the current page.
 */
function name(string $name): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->name = $name,
    );

    return new PageOptions;
}

/**
 * Indicates that the current page should include trashed models.
 */
function withTrashed(bool $withTrashed = true): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->withTrashed = $withTrashed,
    );

    return new PageOptions;
}
