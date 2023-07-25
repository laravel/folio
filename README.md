<p align="center"><img width="472" src="/art/logo.svg" alt="Laravel Folio Package Logo"></p>

<p align="center">
    <a href="https://github.com/laravel/folio/actions">
        <img src="https://github.com/laravel/folio/workflows/tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/laravel/folio">
        <img src="https://poser.pugx.org/laravel/folio/d/total.svg" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/laravel/folio">
        <img src="https://poser.pugx.org/laravel/folio/v/stable.svg" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/laravel/folio">
        <img src="https://poser.pugx.org/laravel/folio/license.svg" alt="License">
    </a>
</p>

- [Introduction](#introduction)
- [Installation](#installation)
- [Creating Routes](#creating-routes)
    - [Nested Routes](#nested-routes)
    - [Index Routes](#index-routes)
- [Route Parameters](#route-parameters)
- [Route Model Binding](#route-model-binding)
    - [Soft Deleted Models](#soft-deleted-models)
- [Middleware](#middleware)
- [PHP Blocks](#php-blocks)
- [Contributing](#contributing)
- [Code of Conduct](#code-of-conduct)
- [Security Vulnerabilities](#security-vulnerabilities)
- [License](#license)

<a name="introduction"></a>
## Introduction

Laravel Folio is a powerful page based router designed to simplify routing in Laravel applications. With Laravel Folio, generating a route becomes as effortless as creating a Blade template within your application's `resources/views/pages` directory.

For example, to create a page that is accessible at the `/greeting` URL, just create a `greeting.blade.php` file in your application's `resources/views/pages` directory:

```php
<div>
    Hello World
</div>
```

<a name="installation"></a>
## Installation

To get started, install Folio into your project using the Composer package manager:

```bash
composer require laravel/folio:^1.0@beta
```

After installing Folio, you may execute the `folio:install` Artisan command, which will install Folio's service provider into your application. This service provider registers the directory where Folio will search for routes / pages:

```bash
php artisan folio:install
```

<a name="creating-routes"></a>
## Creating Routes

You may create a Folio route by placing a Blade template in any of your Folio mounted directories. By default, Folio mounts the `resources/views/pages` directory, but you may customize these directories in your Folio service provider's `boot` method.

Once a Blade template has been placed in a Folio mounted directory, you may immediately access it via your browser. For example, a page placed in `pages/schedule.blade.php` may be accessed in your browser at `http://example.com/schedule`.

<a name="nested-routes"></a>
### Nested Routes

You may create a nested route by creating one or more directories within one of Folio's directories. For instance, to create a page that is accessible via `/user/profile`, create a `profile.blade.php` template within the `pages/user` directory:

```bash
php artisan folio:make user/profile

# pages/user/profile.blade.php → /user/profile
```

<a name="index-routes"></a>
### Index Routes

Sometimes, you may wish to make a given page the "index" of a directory. By placing an `index.blade.php` template within a Folio directory, any requests to the root of that directory will be routed to that page:

```bash
php artisan folio:make index
# pages/index.blade.php → /

php artisan folio:make users/index
# pages/users/index.blade.php → /users
```

<a name="route-parameters"></a>
## Route Parameters

Often, you will need to have segments of the incoming request's URL injected into your page so that you can interact with them. For example, you may need to access the "ID" of the user whose profile is being displayed. To accomplish this, you may encapsulate a segment of the page's filename in square brackets:

```bash
php artisan folio:make users/[id]

# pages/users/[id].blade.php → /users/1
```

Captured segments can be accessed as variables within your Blade template:

```html
<div>
    User {{ $id }}
</div>
```

To capture multiple segments, you can prefix the encapsulated segment with three dots `...`:

```bash
php artisan folio:make user/[...ids]

# pages/users/[...ids].blade.php → /users/1/2/3
```

When capturing multiple segments, the captured segments will be injected into the page as an array:

```html
<ul>
    @foreach ($ids as $id)
        <li>User {{ $id }}</li>
    @endforeach
</ul>
```

<a name="route-model-binding"></a>
## Route Model Binding

If a wildcard segment of your page template's filename corresponds one of your application's Eloquent models, Folio will automatically take advantage of Laravel's route model binding capabilities and attempt to inject the resolved model instance into your page:

```bash
php artisan folio:make user/[User]

# pages/users/[User].blade.php → /users/1
```

Captured models can be accessed as variables within your Blade template. The model's variable name will be converted to "camel case":

```html
<div>
    User {{ $user->id }}
</div>
```

#### Customizing The Key

Sometimes you may wish to resolve bound Eloquent models using a column other than `id`. To do so, you may specify the column in the page's filename. For example, a page with the filename `[Post:slug].blade.php` will attempt to resolve the bound model via the `slug` column instead of the `id` column.

#### Model Location

By default, Folio will search for your model within your application's `app/Models` directory. However, if needed, you may specify the fully-qualified model class name in your template's filename:

```bash
php artisan folio:make user/[.App.Models.User]

# pages/users/[.App.Models.User].blade.php → /users/1
```

<a name="soft-deleted-models"></a>
### Soft Deleted Models

By default, models that have been soft deleted are not retrieved when resolving implicit model bindings. However, if you wish, you can instruct Folio to retrieve soft deleted models by invoking the `withTrashed` function within the page's template:

```php
<?php

use function Laravel\Folio\{withTrashed};

withTrashed();

?>

<div>
    User {{ $user->id }}
</div>
```

<a name="middleware"></a>
## Middleware

You can apply middleware to a specific page by invoking the `middleware` function within the page's template:

```php
<?php

use function Laravel\Folio\{middleware};

middleware(['auth']);

?>

<div>
    Dashboard
</div>
```

Or, to assign middleware to a group of pages, you may provide the `middleware` argument when invoking the `Folio::route` method.

To specify which pages the middleware should be applied to, the array of middleware may be keyed using the corresponding URL patterns of the pages they should be applied to. The `*` character may be utilized as a wildcard character:

```php
use Laravel\Folio\Folio;

Folio::route(resource_path('views/pages'), middleware: [
    'chirps/*' => [
        'auth',
        // ...
    ],
]);
```

You may include closures in the array of middleware to define inline, anonymous middleware:

```php
use Closure;
use Illuminate\Http\Request;
use Laravel\Folio\Folio;

Folio::route(resource_path('views/pages'), middleware: [
    'chirps/*' => [
        'auth',

        function (Request $request, Closure $next) {
            // ...

            return $next($request);
        },
    ],
]);
```

<a name="php-blocks"></a>
## PHP Blocks

When using Folio, the `<?php` and `?>` tags are reserved for the Folio page definition functions such as `middleware` and `withTrashed`.

Therefore, if you need to write PHP code that should be executed within your Blade template, you should use the `@php` Blade directive:

```php
@php
    if (! Auth::user()->can('view-posts', $user)) {
        abort(403);
    }

    $posts = $user->posts;
@endphp

@foreach ($posts as $post)
    <div>
        {{ $post->title }}
    </div>
@endforeach
```

## Contributing
<a name="contributing"></a>

Thank you for considering contributing to Folio! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct
<a name="code-of-conduct"></a>

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities
<a name="security-vulnerabilities"></a>

Please review [our security policy](https://github.com/laravel/folio/security/policy) on how to report security vulnerabilities.

## License
<a name="license"></a>

Laravel Folio is open-sourced software licensed under the [MIT license](LICENSE.md).
