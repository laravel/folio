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
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ............................................................................................. dashboard › dashboard.blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /domain ................................................................................................... my-domain › domain.blade.php
          GET       /events/{event} ............................................................................................... events/[Event].blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/3 ......................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/3.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /posts/{lowerCase}/{upperCase}/{podcast}/{user:email}/show posts.show › posts/[lowerCase]/[UpperCase]/[Podcast]/[User-email]/show.bla…
          GET       /users/articles/{user:wrongColumn} ........................................ user.articles › users/articles/[User-wrong_column].blade.php
          GET       /users/movies/{user}/{movie} ............... users/movies/[.Tests.Feature.Fixtures.User]/[.Tests.Feature.Fixtures.Movie]/index.blade.php
          GET       /users/nuno .......................................................................................... users.nuno › users/nuno.blade.php
          GET       /users/{id} .......................................................................................... users.show › users/[id].blade.php

                                                                                                                                         Showing [19] routes


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
        [{"method":"GET","domain":null,"uri":"\/books","name":null,"view":"books\/index.blade.php"},{"method":"GET","domain":null,"uri":"\/books\/{...book}\/detail
        EOF);
});

it('has the `--path` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/3 ......................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/3.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…

                                                                                                                                          Showing [6] routes


        EOF);
});

it('has the `--name` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--name' => 'users',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /users/nuno .......................................................................................... users.nuno › users/nuno.blade.php
          GET       /users/{id} .......................................................................................... users.show › users/[id].blade.php

                                                                                                                                          Showing [2] routes


        EOF);
});

it('has the `--except-path` option', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--except-path' => 'podcasts',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /books ........................................................................................................... books/index.blade.php
          GET       /books/{...book}/detail ........................................................ books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ......................................................... categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ............................................................................................. dashboard › dashboard.blade.php
          GET       /domain ................................................................................................... my-domain › domain.blade.php
          GET       /events/{event} ............................................................................................... events/[Event].blade.php
          GET       /flights ....................................................................................................... flights/index.blade.php
          GET       /non-routables/{nonRoutable} ............................................. non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /posts/{lowerCase}/{upperCase}/{podcast}/{user:email}/show posts.show › posts/[lowerCase]/[UpperCase]/[Podcast]/[User-email]/show.bla…
          GET       /users/articles/{user:wrongColumn} ........................................ user.articles › users/articles/[User-wrong_column].blade.php
          GET       /users/movies/{user}/{movie} ............... users/movies/[.Tests.Feature.Fixtures.User]/[.Tests.Feature.Fixtures.Movie]/index.blade.php
          GET       /users/nuno .......................................................................................... users.nuno › users/nuno.blade.php
          GET       /users/{id} .......................................................................................... users.show › users/[id].blade.php

                                                                                                                                         Showing [13] routes


        EOF);
});

it('may not find routes with `--path` or `--except-path`', function () {
    $output = new BufferedOutput();

    Folio::route(__DIR__.'/../resources/views/pages');
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

    Folio::route(__DIR__.'/../resources/views/pages');
    $exitCode = Artisan::call('folio:list', [
        '--path' => 'podcasts',
        '--sort' => 'view',
    ], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments/3 ......................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/3.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php

                                                                                                                                          Showing [6] routes


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
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /podcasts/{podcast}/comments/{comment:id} podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tests.Feature.Fixtures.Comment-id].blad…
          GET       /podcasts/{podcast}/comments/3 ......................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/3.blade.php
          GET       /podcasts/{podcast}/comments ....................................... podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast} ............................................................... podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/list ................................................................................................. podcasts/list.blade.php
          GET       /deleted-podcasts/{podcast} ............................................... deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php

                                                                                                                                          Showing [6] routes


        EOF);
});

test('multiple mounted directories', function () {
    $output = new BufferedOutput();

    Folio::path(__DIR__.'/../resources/views/pages');
    Folio::path(__DIR__.'/../resources/views/more-pages');

    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       / .......................................................... more-pages.index › tests/Feature/resources/views/more-pages/index.blade.php
          GET       /books ....................................................................... tests/Feature/resources/views/pages/books/index.blade.php
          GET       /books/{...book}/detail .................... tests/Feature/resources/views/pages/books/[...Tests.Feature.Fixtures.Book]/detail.blade.php
          GET       /categories/{category} ..................... tests/Feature/resources/views/pages/categories/[.Tests.Feature.Fixtures.Category].blade.php
          GET       /dashboard ......................................................... dashboard › tests/Feature/resources/views/pages/dashboard.blade.php
          GET       /deleted-podcasts/{podcast} ........... tests/Feature/resources/views/pages/deleted-podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /domain ............................................................... my-domain › tests/Feature/resources/views/pages/domain.blade.php
          GET       /events/{event} ........................................................... tests/Feature/resources/views/pages/events/[Event].blade.php
          GET       /flights ................................................................... tests/Feature/resources/views/pages/flights/index.blade.php
          GET       /non-routables/{nonRoutable} ......... tests/Feature/resources/views/pages/non-routables/[.Tests.Feature.Fixtures.NonRoutable].blade.php
          GET       /podcasts/list ............................................................. tests/Feature/resources/views/pages/podcasts/list.blade.php
          GET       /podcasts/{podcast} ........................... tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast].blade.php
          GET       /podcasts/{podcast}/comments ... tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/index.blade.php
          GET       /podcasts/{podcast}/comments/3 ..... tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/3.blade.php
          GET       /podcasts/{podcast}/comments/{comment:id} tests/Feature/resources/views/pages/podcasts/[.Tests.Feature.Fixtures.Podcast]/comments/[.Tes…
          GET       /posts/{lowerCase}/{upperCase}/{podcast}/{user:email}/show posts.show › tests/Feature/resources/views/pages/posts/[lowerCase]/[UpperC…
          GET       /users/articles/{user:wrongColumn} .... user.articles › tests/Feature/resources/views/pages/users/articles/[User-wrong_column].blade.php
          GET       /users/movies/{user}/{movie} tests/Feature/resources/views/pages/users/movies/[.Tests.Feature.Fixtures.User]/[.Tests.Feature.Fixtures.M…
          GET       /users/nuno ...................................................... users.nuno › tests/Feature/resources/views/pages/users/nuno.blade.php
          GET       /users/{id} ...................................................... users.show › tests/Feature/resources/views/pages/users/[id].blade.php
          GET       /{...user} ................................................................ tests/Feature/resources/views/more-pages/[...User].blade.php
          GET       /{...user}/detail ......................... more-pages.user.detail › tests/Feature/resources/views/more-pages/[...User]/detail.blade.php

                                                                                                                                         Showing [22] routes


        EOF);
});

it('prefixes URIs', function (string $uri) {
    $output = new BufferedOutput();

    Folio::path(__DIR__.'/../resources/views/more-pages')->uri($uri);

    $exitCode = Artisan::call('folio:list', [], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toOutput(<<<'EOF'

          GET       /api ................................................................................................ more-pages.index › index.blade.php
          GET       /api/{...user} ..................................................................................................... [...User].blade.php
          GET       /api/{...user}/detail .............................................................. more-pages.user.detail › [...User]/detail.blade.php

                                                                                                                                          Showing [3] routes


        EOF);
})->with([
    'api',
    '/api',
    'api/',
    '/api/',
]);
