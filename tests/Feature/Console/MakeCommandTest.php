<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\Folio;
use Laravel\Folio\Support\Project;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    File::partialMock();
});

it('makes routes', function (string $name, string $path) {
    $this->artisan('folio:page', ['name' => $name])->assertOk();

    $path = resource_path('views/pages/'.$path);

    expect($path)->toBeFile()->and(file_get_contents($path))->toBe(
        <<<'PHP'
        <div>
            //
        </div>

        PHP
    );
})->with([
    ['index', 'index.blade.php'],
    ['chirps/index', 'chirps/index.blade.php'],
    ['chirps-index.blade.php', 'chirps-index.blade.php'],
    ['ChIrPs_index.blade.php', 'chirps_index.blade.php'],
    ['chirps/index.blade.php', 'chirps/index.blade.php'],
    ['chirps/[id].blade.php', 'chirps/[id].blade.php'],
    ['chirps/[...id].blade.php', 'chirps/[...id].blade.php'],
    ['chirps/[Chirp].blade.php', 'chirps/[Chirp].blade.php'],
    ['USERS/[User]/chirps/[Chirp]', 'users/[User]/chirps/[Chirp].blade.php'],
]);

afterEach(function () {
    collect([
        resource_path('views/pages'),
        resource_path('views/admin-pages'),
        resource_path('views/site-pages'),
    ])->each(function (string $path) {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    });
});

it('makes a page in a selected mounted path', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'))->uri('/admin');
    Folio::path(resource_path('views/site-pages'))->uri('/site')->domain('example.com');

    $this->artisan('folio:page', ['name' => 'dashboard', '--force' => true])
        ->expectsChoice(
            'Which Folio path should the page be created in?',
            resource_path('views/site-pages'),
            [
                resource_path('views/admin-pages') => Project::relativePathOf(resource_path('views/admin-pages')).' (URI: /admin)',
                resource_path('views/site-pages') => Project::relativePathOf(resource_path('views/site-pages')).' (URI: /site, Domain: example.com)',
            ],
        )
        ->assertOk();

    expect(resource_path('views/site-pages/dashboard.blade.php'))->toBeFile();
});

it('accepts an absolute mounted path option', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));
    Folio::path(resource_path('views/site-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => resource_path('views/site-pages'),
    ])->assertOk();

    expect(resource_path('views/site-pages/dashboard.blade.php'))->toBeFile();
});

it('accepts a project relative mounted path option', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));
    Folio::path(resource_path('views/site-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => Project::relativePathOf(resource_path('views/site-pages')),
    ])->assertOk();

    expect(resource_path('views/site-pages/dashboard.blade.php'))->toBeFile();
});

it('accepts a mounted path option with a trailing separator', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));
    Folio::path(resource_path('views/site-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => resource_path('views/site-pages').'/',
    ])->assertOk();

    expect(resource_path('views/site-pages/dashboard.blade.php'))->toBeFile();
});

it('accepts either directory separator in a relative mounted path option', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));
    Folio::path(resource_path('views/site-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => str_replace('/', '\\', Project::relativePathOf(resource_path('views/site-pages'))),
    ])->assertOk();

    expect(resource_path('views/site-pages/dashboard.blade.php'))->toBeFile();
});

it('automatically uses a single mounted path', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'))->uri('/admin');

    $this->artisan('folio:page', ['name' => 'dashboard'])->assertOk();

    expect(resource_path('views/admin-pages/dashboard.blade.php'))->toBeFile();
});

it('treats duplicate registrations of a path as one generator target', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'))->uri('/admin');
    Folio::path(resource_path('views/admin-pages'))->uri('/staff')->domain('example.com');

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => resource_path('views/admin-pages'),
    ])->assertOk();

    expect(resource_path('views/admin-pages/dashboard.blade.php'))->toBeFile();
});

it('uses the first mounted path non-interactively', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);
    File::makeDirectory(resource_path('views/site-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));
    Folio::path(resource_path('views/site-pages'));

    $exitCode = Artisan::call('folio:page', [
        'name' => 'dashboard',
        '--no-interaction' => true,
    ], new BufferedOutput);

    expect($exitCode)->toBe(0)
        ->and(resource_path('views/admin-pages/dashboard.blade.php'))->toBeFile();
});

it('rejects an unknown mounted path', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => 'workbench/resources/views/missing-pages',
    ]);
})->throws(InvalidArgumentException::class, 'is not one of the configured Folio paths');

it('rejects a mount option when no Folio paths are configured', function () {
    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => resource_path('views/pages'),
    ]);
})->throws(InvalidArgumentException::class, 'may only be used when a Folio path is configured');

it('rejects an empty mount option', function () {
    File::makeDirectory(resource_path('views/admin-pages'), recursive: true, force: true);

    Folio::path(resource_path('views/admin-pages'));

    $this->artisan('folio:page', [
        'name' => 'dashboard',
        '--mount' => '',
    ]);
})->throws(InvalidArgumentException::class, 'is not one of the configured Folio paths');
