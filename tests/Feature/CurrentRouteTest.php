<?php

use Laravel\Folio\Folio;

beforeEach(fn () => Folio::route(__DIR__.'/resources/views/another-set-of-pages'));

it('dynamically updates folio is named route', function () {
    $response = $this->get(route('pens.index'));

    $response
        ->assertSee('Is pens.index active: true.')
        ->assertSee('Current route name: pens.index.')
        ->assertSee('Has pens.index: false.')
        ->assertSee('Is pens: true.');
});

it('resets folio is route name after handling request', function () {
    $this->get(route('pens.index'));

    expect(request()->route()->getName())->toBe('laravel-folio');
});

it('does not change the route name if there is no name', function () {
    $response = $this->get('pens/show');

    $response
        ->assertSee('Is pens.show active: false.')
        ->assertSee('Current route name: laravel-folio.')
        ->assertSee('Has pens.show: false.')
        ->assertSee('Is pens/show: true.');
});
