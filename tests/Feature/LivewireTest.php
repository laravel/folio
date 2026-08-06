<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Laravel\Folio\Support\LivewireComponents;
use Livewire\Component;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Feature\Fixtures\Podcast;

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

test('emoji-prefixed multi-segment model wildcard components may be used as folio pages', function () {
    Schema::create('podcasts', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    $firstPodcast = Podcast::create(['name' => 'First podcast']);
    $secondPodcast = Podcast::create(['name' => 'Second podcast']);

    $this->get("/podcasts/{$firstPodcast->id}/{$secondPodcast->id}")
        ->assertOk()
        ->assertSee('First podcast, Second podcast')
        ->assertSee('wire:snapshot', false);
});

test('livewire component route metadata is cleared after handling the request', function () {
    $this->get('/counter')->assertOk();

    expect(request()->route()->getAction('livewire_component'))->toBeNull();
});

test('folio wildcard component names resolve in a fresh application', function () {
    $path = __DIR__.'/resources/views/livewire-pages';
    $view = $path.'/podcasts/⚡[...Tests.Feature.Fixtures.Podcast].blade.php';
    $component = LivewireComponents::name($view);

    expect($component)->toStartWith('folio-');

    $this->refreshApplication();

    config()->set('livewire.component_namespaces.pages', $path);
    app('livewire')->addNamespace('pages', viewPath: $path);
    Folio::route($path);

    expect(app('livewire')->new($component))->toBeInstanceOf(Component::class);
});

test('folio route listings and named urls omit the emoji prefix', function () {
    $output = new BufferedOutput;

    Artisan::call('folio:list', ['--json' => true], $output);

    $routes = collect(json_decode($output->fetch(), true, flags: JSON_THROW_ON_ERROR));

    expect($routes->pluck('uri'))
        ->toContain('/profile')
        ->not->toContain('/⚡profile')
        ->and(route('livewire.profile', [], false))->toBe('/profile');
});
