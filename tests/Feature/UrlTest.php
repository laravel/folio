<?php

use Tests\Feature\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Exceptions\NameNotFoundException;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Book;
use Tests\Feature\Fixtures\Category;
use Tests\Feature\Fixtures\Comment;
use Tests\Feature\Fixtures\Podcast;

use function Laravel\Folio\url;

beforeEach(function () {
    Folio::route(__DIR__ . '/resources/views/pages', names: [
        'flights.index' => 'flights/index.blade.php',
        'users.show' => 'users/[id].blade.php',
        'categories.show' => 'categories/[.Tests.Feature.Fixtures.Category].blade.php',
        'podcasts.comments.index' => 'podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php',
        'podcasts.comments.show' => 'podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment:id].blade.php',
        'books.detail' => 'books/[...Tests.Feature.Fixtures.Book]/detail.blade.php',
        'books.show' => 'books/[id:$theId]',
        'books.many' => 'books/[...id:$theIds]',
    ]);
});

test('generate url for basic page', function () {
    $url = url('flights.index');

    expect($url)->toBe('/flights');
});

test('generate url with simple parameter', function () {
    $url = url('users.show', ['id' => 1]);

    expect($url)->toBe('/users/1');
});

test('generate url with UrlRoutable parameter', function () {
    $user = new User(['id' => 1]);
    $url = url('users.show', ['id' => $user]);

    expect($url)->toBe('/users/1');
});

test('generate url with BackedEnum parameter', function () {
    $url = url('categories.show', ['category' => Category::Post]);

    expect($url)->toBe('/categories/posts');
});

test('generate url with parameter mid-path', function () {
    $podcast = new Podcast(['id' => 1]);
    $url = url('podcasts.comments.index', ['podcast' => $podcast]);

    expect($url)->toBe('/podcasts/1/comments');
});

test('generate url with multiple parameters', function () {
    $podcast = new Podcast(['id' => 1]);
    $comment = new Comment(['id' => 2]);
    $url = url('podcasts.comments.show', ['podcast' => $podcast, 'comment' => $comment]);

    expect($url)->toBe('/podcasts/1/comments/2');
});

test('generate url with variadic segment', function () {
    $books = [
        new Book(['id' => 1]),
        new Book(['id' => 2]),
        new Book(['id' => 3]),
    ];

    $url = url('books.detail', ['books' => $books]);

    expect($url)->toBe('/books/1/2/3/detail');
});

test('generate url with variadic segment passed as a Collection', function () {
    $books = collect([
        new Book(['id' => 1]),
        new Book(['id' => 2]),
        new Book(['id' => 3]),
    ]);

    $url = url('books.detail', ['books' => $books]);

    expect($url)->toBe('/books/1/2/3/detail');
});

test('generate url with variadic segment passing a single value', function () {
    $book = new Book(['id' => 1]);
    $url = url('books.detail', ['books' => $book]);

    expect($url)->toBe('/books/1/detail');
});

test('generate url with segment with custom variable', function () {
    $url = url('books.show', ['theId' => 1]);

    expect($url)->toBe('/books/1');
});

test('generate url with variadic segment with custom variable', function () {
    $url = url('books.many', ['theIds' => [1, 2, 3]]);

    expect($url)->toBe('/books/1/2/3');
});

it('throws exception when a url parameter is missing', function () {
    url('users.show');
})->throws(
    UrlGenerationException::class,
    'Missing required parameter for [Path: users/[id]] [Missing parameter: id]'
);

it('throws exception when generating url for unknown name', function () {
    url('non-existent');
})->throws(
    NameNotFoundException::class,
    'Page [non-existent] not found.'
);
