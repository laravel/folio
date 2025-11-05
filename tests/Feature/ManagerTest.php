<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Folio\Events\ViewMatched;
use Laravel\Folio\Folio;
use Laravel\Folio\Pipeline\MatchedView;
use Tests\Feature\Fixtures\Http\Middleware\WithTerminableMiddleware;

afterEach(function () {
    Folio::renderUsing(null);
});

it('registers routes', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $response = $this->get('/users/Taylor');

    $response->assertStatus(200)->assertSee('Hello, Taylor');
});

it('requires a valid path to register routes', function () {
    Folio::route('non-existent-path');
})->throws(InvalidArgumentException::class);

it('registers routes with a custom URI', function (string $uri) {
    Folio::route(__DIR__.'/resources/views/pages')->uri($uri);

    $response = $this->get("$uri/users/Taylor");

    $response->assertSee('Hello, Taylor');
})->with(['/', '/custom-uri']);

it('registers routes with middleware', function () {
    Route::get('login', fn () => 'login')->name('login');
    Folio::route(__DIR__.'/resources/views/pages')->middleware(['*' => ['auth']]);

    $response = $this->get('/users/Taylor');

    $response->assertRedirect('login');
});

it('registers routes with custom render callback', function () {
    Folio::renderUsing(function (Request $request, MatchedView $view) {
        return response([
            $view->path,
            $view->data,
            $view->mountPath,
        ]);
    });

    Folio::route(__DIR__.'/resources/views/pages');

    $response = $this->get('/users/Taylor');
    [$path, $data, $mountPath] = $response->json();

    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
    $mountPath = str_replace(DIRECTORY_SEPARATOR, '/', $mountPath);

    expect($path)->toEndWith('/resources/views/pages/users/[id].blade.php')
        ->and($data)->toBe(['id' => 'Taylor'])
        ->and($mountPath)->toEndWith('/resources/views/pages');
});

it('fires view matched event on route', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $events = Event::fake(ViewMatched::class);

    $response = $this->get('/users/Taylor');

    $response->assertOk();

    $events->assertDispatched(ViewMatched::class, function ($event) {
        return str_ends_with(str_replace(DIRECTORY_SEPARATOR, '/', $event->matchedView->path), '/resources/views/pages/users/[id].blade.php');
    });
});

it('doesn\'t fire view matched event on 404', function () {
    Folio::route(__DIR__.'/resources/views/pages');

    $events = Event::fake(ViewMatched::class);

    $response = $this->get('/invalid-route');

    $response->assertNotFound();

    $events->assertNotDispatched(ViewMatched::class);
});

it('registers routes with domain', function (?string $domain, string $host, int $status) {
    if ($domain) {
        Folio::domain($domain)->path(__DIR__.'/resources/views/pages');
    } else {
        Folio::route(__DIR__.'/resources/views/pages');
    }

    $response = $this->get("https://$host/users/Taylor");

    $response->assertStatus($status);
})->with([
    [null, 'domain.com', 200],
    [null, 'another-domain.com', 200],
    [null, 'sub.domain.com', 200],
    [null, 'another-sub.domain.com', 200],

    ['domain.com', 'domain.com', 200],
    ['domain.com', 'another-domain.com', 404],
    ['domain.com', 'sub.domain.com', 404],
    ['domain.com', 'another-sub.domain.com', 404],

    ['sub.domain.com', 'domain.com', 404],
    ['sub.domain.com', 'another-domain.com', 404],
    ['sub.domain.com', 'sub.domain.com', 200],
    ['sub.domain.com', 'another-sub.domain.com', 404],
]);

it('registers routes with segments in domain', function (?string $domain, string $host, string $domainValue, string $subDomainValue) {
    Folio::domain($domain)->path(__DIR__.'/resources/views/pages');

    $response = $this->get("https://$host/domain");

    $response->assertStatus(200)
        ->assertSee("The domain is: $domainValue.")
        ->assertSee("The sub-domain is: $subDomainValue.");
})->with([
    ['domain.com', 'domain.com', 'none', 'none'],
    ['sub.domain.com', 'sub.domain.com', 'none', 'none'],
    ['{domain}.com', 'domain-value.com', 'domain-value', 'none'],
    ['{subDomain}.domain.com', 'sub-domain-value.domain.com', 'none', 'sub-domain-value'],
    ['sub.{domain}.com', 'sub.domain-value.com', 'domain-value', 'none'],
    ['{subDomain}.{domain}.com', 'sub-domain-value.domain-value.com', 'domain-value', 'sub-domain-value'],
]);

describe('precedence of routes does not matter', function () {
    test('declaring path that matches first', function () {
        Folio::path(__DIR__.'/resources/views/even-more-pages');
        Folio::path(__DIR__.'/resources/views/pages');

        $response = $this->get('/profile');

        $response->assertStatus(200)->assertSee('My profile');
    });

    test('declaring path that matches last', function () {
        Folio::path(__DIR__.'/resources/views/pages');
        Folio::path(__DIR__.'/resources/views/even-more-pages');

        $response = $this->get('/profile');

        $response->assertStatus(200)->assertSee('My profile');
    });
});

describe('precedence of domains does not matter', function () {
    test('declaring "domain.com" first', function () {
        Folio::domain('domain.com')->path(__DIR__.'/resources/views/pages');
        Folio::domain('another-domain.com')->path(__DIR__.'/resources/views/pages');

        $response = $this->get('https://domain.com/dashboard');

        $response->assertStatus(200);
    });

    test('declaring "domain.com" last', function () {
        Folio::domain('another-domain.com')->uri('/app')->path(__DIR__.'/resources/views/pages');
        Folio::domain('domain.com')->uri('/app')->path(__DIR__.'/resources/views/pages');

        $response = $this->get('https://domain.com/app/dashboard');

        $response->assertStatus(200);
    });
});

test('only the middleware of match mount path gets used on duplicate mount paths', function () {
    $_SERVER['__folio_middleware'] = 0;

    $middleware = ['*' => [
        function ($request, $next) {
            $_SERVER['__folio_middleware']++;

            return $next($request);
        },
    ]];

    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);
    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);
    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);

    $response = $this->get('dashboard');

    $response->assertStatus(200);

    expect($_SERVER['__folio_middleware'])->toBe(1);

    $response = $this->get('dashboard');

    $response->assertStatus(200);

    expect($_SERVER['__folio_middleware'])->toBe(2);
});

test('only the terminable middleware of match mount path gets used on duplicate mount paths', function () {
    $_SERVER['__folio_*_middleware.terminate'] = 0;
    $_SERVER['__folio_*_middleware.terminate.should_fail'] = false;

    $middleware = ['*' => [WithTerminableMiddleware::class]];

    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);
    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);
    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);

    $response = $this->get('dashboard');

    $response->assertStatus(200);

    expect($_SERVER['__folio_*_middleware.terminate'])->toBe(1);

    $response = $this->get('dashboard');

    $response->assertStatus(200);

    expect($_SERVER['__folio_*_middleware.terminate'])->toBe(2);
});

test('middleware of non matched domain does not get executed', function () {
    $_SERVER['__folio_middleware'] = 0;

    $middleware = ['*' => [
        function ($request, $next) {
            $_SERVER['__folio_middleware']++;

            return $next($request);
        },
    ]];

    Folio::domain('another-domain.com')->path(__DIR__.'/resources/views/pages')->middleware($middleware);
    Folio::path(__DIR__.'/resources/views/pages')->middleware($middleware);

    $response = $this->get('https://domain.com/dashboard');

    $response->assertStatus(200);

    expect($_SERVER['__folio_middleware'])->toBe(1);
});

test('sub domain matching does not get effected by root domain matching', function () {
    Folio::domain('domain.com')->path(__DIR__.'/resources/views/pages')
        ->middleware(['*' => [fn () => abort(404)]]);

    Folio::domain('sub.domain.com')->path(__DIR__.'/resources/views/pages');

    $response = $this->get('https://sub.domain.com/dashboard');

    $response->assertStatus(200);
});

test('multiple domains with overlapping paths', function () {
    Folio::domain('one.example.com')->path(__DIR__.'/resources/views/domain-one');
    Folio::domain('two.example.com')->path(__DIR__.'/resources/views/domain-two');

    $responseOne = $this->get('https://one.example.com/');
    $responseTwo = $this->get('https://two.example.com/');
    $responseOneAbout = $this->get('https://one.example.com/about');
    $responseTwoAbout = $this->get('https://two.example.com/about');

    $responseOne->assertStatus(200)->assertSee('Domain One - Home');
    $responseTwo->assertStatus(200)->assertSee('Domain Two - Home');
    $responseOneAbout->assertStatus(200)->assertSee('Domain One - About');
    $responseTwoAbout->assertStatus(200)->assertSee('Domain Two - About');

    // Also verify cross-domain isolation
    $wrongDomainOne = $this->get('https://two.example.com/')->getContent();
    $wrongDomainTwo = $this->get('https://one.example.com/')->getContent();
    
    expect($wrongDomainOne)->not->toContain('Domain One');
    expect($wrongDomainTwo)->not->toContain('Domain Two');
});
