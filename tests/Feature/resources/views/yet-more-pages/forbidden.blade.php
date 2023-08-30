<?php

use Illuminate\Support\Facades\Gate;

use function Laravel\Folio\render;

render(function () {
    return Gate::authorize('viewAny');
}); ?>

<div>
    @php
        throw new \Exception('Should not be rendered');
    @endphp
</div>
