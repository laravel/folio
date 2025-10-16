<?php

use function Laravel\Folio\name;

name('test.route');

?>

<h1>Test Route</h1>
<p>User: {{ $user->email }}</p>
<p>Podcast: {{ $podcast->name }}</p>
