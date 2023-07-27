<?php

use Illuminate\Support\Facades\Route;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Loader;

it('can use a custom loader', function(){
    Folio::route(__DIR__.'/resources/views/pages', loader: new Loader());

    $response = $this->get('/users/Taylor');

    $response->assertOk();

    $this->assertTrue(Route::has('test-folio-custom-loader'));
});