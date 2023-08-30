<?php

use Illuminate\Contracts\Cache\Repository;
use Illuminate\View\View;

use function Laravel\Folio\render;

render(function (View $view, Repository $cache) {
    $count = $cache->get('count', 0);

    $cache->put('count', $count = $count + 1);

    $view->with([
        'count' => $count,
    ]);
}); ?>

<div>
    <h1>Counter</h1>

    <p>
        This page has been viewed {{ $count }} times.
    </p>
</div>
