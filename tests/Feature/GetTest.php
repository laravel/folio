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

test('implicit model bindings are resolved', function () {
    $user = User::create([
        'name' => 'test-user-name',
    ]);

    $this->get('/users/'.$user->id)->assertSee('test-user-name');
});

test('may return a custom response', function () {
    $user = User::create([
        'name' => 'test-user-name',
    ]);

    $this->get('/custom-response')->assertRedirect('/users/'.$user->id);
});
