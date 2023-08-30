<?php

use function Laravel\Folio\render;

render(function ($view) {
    if (request()->hasHeader('HX-Request')) {
        return $view->fragment(request()->header('HX-Request'));
    }
}) ?>

<div>
    <h1>This is the title.</h1>

    @fragment('fragment-name')
        <div>This is a fragment.</div>
    @endfragment
</div>
