<?php

use Illuminate\Support\Facades\URL;
use Laravel\Folio\UrlGeneratorDecorator;

it('registers an url generator "decorator"', function () {
    foreach ([
        URL::getFacadeRoot(),
        app('url'),
    ] as $instance) {
        expect($instance)->toBeInstanceOf(UrlGeneratorDecorator::class);
    }
});
