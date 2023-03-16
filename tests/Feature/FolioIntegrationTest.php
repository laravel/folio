<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Podcast;

beforeEach(function () {
    $_SERVER['__folio_users_middleware'] = false;
    $_SERVER['__folio_flights_middleware'] = false;
    $_SERVER['__folio_flights_inline_middleware'] = false;

    Schema::create('podcasts', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('comments', function ($table) {
        $table->id();
        $table->foreignId('podcast_id');
        $table->string('content');
        $table->timestamps();
    });

    Folio::route(__DIR__.'/resources/views/pages', middleware: [
        '/users/*' => [
            function ($request, $next) {
                $_SERVER['__folio_users_middleware'] = true;
                return $next($request);
            }
        ],
        '/flights/*' => [
            function ($request, $next) {
                $_SERVER['__folio_flights_middleware'] = true;
                return $next($request);
            }
        ],
    ]);
});

test('pages can be rendered and middleware invoked', function () {
    $response = $this->get('/users/Taylor');

    $response->assertSee('Hello, Taylor');

    $this->assertTrue($_SERVER['__folio_users_middleware']);
});

test('inline middleware are invoked', function () {
    $response = $this->get('/flights');

    $response->assertSee('Flight Index');

    $this->assertTrue($_SERVER['__folio_flights_middleware']);
    $this->assertTrue($_SERVER['__folio_flights_inline_middleware']);
});

test('middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/users/1');

    $this->assertCount(1, $middleware);
    $this->assertTrue($middleware[0] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});

test('inline middleware can be retrieved for a given uri', function () {
    $middleware = Folio::middlewareFor('/flights');

    $this->assertCount(2, $middleware);
    $this->assertTrue($middleware[0] instanceof Closure);
    $this->assertTrue($middleware[1] instanceof Closure);

    $this->assertEmpty(Folio::middlewareFor('/missing/1'));
});

test('implicit bindings are resolved', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id)->assertSee('test-podcast-name');
});

test('child implicit bindings are scoped to the parent if field is present', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ])->fresh();

    $comment = $podcast->comments()->create([
        'content' => 'test-comment-content',
    ])->fresh();

    $secondPodcast = Podcast::create([
        'name' => 'another-podcast-name',
    ])->fresh();

    $secondComment = $secondPodcast->comments()->create([
        'content' => 'another-comment-content',
    ])->fresh();

    $this->get('/podcasts/'.$podcast->id.'/comments/'.$secondComment->id)
            ->assertNotFound();

    $this->get('/podcasts/'.$podcast->id.'/comments/'.$comment->id)
            ->assertSee('test-comment-content');
});

test('soft deletable bindings are not resolved if not allowed', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $podcast->delete();

    $this->get('/podcasts/'.$podcast->id)->assertNotFound();
});

test('soft deletable bindings are resolved if allowed', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $podcast->delete();

    $this->get('/deleted-podcasts/'.$podcast->id)->assertSee('test-podcast-name');
});

test('enums can be injected', function () {
    $response = $this->get('/categories/posts');

    $response->assertSee('posts');
});

test('not found error is generated if enum value is not valid', function () {
    $response = $this->get('/categories/missing-category');

    $response->assertNotFound();
});
