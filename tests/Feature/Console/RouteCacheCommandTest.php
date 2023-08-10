<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\Folio;

test('routes may be cached', function () {
    Folio::route(__DIR__.'/../resources/views/pages');

    $command = $this->artisan('route:cache');

    $command->expectsOutputToContain('Routes cached successfully.');

    $command->assertOk();
});
