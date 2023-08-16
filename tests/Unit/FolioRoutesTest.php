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
        $name => [
            'mountPath' => $mountPath,
            'path' => $viewPath,
            'baseUri' => '/',
            'domain' => null,
        ],
    ], true);

    expect($names->has($name))->toBeTrue()
        ->and($names->get($name, $arguments, false))->toBe($expectedRoute);
})->with(fn () => collect([
    'podcasts.index' => ['podcasts/index.blade.php', [], '/podcasts'],
    'podcasts.index-with-query-parameters' => ['podcasts/index.blade.php', ['page' => 1], '/podcasts?page=1'],
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
    'podcasts.stats-with-query-parameters' => ['podcasts/stats.blade.php', ['page' => 1, 'lowerCase' => 'lowerCaseKeyValue', 'Upper_case-key' => 'Upper_caseKeyValue'], '/podcasts/stats?page=1&lowerCase=lowerCaseKeyValue&Upper_case-key=Upper_caseKeyValue'],
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
    'articles.query-parameter' => ['articles.blade.php', ['page' => 1], '/articles?page=1'],
    'articles.query-parameters' => ['articles.blade.php', ['page' => 1, 'lowerCase' => 'lowerCaseKeyValue', 'Upper_case-key' => 'Upper_caseKeyValue'], '/articles?page=1&lowerCase=lowerCaseKeyValue&Upper_case-key=Upper_caseKeyValue'],
    'articles.query-array-parameter' => ['articles.blade.php', ['page' => [1, 2]], '/articles?page%5B0%5D=1&page%5B1%5D=2'],
])->map(function (array $value) {
    $mountPath = 'resources/views/pages';

    [$viewRelativePath, $arguments, $expectedRoute] = $value;

    return [$mountPath, $mountPath.'/'.$viewRelativePath, $arguments, $expectedRoute];
})->mapWithKeys(fn (array $value, string $key) => [$key => [$key, $value]])->toArray());

it('may have absolute routes', function (string $name, array $scenario) {
    [$mountPath, $viewPath, $domain, $arguments, $expectedRoute] = $scenario;

    $arguments = collect($arguments)->map(fn ($argument) => value($argument))->all();

    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        $name => [
            'mountPath' => $mountPath,
            'path' => $viewPath,
            'baseUri' => '/',
            'domain' => $domain,
        ],
    ], true);

    expect($names->has($name))->toBeTrue()
        ->and($names->get($name, $arguments, true))->toBe($expectedRoute);
})->with(fn () => collect([
    'podcasts.index' => ['podcasts/index.blade.php', 'domain.com', [], 'http://domain.com/podcasts'],
    'podcasts.show' => ['podcasts/[id].blade.php', 'domain.com', ['id' => 1], 'http://domain.com/podcasts/1'],
    'podcasts.show-by-account-and-name' => ['podcasts/[name].blade.php', '{account}.domain.com', ['account' => 'taylor', 'name' => 'Taylor'], 'http://taylor.domain.com/podcasts/Taylor'],
])->map(function (array $value) {
    $mountPath = 'resources/views/pages';

    [$viewRelativePath, $domain, $arguments, $expectedRoute] = $value;

    return [$mountPath, $mountPath.'/'.$viewRelativePath, $domain, $arguments, $expectedRoute];
})->mapWithKeys(fn (array $value, string $key) => [$key => [$key, $value]])->toArray());

test('precedence', function () {
    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        'podcasts.show' => [
            'mountPath' => 'resources/views/pages',
            'path' => 'resources/views/pages/podcasts/[id].blade.php',
            'baseUri' => '/',
            'domain' => null,
        ],
    ], true);

    expect(fn () => $names->get('podcasts.show', ['id' => '{name}', 'name' => 'foo'], false))
        ->toThrow(UrlGenerationException::class, 'Missing required parameter for [Route: podcasts.show] [URI: /podcasts/{name}] [Missing parameter: name].');
});

it('may not have routes', function () {
    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        'podcasts.index' => [
            'mountPath' => 'resources/views/pages',
            'path' => 'resources/views/pages/podcasts/index.blade.php',
            'baseUri' => '/',
            'domain' => null,
        ],
    ], true);

    expect($names->has('podcasts.show'))->toBeFalse()
        ->and(fn () => $names->get('podcasts.show', [], false))->toThrow(RouteNotFoundException::class);
});

it('can not have missing parameters', function () {
    $names = new FolioRoutes(Mockery::mock(FolioManager::class), '', [
        'podcasts.show' => [
            'mountPath' => 'resources/views/pages',
            'path' => 'resources/views/pages/podcasts/[id].blade.php',
            'baseUri' => '/',
            'domain' => null,
        ],
    ], true);

    expect(fn () => $names->get('podcasts.show', [], false))
        ->toThrow(UrlGenerationException::class, 'Missing required parameter [id] for path [resources/views/pages/podcasts/[id]].');
});
