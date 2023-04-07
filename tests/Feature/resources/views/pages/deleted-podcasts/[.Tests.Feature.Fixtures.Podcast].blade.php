<?php
use function Laravel\Folio\{page};

page(withTrashed: true);
?>
<div>
    {{ $podcast->name }}
</div>
