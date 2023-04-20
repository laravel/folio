<p align="center"><img src="https://laravel.com/assets/img/components/logo-folio.svg"></p>

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
- [Defining Routes](#defining-routes)
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

<a name="defining-routes"></a>
## Defining Routes

You may define a Folio route by placing a file with the `.blade.php` extension in any of your Folio "route" directories.

Or, you can use the `folio:make` Artisan command to create the blade view:

```bash
php artisan folio:make greeting
```

By adding the `--test` directive when generating a route, a corresponding test file will also be generated. If you want the associated test to use [Pest](https://pestphp.com/), you should use the `--pest` flag:

```bash
php artisan folio:make greeting --test --pest
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
