<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\Console\ListCommand;
use Laravel\Folio\Folio;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(fn () => ListCommand::resolveTerminalWidthUsing(function () {
    return 150;
}));

it('may not have routes', function () {
    $command = $this->artisan('folio:list');

    $command->expectsOutputToContain('Your application doesn\'t have any routes.');

    $command->assertOk();
});

it('may have routes', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment:id].blad…
          GET       /users/nuno ....................................................................................................... users/nuno.blade.php
          GET       /users/{id} ....................................................................................................... users/[id].blade.php

                                                                                                                                         Showing [12] routes


        EOF);
});

it('has the `--json` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--json' => true,
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toStartWith(<<<'EOF'
        [{"method":"GET","uri":"\/books","view":"books\/index.blade.php"},{"method":"GET","uri":"\/books\/{...book}\/detail
        EOF);
});

it('has the `--path` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment:id].blad…

                                                                                                                                          Showing [5] routes


        EOF);
});

it('has the `--except-path` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--except-path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /users/nuno ....................................................................................................... users/nuno.blade.php
          GET       /users/{id} ....................................................................................................... users/[id].blade.php

                                                                                                                                          Showing [7] routes


        EOF);
});

it('may not find routes with `--path` or `--except-path`', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'sylvie',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'


                                                                                                                                          Showing [0] routes


        EOF);
});

it('has the `--sort` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
        '--sort' => 'view',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment:id].blad…
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php

                                                                                                                                          Showing [5] routes


        EOF);
});

it('has the `--reverse` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
        '--reverse' => true,
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment:id].blad…
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php

                                                                                                                                          Showing [5] routes


        EOF);
});

test('multiple mounted directories', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    Folio::route(__DIR__.'/../resources/views/more-pages');

    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toBe(<<<'EOF'

          GET       /books ....................................................................... tests/Feature/resources/views/pages/books/index.blade.php
          GET       / ............................................................................. tests/Feature/resources/views/more-pages/index.blade.php
          GET       /books/{...book}/detail .................... tests/Feature/resources/views/pages/books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ..................... tests/Feature/resources/views/pages/categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /deleted-podcasts/{podcast} ........... tests/Feature/resources/views/pages/deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /flights ................................................................... tests/Feature/resources/views/pages/flights/index.blade.php
          GET       /non-routables/{nonRoutable} ......... tests/Feature/resources/views/pages/non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ............................................................. tests/Feature/resources/views/pages/podcasts/list.blade.php
          GET       /podcasts/{podcast} ........................... tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ... tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tes…
          GET       /users/nuno ................................................................... tests/Feature/resources/views/pages/users/nuno.blade.php
          GET       /users/{id} ................................................................... tests/Feature/resources/views/pages/users/[id].blade.php
          GET       /{...user} ................................................................ tests/Feature/resources/views/more-pages/[...User].blade.php
          GET       /{...user}/detail .................................................. tests/Feature/resources/views/more-pages/[...User]/detail.blade.php

                                                                                                                                         Showing [15] routes


        EOF);
});
