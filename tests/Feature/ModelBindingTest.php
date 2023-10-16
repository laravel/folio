<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Laravel\Folio\Pipeline\PotentiallyBindablePathSegment;
use Tests\Feature\Fixtures\Event;
use Tests\Feature\Fixtures\Podcast;
use Tests\Feature\Fixtures\User;

beforeEach(function () {
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });

    Schema::create('movies', function ($table) {
        $table->id();
        $table->foreignId('user_id');
        $table->string('name');
        $table->timestamps();
    });

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

    Schema::create('events', function ($table) {
        $table->id();
        $table->timestamps();
    });

    Folio::route(__DIR__.'/resources/views/pages');
});

afterEach(function () {
    PotentiallyBindablePathSegment::resolveUrlRoutableNamespacesUsing(null);
});

test('implicit model bindings are resolved', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id)->assertSee('test-podcast-name');
});

test('implicit model bindings are resolved even if model base class conflicts with facade name', function () {
    PotentiallyBindablePathSegment::resolveUrlRoutableNamespacesUsing(function () {
        return ['\Tests\Feature\Fixtures'];
    });

    $event = Event::create();

    $this->get('/events/'.$event->id)->assertSee('Tests\Feature\Fixtures\Event: 1.');
});

test('implicit model bindings are accessible via middleware', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id);

    expect($_SERVER['__folio_podcasts_inline_middleware'])->id->toBe($podcast->id);
});

test('implicit model bindings are accessible via @php tags', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id);

    expect($_SERVER['__folio_podcasts_php_blade_block'])->id->toBe($podcast->id);
});

test('not found error is thrown if implicit binding can not be resolved', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/missing-id')->assertNotFound();
});

test('regular routes may be used if implicit binding can not be resolved', function () {
    $this->get('/podcasts/list')
        ->assertStatus(200)
        ->assertSee('podcast');

    $this->get('/podcasts/1')->assertNotFound();

    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id)->assertSee('test-podcast-name');

    $this->get('/podcasts/'.$podcast->id.'/comments/1')
        ->assertNotFound();

    $podcast->comments()->create([
        'content' => 'test-comment-content-1',
    ])->fresh();

    $this->get('/podcasts/'.$podcast->id.'/comments/1')
        ->assertStatus(200)
        ->assertSee('test-comment-content-1');

    $podcast->comments()->create([
        'content' => 'test-comment-content-2',
    ])->fresh();

    $this->get('/podcasts/'.$podcast->id.'/comments/2')
        ->assertStatus(200)
        ->assertSee('test-comment-content-2');

    $this->get('/podcasts/'.$podcast->id.'/comments/3')
        ->assertStatus(200)
        ->assertSee('literal-comment');

    $podcast->comments()->create([
        'content' => 'test-comment-content-3',
    ])->fresh();

    $this->get('/podcasts/'.$podcast->id.'/comments/3')
        ->assertStatus(200)
        ->assertSee('literal-comment');
});

test('child implicit bindings are resolved', function () {
    $user = User::create([
        'name' => 'test-name',
        'email' => 'test-email@gmail.com',
    ]);

    $user->movies()->create([
        'name' => 'test-movie-name',
    ]);

    $movie = $user->movies()->create([
        'name' => 'test-movie-name-2',
    ]);

    $this->get('/users/movies/'.$user->id.'/'.$movie->id)->assertSee(
        'test-name: test-movie-name-2.',
    );
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

    //Doesn't belong to parent...
    $this->get('/podcasts/'.$podcast->id.'/comments/'.$secondComment->id)
        ->assertNotFound();

    //Belongs to parent...
    $this->get('/podcasts/'.$podcast->id.'/comments/'.$comment->id)
        ->assertSee('test-comment-content');
});

test('soft deletable bindings are not resolved if not allowed by page', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $podcast->delete();

    $this->get('/podcasts/'.$podcast->id)->assertNotFound();
});

test('soft deletable bindings are resolved if allowed by page', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $podcast->delete();

    $this->get('/deleted-podcasts/'.$podcast->id)->assertSee('test-podcast-name');
});

test('enums are properly injected into page', function () {
    $response = $this->get('/categories/posts');

    $response->assertSee('posts');
});

test('not found error is generated if enum value is not valid', function () {
    $response = $this->get('/categories/missing-category');

    $response->assertNotFound();
});

it('throws exception when attempting to bind to class that is not routable', function () {
    $this->withoutExceptionHandling();

    $this->get('/non-routables/1')->assertNotFound();
})->throws(
    Exception::class,
    'Folio route attempting to bind to class [Tests\Feature\Fixtures\NonRoutable], but it does not implement the UrlRoutable interface.',
);
