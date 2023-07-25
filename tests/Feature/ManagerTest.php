<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Folio\Events\ViewMatched;
use Laravel\Folio\Folio;
use Laravel\Folio\Pipeline\MatchedView;

afterEach(function () {
    Folio::renderUsing(null);
});

it('registers routes', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');
});

it('registers routes with a custom URI', function (string $uri) {
    Folio::route(__DIR__.'/resources/views/pages', $uri);

    $response = $this->get("$uri/users/Taylor");

    $response->assertSee('Hello, Taylor');
})->with(['/', '/custom-uri']);

it('registers routes with middleware', function () {
    Route::get('login', fn () => 'login')->name('login');
    Folio::route(__DIR__.'/resources/views/pages', middleware: ['*' => ['auth']]);

    $response = $this->get('/users/Taylor');

    $response->assertRedirect('login');
});

it('registers routes with custom render callback', function () {
    Folio::renderUsing(function (Request $request, MatchedView $view) {
        return response([
            $view->path,
            $view->data,
            $view->mountPath,
        ]);
    });

    Folio::route(__DIR__.'/resources/views/pages');

    $response = $this->get('/users/Taylor');
    [$path, $data, $mountPath] = $response->json();

    expect($path)->toBe(__DIR__.'/resources/views/pages/users/[id].blade.php')
        ->and($data)->toBe(['id' => 'Taylor'])
        ->and($mountPath)->toBe(__DIR__.'/resources/views/pages');
});

it('fires view matched event on route', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $events = Event::fake(ViewMatched::class);

    $response = $this->get('/users/Taylor');

    $response->assertOk();

    $events->assertDispatched(ViewMatched::class, function ($event) {
        return $event->matchedView->path === __DIR__.'/resources/views/pages/users/[id].blade.php';
    });
});

it('doesn\'t fire view matched event on 404', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $events = Event::fake(ViewMatched::class);

    $response = $this->get('/invalid-route');

    $response->assertNotFound();

    $events->assertNotDispatched(ViewMatched::class);
});
