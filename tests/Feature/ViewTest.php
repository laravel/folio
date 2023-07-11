<?php

use Laravel\Folio\Folio;

it('may have blade php blocks', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $response = $this->get('/users/nuno');

    $response->assertSee('Hello, Nuno Maduro from PHP block.');
});

it('blade php blocks are only executed when rendering the view', function () {
    $_SERVER['__folio_rendered_count'] = 0;

    Folio::route(__DIR__.'/resources/views/pages');

    $this->get('/users/nuno');
    $response = $this->get('/users/nuno');

    $response->assertSee('Rendered [2] time from PHP block.');
});
