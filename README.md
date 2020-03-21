# Laravel Has Using Joins

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/laravel-where-has-with-joins)
[![Actions Status](https://github.com/kirschbaum-development/laravel-where-has-with-joins/workflows/CI/badge.svg)](https://github.com/kirschbaum-development/laravel-where-has-with-joins/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://scrutinizer-ci.com/g/kirschbaum-development/laravel-where-has-with-joins)
[![Total Downloads](https://img.shields.io/packagist/dt/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/laravel-where-has-with-joins)

[Querying relationship existence](https://laravel.com/docs/7.x/eloquent-relationships#querying-relationship-existence) is a very powerful and convinient feature of Eloquent. BUT, behind the scenes it doesn't use the most performant way to query your data. So, if you need to run this on big tables, it could become a performance bottleneck.

This packages implements the same functionality, but instead of using the `where exists` syntax, it uses joins.

## Installation

You can install the package via composer:

```bash
composer require kirschbaum-development/laravel-where-has-with-joins
```

## Usage

The usage is very simple. On any place you would use a `has`, `whereHas` or `doesntHave` function, just suffix the method name with `withJoins`.

``` php
User::has('posts');
User::hasWithJoins('posts');
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email luis.nh@gmail.com instead of using the issue tracker.

## Credits

- [Luis Dalmolin](https://github.com/kirschbaum-development)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
