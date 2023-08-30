<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

use function Laravel\Folio\middleware;
use function Laravel\Folio\render;

middleware(function (Request $request, Closure $next) {
    $request->attributes->set('content', 'middleware 1');

    return $next($request);
});

render(function (View $view, Request $request) {
    return $view->with('content', $request->attributes->get('content').' view 3');
})->middleware(function (Request $request, Closure $next) {
    $request->attributes->set('content', $request->attributes->get('content').' middleware 2');

    return $next($request);
}) ?>

<div>
    <span>Content: {{ $content }}.</span>
</div>

