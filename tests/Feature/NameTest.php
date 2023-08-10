<?php

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
    'Missing required parameter for [Path: tests/Feature/resources/views/pages/users/articles/[User-wrong_column]] [Missing parameter: user]',
);

test('routes may not have a name', function () {
    route('users.index');
})->throws(RouteNotFoundException::class, 'Route [users.index] not defined.');
