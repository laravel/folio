<?php

use function Laravel\Folio\middleware;

middleware('auth');

?>

@foreach($categories as $category)
    {{ $category->value }}
@endforeach
