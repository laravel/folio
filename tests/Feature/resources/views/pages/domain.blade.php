<?php

use function Laravel\Folio\{middleware, name};

middleware([])->name('my-domain'); ?>

<div>
    <ul>
        <li> The domain is: {{ $domain ?? 'none' }}.</li>
        <li>The sub-domain is: {{ $subDomain ?? 'none' }}.</li>
    </ul>
</div>
