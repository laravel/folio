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
- [Middleware](#middleware)
- [Route Model Binding](#route-model-binding)
    - [Soft Deleted Models](#soft-deleted-models)
- [Contributing](#contributing)
- [Code of Conduct](#code-of-conduct)
- [Security Vulnerabilities](#security-vulnerabilities)
- [License](#license)

<a name="introduction"></a>
## Introduction

Laravel Folio is a powerful page based router designed to simplify routing in Laravel applications. With Laravel Folio, generating a route becomes as effortless as creating a blade view under the `resources/views/pages` directory.

To generate a basic `/greeting` Folio route, all you need to do is create a `greeting.blade.php` file in the `resources/views/pages` directory:

```php
<div>
    Hello World
</div>
```

<a name="installation"></a>
## Installation

First, install Folio into your project using the Composer package manager:

```bash
composer require laravel/folio
```

After installing Folio, you may execute the `folio:install` Artisan command, which will install Folio's service provider file into your application:

```bash
php artisan folio:install
```

<a name="creating-routes"></a>
## Creating Routes

You may create a Folio route by placing a file with the `.blade.php` extension in any of your Folio mount directories. The default mounted directories are `resources/views/pages`, but you may customize these directories in your Folio service provider's `boot` method.

Or, you can use the `folio:make` Artisan command to create a Folio route:

```bash
php artisan folio:make greeting

# pages/greeting.blade.php → /greeting
```

<a name="nested-routes"></a>
### Nested Routes

You may create a nested route by creating one or more directories within a Folio mount directory. For instance, if you wish to include a route for `/user/profile`, you can accomplish this by placing the `profile.blade.php` file inside the `pages/user` directory:

```bash
php artisan folio:make user/profile

# pages/user/profile.blade.php → /user/profile
```

<a name="index-routes"></a>
### Index Routes

You can create a route to the root directory by placing an `index.blade.php` file in the directory itself. Folio will then automatically route any file named `index.blade.php` to the root of that directory:

```bash
php artisan folio:make index
# pages/index.blade.php → /

php artisan folio:make user/profile/index
# pages/user/profile/index.blade.php → /user/profile
```

<a name="route-parameters"></a>
## Route Parameters

To capture segments of the URL within your route, you may use the square bracket syntax. As example, you may use the `[id]` as blade view name, to capture a user's ID from the URL:

```bash
php artisan folio:make user/[id]

# pages/user/[id].blade.php → /user/1
```

Captured segments can be accessed as regular variables within your blade view:

```html
<div>
    User {{ $id }}
</div>
```

To capture multiple segments, you can use three dots `...` within the square brackets:

```bash
php artisan folio:make user/[...ids]

# pages/user/[...ids].blade.php → /user/1/2/3
```

The `$ids` that have been captured will be available in the form of an `array`:

```html
<ul>
    @foreach ($ids as $id)
        <li> User {{ $id }} </li>
    @endforeach
</ul>
```

<a name="middleware"></a>
## Middleware

To assign middleware to a particular set of routes, you may use the `middleware` named argument when invoking the `Folio::route` method.

To specify which routes the middleware should be applied to, the array's keys should indicate the desired URL patterns. The `*` character can be utilized as a wildcard character:

```php
Folio::route(resource_path('views/pages'), middleware: [
    'chirps/*' => [
        'auth',

        //
    ],
]);
```

You can include either multiple middleware names or a `closure` on the array:

```php
Folio::route(resource_path('views/pages'), middleware: [
    'chirps/*' => [
        'auth',

        function (Request $request, Closure $next) {
            //

            return $next($request);
        },
    ],
]);
```

It is also possible to apply middleware to a specific page using the `page` function:

```php
<?php

use function Laravel\Folio\{page};

page(middleware: ['auth']);

?>

<div>
    Dashboard
</div>
```

<a name="route-model-binding"></a>
## Route Model Binding

As you may be aware, Laravel's route model binding offers a simple approach to inject model instances directly into your routes. In Folio, you can opt to inject the complete model instance by specifying the model name enclosed in square brackets:

```bash
php artisan folio:make user/[User]

# pages/user/[User].blade.php → /user/1
```

Folio will search for your model name within the `app/Models` directory by default. Nonetheless, you have the option to provide the model class fully qualified name as well:

```bash
php artisan folio:make user/[App.Models.User]

# pages/user/[App.Models.User].blade.php → /user/1
```

Captured models can be accessed as regular variables within your blade view, and the variable name will follow the "$camelCase" naming convention:

```html
<div>
    User {{ $user->id }}
</div>
```

<a name="soft-deleted-models"></a>
### Soft Deleted Models

Models that have been soft deleted are not retrieved through implicit model binding. However, if you wish, you can instruct implicit binding to retrieve such models by using the named argument `withTrashed` and the function `page`:

```php
<?php

use function Laravel\Folio\{page};

page(withTrashed: true);

?>

<div>
    User {{ $user->id }}
</div>
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
