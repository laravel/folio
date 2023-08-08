<?php

use function Laravel\Folio\{withTrashed};

withTrashed();

?>

<div>
    {{ $podcast->name }}
</div>
