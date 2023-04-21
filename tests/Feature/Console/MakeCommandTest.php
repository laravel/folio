<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::partialMock();
});

it('makes routes', function (string $name, string $path) {
    $this->artisan('folio:make', ['name' => $name])->assertOk();

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
    ['chirps_index.blade.php', 'chirps_index.blade.php'],
    ['chirps/index.blade.php', 'chirps/index.blade.php'],
    ['chirps/[Chirp].blade.php', 'chirps/[Chirp].blade.php'],
]);

afterEach(function () {
    collect([
        resource_path('views/pages'),
    ])->each(function (string $path) {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    });
});
