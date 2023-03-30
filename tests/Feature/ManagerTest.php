<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
