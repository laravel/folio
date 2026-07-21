<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\Folio;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    $path = __DIR__.'/resources/views/livewire-pages';

    config()->set('livewire.component_layout', 'components.app');
    config()->set('livewire.component_namespaces.pages', $path);

    app('livewire')->addNamespace('pages', viewPath: $path);

    Folio::route($path);
});

test('livewire single-file components may be used as folio pages', function () {
    $this->get('/counter')
        ->assertOk()
        ->assertSee('Count: 1')
        ->assertSee('wire:snapshot', false);
});

test('livewire single-file components with an emoji prefix use the unprefixed uri', function () {
    $this->get('/profile')
        ->assertOk()
        ->assertSee('Livewire profile')
        ->assertSee('wire:snapshot', false);
});

test('emoji-prefixed index components match folio index routes', function (string $uri, string $content) {
    $this->get($uri)
        ->assertOk()
        ->assertSee($content)
        ->assertSee('wire:snapshot', false);
})->with([
    ['/', 'Livewire home'],
    ['/settings', 'Livewire settings'],
]);

test('emoji-prefixed wildcard components receive folio route parameters', function (string $uri, string $content) {
    $this->get($uri)
        ->assertOk()
        ->assertSee($content)
        ->assertSee('wire:snapshot', false);
})->with([
    ['/andrew', 'Profile: andrew'],
    ['/teams/lineweb', 'Team: lineweb'],
]);

test('folio route listings and named urls omit the emoji prefix', function () {
    $output = new BufferedOutput;

    Artisan::call('folio:list', ['--json' => true], $output);

    $routes = collect(json_decode($output->fetch(), true, flags: JSON_THROW_ON_ERROR));

    expect($routes->pluck('uri'))
        ->toContain('/profile')
        ->not->toContain('/⚡profile')
        ->and(route('livewire.profile', [], false))->toBe('/profile');
});
