<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\Console\ListCommand;
use Laravel\Folio\Folio;
use Symfony\Component\Console\Output\BufferedOutput;
use function Orchestra\Testbench\workbench_path;

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

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ......................................................................................................... dashboard.blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /domain ............................................................................................................... domain.blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /users/nuno ....................................................................................................... users/nuno.blade.php
          GET       /users/{id} ....................................................................................................... users/[id].blade.php

                                                                                                                                         Showing [14] routes


        EOF);
});

it('has the `--json` option', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--json' => true,
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toStartWith(<<<'EOF'
        [{"method":"GET","domain":null,"uri":"\/books","view":"books\/index.blade.php"},{"method":"GET","domain":null,"uri":"\/books\/{...book}\/detail
        EOF);
});

it('has the `--path` option', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…

                                                                                                                                          Showing [5] routes


        EOF);
});

it('has the `--except-path` option', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--except-path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ......................................................................................................... dashboard.blade.php
          GET       /domain ............................................................................................................... domain.blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /users/nuno ....................................................................................................... users/nuno.blade.php
          GET       /users/{id} ....................................................................................................... users/[id].blade.php

                                                                                                                                          Showing [9] routes


        EOF);
});

it('may not find routes with `--path` or `--except-path`', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'sylvie',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'


                                                                                                                                          Showing [0] routes


        EOF);
});

it('has the `--sort` option', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
        '--sort' => 'view',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php

                                                                                                                                          Showing [5] routes


        EOF);
});

it('has the `--reverse` option', function () {
    $output = new BufferedOutput();

    Folio::route(workbench_path('/resources/views/pages'));
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
        '--reverse' => true,
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php

                                                                                                                                          Showing [5] routes


        EOF);
});

test('multiple mounted directories', function () {
    $output = new BufferedOutput();

    Folio::path(workbench_path('/resources/views/pages'));
    Folio::path(workbench_path('/resources/views/more-pages'));

    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       / ................................................................................. workbench/resources/views/more-pages/index.blade.php
          GET       /books ........................................................................... workbench/resources/views/pages/books/index.blade.php
          GET       /books/{...book}/detail ........................ workbench/resources/views/pages/books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................... workbench/resources/views/pages/categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ......................................................................... workbench/resources/views/pages/dashboard.blade.php
          GET       /deleted-podcasts/{podcast} ............... workbench/resources/views/pages/deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /domain ............................................................................... workbench/resources/views/pages/domain.blade.php
          GET       /flights ....................................................................... workbench/resources/views/pages/flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............. workbench/resources/views/pages/non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ................................................................. workbench/resources/views/pages/podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................... workbench/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....... workbench/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} workbench/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.F…
          GET       /users/nuno ....................................................................... workbench/resources/views/pages/users/nuno.blade.php
          GET       /users/{id} ....................................................................... workbench/resources/views/pages/users/[id].blade.php
          GET       /{...user} .................................................................... workbench/resources/views/more-pages/[...User].blade.php
          GET       /{...user}/detail ...................................................... workbench/resources/views/more-pages/[...User]/detail.blade.php

                                                                                                                                         Showing [17] routes


        EOF);
});
