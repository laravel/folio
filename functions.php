<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laravel\Folio\Options\PageOptions;

/**
 * Specify the callback that should be used to render matched views.
 */
function render(callable $callback): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->renderUsing = $callback,
    );

    return new PageOptions;
}

/**
 * Specify the name of the current page.
 */
function name(string $name): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->name = $name,
    );

    return new PageOptions;
}

/**
 * Add one or more middleware to the current page.
 */
function middleware(Closure|string|array $middleware = []): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->middleware = Metadata::instance()->middleware->merge(Arr::wrap($middleware)),
    );

    return new PageOptions;
}

/**
 * Indicate that the current page should include trashed models.
 */
function withTrashed(bool $withTrashed = true): PageOptions
{
    Container::getInstance()->make(InlineMetadataInterceptor::class)->whenListening(
        fn () => Metadata::instance()->withTrashed = $withTrashed,
    );

    return new PageOptions;
}
