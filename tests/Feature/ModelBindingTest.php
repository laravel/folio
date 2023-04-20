<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Folio;
use Tests\Feature\Fixtures\Podcast;

beforeEach(function () {
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

    Folio::route(__DIR__.'/resources/views/pages');
});

test('implicit model bindings are resolved', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/'.$podcast->id)->assertSee('test-podcast-name');
});

test('not found error is thrown if implicit binding can not be resolved', function () {
    $podcast = Podcast::create([
        'name' => 'test-podcast-name',
    ]);

    $this->get('/podcasts/missing-id')->assertNotFound();
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
