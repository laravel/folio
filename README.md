<p align="center"><img src="/art/logo.svg" alt="Laravel Folio Package Logo"></p>

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

```
php artisan folio:make index
# pages/index.blade.php → /

php artisan folio:make user/profile/index
# pages/user/profile/index.blade.php → /user/profile
```

<a name="route-parameters"></a>
### Route Parameters

To capture segments of the URI within your route, you may use the bracket syntax. As example, you may use the `[id]` as blade view name, to capture a user's ID from the URL:

```
php artisan folio:make user/[id]

# pages/user/[id].blade.php → /user/1
```

Captured segments can be accessed as regular variables within your blade view:

```html
<div>
    User {{ $id }}
</div>
```

To capture multiple segments, you can use three dots `...` within the brackets:

```
php artisan folio:make user/[...id]

# pages/user/[...id].blade.php → /user/1/2/3
```

In this scenario, the captured `$id` will be accessible in `array` format:

```html
<ul>
    @foreach ($id)
        <li> User {{ $id }} </li>
    @endforeach
</ul>
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
