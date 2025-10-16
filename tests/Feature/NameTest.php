<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\Folio;
use Laravel\Folio\FolioRoutes;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\Feature\Fixtures\Podcast;
use Tests\Feature\Fixtures\User;

beforeEach(function () {
    Folio::route(__DIR__.'/resources/views/pages');
    Folio::path(__DIR__.'/resources/views/more-pages');

    app(FolioRoutes::class)->flush();

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('email');
        $table->timestamps();
    });

    Schema::create('podcasts', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Podcast::create([
        'name' => 'test-podcast-name-1',
    ]);

    User::create([
        'email' => 'test-email-1@laravel.com',
    ]);
});

$dataset = [
    ['/users/Taylor', 'users.show', ['id' => 'Taylor']],
    ['/users/nuno', 'users.nuno', []],
    ['/dashboard', 'dashboard', []],
    ['/domain', 'my-domain', []],
    ['/', 'more-pages.index', []],
    ['/1/2/3/detail', 'more-pages.user.detail', ['users' => [1, 2, 3]]],
];

test('routes may have a name without cache', function (string $expected, $name, array $arguments = []) {
    $route = route($name, $arguments, false);

    expect($route)->toBe($expected);
})->with($dataset);

test('routes may have a name using cache', function (string $expected, $name, array $arguments = []) {
    app(FolioRoutes::class)->persist();

    app()->forgetInstance(FolioRoutes::class);

    $route = route($name, $arguments, false);

    expect($route)->toBe($expected);
})->with($dataset);

test('may be or not absolute', function () {
    $route = route('users.show', ['id' => 'Taylor'], false);
    $absoluteRoute = route('users.show', ['id' => 'Taylor'], true);

    expect($route)->toBe('/users/Taylor');
    expect($absoluteRoute)->toBe('http://localhost/users/Taylor');
});

test('feature parity', function () {
    Route::get('/posts/{lowerCase}/{UpperCase}/{podcast}/{user:email}/show', function (
        string $id,
        string $upperCase,
        Podcast $podcast,
        User $user) {
        //
    })->name('users.regular');

    $parameters = [
        'lowerCase' => 'lowerCaseValue',
        'upperCase' => 'UpperCaseValue',
        'podcast' => Podcast::first(),
        'user' => User::first(),
    ];

    $expectedRoute = '/posts/lowerCaseValue/UpperCaseValue/1/test-email-1@laravel.com/show';

    $route = route('users.regular', [
        'lowerCase' => 'lowerCaseValue',
        'UpperCase' => 'UpperCaseValue',
        'podcast' => Podcast::first(),
        'user' => User::first(),
    ], false);

    expect($route)->toBe($expectedRoute);

    $route = route('posts.show', $parameters, false);

    expect($route)->toBe($expectedRoute);
});

test('model route binding wrong column', function () {
    $parameters = [
        'user' => User::first(),
    ];

    $route = route('user.articles', $parameters, false);
})->throws(
    UrlGenerationException::class,
    'Missing required parameter [user] for path [tests/Feature/resources/views/pages/users/articles/[User-wrong_column]].',
);

test('routes may not have a name', function () {
    route('users.index');
})->throws(RouteNotFoundException::class, 'Route [users.index] not defined.');

test('custom uri', function () {
    Folio::uri('/user')->path(__DIR__.'/resources/views/even-more-pages');

    $absoluteRoute = route('profile');
    $route = route('profile', [], false);

    expect($absoluteRoute)->toBe('http://localhost/user/profile')
        ->and($route)->toBe('/user/profile');
});

test('custom domain', function () {
    Folio::domain('example.com')->uri('/user')->path(__DIR__.'/resources/views/even-more-pages');

    $absoluteRoute = route('profile');
    $route = route('profile', [], false);

    expect($absoluteRoute)->toBe('http://example.com/user/profile')
        ->and($route)->toBe('/user/profile');
});

test('route helper accepts single scalar parameter', function () {
    $route = route('users.show', 'Taylor', false);

    expect($route)->toBe('/users/Taylor');
});

test('route helper accepts single model parameter', function () {
    $user = User::first();

    // Test with single model - this should work for routes with only one model parameter
    $route = route('users.show', $user, false);
    expect($route)->toBe('/users/1'); // User ID is 1
});

test('route helper accepts positional parameters with multiple models', function () {
    $user = User::first();
    $podcast = Podcast::first();

    // Test with array of models
    $route = route('test.route', [$user, $podcast], false);
    expect($route)->toContain('/test-route/');

    // Test with associative array (should still work)
    $route = route('test.route', ['user' => $user, 'podcast' => $podcast], false);
    expect($route)->toContain('/test-route/');
});

test('route helper works with single model parameter', function () {
    $user = User::first();

    // Test that route() accepts a single model parameter
    $route = route('users.show', $user, false);
    expect($route)->toBe('/users/1');

    // Test that it works with scalar values too
    $route = route('users.show', 'Taylor', false);
    expect($route)->toBe('/users/Taylor');
});

test('route helper works with fully qualified model class names', function () {
    $podcast = Podcast::first();

    // Test with fully qualified class name in segment [.Tests.Feature.Fixtures.Podcast]
    $route = route('podcasts.show', $podcast, false);
    expect($route)->toContain('/podcasts/');
});

test('podcast view renders correctly with model parameter', function () {
    $podcast = Podcast::first();

    // Test the actual view content by making a request
    $response = $this->get(route('podcasts.show', $podcast, false));

    $response->assertStatus(200);
    $response->assertSee($podcast->name);
});
