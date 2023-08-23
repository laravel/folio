<?php

use function Laravel\Folio\name;

name('pens.index'); ?>

<x-app>
    <ul>
        <li>Is pens.index active: {{ request()->routeIs('pens.index') ? 'true' : 'false' }}.</li>
        <li>Current route name: {{ Route::currentRouteName() }}.</li>
        <li>Has pens.index: {{ Route::has('pens.index') ? 'true' : 'false' }}.</li> <!-- It's a know limitation... -->
        <li>Is pens: {{ request()->is('pens') ? 'true' : 'false' }}.</li>
    </ul>
</x-app>
