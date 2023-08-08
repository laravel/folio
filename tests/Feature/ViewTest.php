<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Book;
use Tests\Feature\Fixtures\User;
use function Orchestra\Testbench\workbench_path;

it('may have blade php blocks', function () {
    Folio::route(workbench_path('/resources/views/pages'));

    $response = $this->get('/users/nuno');

    $response->assertSee('Hello, Nuno Maduro from PHP block.');
});

it('blade php blocks are only executed when rendering the view', function () {
    $_SERVER['__folio_rendered_count'] = 0;

    Folio::route(workbench_path('/resources/views/pages'));

    $this->get('/users/nuno');
    $response = $this->get('/users/nuno');

    $response->assertSee('Rendered [2] time from PHP block.');
});

it('may have blade php blocks with authorization logic', function () {
    Folio::route(workbench_path('/resources/views/pages'));

    Schema::create('users', function ($table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('books', function ($table) {
        $table->id();
        $table->string('title');
        $table->foreignId('user_id');
        $table->timestamps();
    });

    $user = User::create();

    Book::create([
        'title' => 'test-book-title',
        'user_id' => $user->id,
    ]);

    Gate::define('view-books', fn () => true);
    $this->actingAs($user)->get('/books')->assertStatus(200);

    Gate::define('view-books', fn () => false);
    $this->actingAs($user)->get('/books')->assertStatus(403);
});
