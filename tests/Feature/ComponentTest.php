<?php

use Laravel\Folio\Folio;
use function Orchestra\Testbench\workbench_path;

it('may use component props', function () {
    Folio::route(workbench_path('resources/views/pages'));

    $this->withoutExceptionHandling();

    $response = $this->get('/dashboard');

    $response
        ->assertSee('My Page Title Is: Videos.')
        ->assertSee('Hello From Main Content');

    expect($response->getContent())
        ->toStartWith('<!DOCTYPE html>');
});
