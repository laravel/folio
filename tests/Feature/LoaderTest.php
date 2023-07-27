<?php

use Illuminate\Support\Facades\Route;
use Laravel\Folio\Folio;
use Laravel\Folio\MountPath;
use Tests\Feature\Fixtures\Loader;

it('can use a custom loader', function(){
    Folio::route(__DIR__.'/resources/views/pages', loader: new Loader());

    $response = $this->get('/users/Taylor');

    $response->assertOk();

    $this->assertTrue(Route::has('test-folio-custom-loader'));
});

it('can use a custom loader as a callback function', function(){
    Folio::route(__DIR__.'/resources/views/pages', loader: function(string $uri, MountPath $mountPath, Closure $handler){

        if ($uri === '/') {
            Route::fallback($handler)
                ->name('test-folio-custom-callback');
        } else {
            Route::get(
                '/'.trim($uri, '/').'/{uri?}',
                $handler
            )->name('test-folio-custom-callback')->where('uri', '.*');
        }
    });

    $response = $this->get('/users/Taylor');

    $response->assertOk();

    $this->assertTrue(Route::has('test-folio-custom-callback'));
});