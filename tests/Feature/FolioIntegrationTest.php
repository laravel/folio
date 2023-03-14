<?php

use Laravel\Folio\Folio;

beforeEach(function () {
    Folio::route(__DIR__.'/resources/views/pages', middleware: []);
});

test('root index view can be matched', function () {
    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');
})->only();
