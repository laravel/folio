<?php
use function Laravel\Folio\{folio};

folio(withTrashed: true);
?>
<div>
    {{ $podcast->name }}
</div>
