<?php

use function Laravel\Folio\page;

page(middleware: 'auth');

?>

@foreach($categories as $category)
    {{ $category->value }}
@endforeach
