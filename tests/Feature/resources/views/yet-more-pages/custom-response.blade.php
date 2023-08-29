<?php

use function Laravel\Folio\get;

get(function () {
    return to_route('users.show', ['user' => 1]);
}); ?>

<div>
    @php
        throw new \Exception('Should not be rendered');
    @endphp
</div>
