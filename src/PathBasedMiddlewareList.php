<?php

namespace Laravel\Folio;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Folio\Pipeline\MatchedView;

class PathBasedMiddlewareList
{
    /**
     * Create a new path based middleware list instance.
     */
    public function __construct(protected array $middleware)
    {
    }

    /**
     * Find the middleware that match the given matched view's path.
     */
    public function match(MatchedView $view): array
    {
        $matched = [];

        $relativePath = trim($view->relativePath(), '/');

        foreach ($this->middleware as $pattern => $middleware) {
            if (Str::is(trim($pattern, '/'), $relativePath)) {
                $matched = array_merge($matched, Arr::wrap($middleware));
            }
        }

        return $matched;
    }
}
