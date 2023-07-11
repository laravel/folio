<?php

use function Laravel\Folio\middleware;

middleware(function ($request, $next) {
    $_SERVER['__folio_podcasts_inline_middleware'] = $request->route('podcast');

    return $next($request);
});

?>

@php
    $_SERVER['__folio_podcasts_php_blade_block'] = $podcast;
@endphp


<div>
    {{ $podcast->name }}
</div>
