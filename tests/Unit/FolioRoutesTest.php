<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\FolioManager;
use Laravel\Folio\FolioRoutes;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\Feature\Fixtures\Category;
use Tests\Feature\Fixtures\Podcast;

beforeEach(function () {
    Schema::create('podcasts', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Podcast::create([
        'name' => 'test-podcast-name-1',
    ]);

    Podcast::create([
        'name' => 'test-podcast-name-2',
    ]);
});

it('may have routes', function (string $name, array $scenario) {
    [$mountPath, $viewPath, $arguments, $expectedRoute] = $scenario;

    $arguments = collect($arguments)->map(fn ($argument) => value($argument))->all();

    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        $name => [$mountPath, $viewPath],
    ], true);

    expect($names->has($name))->toBeTrue()
        ->and($names->get($name, $arguments, false))->toBe($expectedRoute);
})->with(fn () => collect([
    'podcasts.index' => ['podcasts/index.blade.php', [], '/podcasts'],
    'podcasts.show-by-id' => ['podcasts/[id].blade.php', ['id' => 1], '/podcasts/1'],
    'podcasts.show-by-name' => ['podcasts/[name].blade.php', ['name' => 'Taylor'], '/podcasts/Taylor'],
    'podcasts.show-by-slug' => ['podcasts/[slug].blade.php', ['slug' => 'nuno'], '/podcasts/nuno'],
    'podcasts.show-by-slug-and-id' => ['podcasts/[slug]/[id].blade.php', ['slug' => 'nuno', 'id' => 1], '/podcasts/nuno/1'],
    'podcasts.show-by-model' => ['podcasts/[Podcast].blade.php', ['podcast' => fn () => Podcast::first()], '/podcasts/1'],
    'podcasts.show-by-model-fqn' => ['podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php', ['podcast' => fn () => Podcast::first()], '/podcasts/1'],
    'podcasts.show-by-model-name-1' => ['podcasts/[Podcast:name].blade.php', ['podcast' => fn () => Podcast::first()], '/podcasts/test-podcast-name-1'],
    'podcasts.show-by-model-name-2' => ['podcasts/[Podcast-name].blade.php', ['podcast' => fn () => Podcast::first()], '/podcasts/test-podcast-name-1'],
    'podcasts.show-by-backed-enum' => ['podcasts/[Category].blade.php', ['category' => Category::Post], '/podcasts/posts'],
    'podcasts.show-by-id-with-nested-page' => ['podcasts/[id]/stats.blade.php', ['id' => 1], '/podcasts/1/stats'],
    'podcasts.stats' => ['podcasts/stats.blade.php', [], '/podcasts/stats'],
    'podcasts.many-by-id' => ['podcasts/[...id].blade.php', ['ids' => [1, 2, 3]], '/podcasts/1/2/3'],
    'podcasts.many-by-name' => ['podcasts/[...name].blade.php', ['names' => ['Taylor', 'Nuno']], '/podcasts/Taylor/Nuno'],
    'podcasts.many-by-slug' => ['podcasts/[...slug].blade.php', ['slugs' => ['nuno', 'taylor']], '/podcasts/nuno/taylor'],
    'podcasts.many-by-slug-and-id' => ['podcasts/[...slug]/[...id].blade.php', ['slugs' => ['nuno', 'taylor'], 'ids' => [1, 2]], '/podcasts/nuno/taylor/1/2'],
    'podcasts.many-by-model' => ['podcasts/[...Podcast].blade.php', ['podcasts' => fn () => Podcast::all()], '/podcasts/1/2'],
    'podcasts.many-by-model-fqn' => ['podcasts/[...Tests.Feature.Fixtures.Podcast].blade.php', ['podcasts' => fn () => Podcast::all()], '/podcasts/1/2'],
    'podcasts.many-by-model-name-1' => ['podcasts/[...Podcast:name].blade.php', ['podcasts' => fn () => Podcast::all()], '/podcasts/test-podcast-name-1/test-podcast-name-2'],
    'podcasts.many-by-model-name-2' => ['podcasts/[...Podcast-name].blade.php', ['podcasts' => fn () => Podcast::all()], '/podcasts/test-podcast-name-1/test-podcast-name-2'],
    'podcasts.many-by-backed-enum' => ['podcasts/[...Category].blade.php', ['categories' => [Category::Post, Category::Video]], '/podcasts/posts/video'],
    'podcasts.many-by-id-with-nested-page' => ['podcasts/[...id]/stats.blade.php', ['ids' => [1, 2, 3]], '/podcasts/1/2/3/stats'],
])->map(function (array $value) {
    $mountPath = 'resources/views/pages';

    [$viewRelativePath, $arguments, $expectedRoute] = $value;

    return [$mountPath, $mountPath.'/'.$viewRelativePath, $arguments, $expectedRoute];
})->mapWithKeys(fn (array $value, string $key) => [$key => [$key, $value]])->toArray());

it('may not have routes', function () {
    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        'podcasts.index' => ['resources/views/pages', 'resources/views/pages/podcasts/index.blade.php'],
    ], true);

    expect($names->has('podcasts.show'))->toBeFalse()
        ->and(fn () => $names->get('podcasts.show', [], false))->toThrow(RouteNotFoundException::class);
});

it('can not have missing parameters', function () {
    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        'podcasts.show' => ['resources/views/pages', 'resources/views/pages/podcasts/[id].blade.php'],
    ], true);

    expect(fn () => $names->get('podcasts.show', [], false))
        ->toThrow(UrlGenerationException::class, 'Missing required parameter for [Path: resources/views/pages/podcasts/[id]] [Missing parameter: id]');
});
