<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\User;

beforeEach(function () {
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Folio::route(__DIR__.'/resources/views/yet-more-pages');
});

test('implicit model binding', function () {
    $user = User::create([
        'name' => 'test-user-name',
    ]);

    $this->get('/users/'.$user->id)->assertSee('test-user-name');
});

test('may return a redirect response', function () {
    $user = User::create([
        'name' => 'test-user-name',
    ]);

    $this->get('/redirect')->assertRedirect('/users/'.$user->id);
});

test('may return a json response', function () {
    $this->get('/json')->assertJson([
        'user' => 1,
    ]);
});

test('may return a forbidden response', function () {
    $this->get('/forbidden')->assertForbidden();
});

test('may have dependencies', function () {
    $this->get('/counter')->assertSee('This page has been viewed 1 times.');

    $this->get('/counter')->assertSee('This page has been viewed 2 times.');

    $this->get('/counter')->assertSee('This page has been viewed 3 times.');
});

test('middleware precedence', function () {
    $this->get('/middleware')->assertSee('Content: middleware 1 middleware 2 view 3.');
});

test('fragments', function () {
    $this->get('/fragments')
        ->assertSee('This is the title.')
        ->assertSee('This is a fragment.');

    $this->get('/fragments', ['HX-Request' => 'fragment-name'])
        ->assertDontSee('This is the title.')
        ->assertSee('This is a fragment.');
});
