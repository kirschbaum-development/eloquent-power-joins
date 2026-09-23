![Eloquent Power Joins](screenshots/eloquent-power-joins.jpg "Eloquent Power Joins")

# The Laravel magic you know, now applied to joins

![Laravel Supported Versions](https://img.shields.io/badge/laravel-11.x/12.x/13.x-green.svg)
[![run-tests](https://github.com/kirschbaum-development/eloquent-power-joins/actions/workflows/ci.yaml/badge.svg)](https://github.com/kirschbaum-development/eloquent-power-joins/actions/workflows/ci.yaml)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/kirschbaum-development/eloquent-power-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/eloquent-power-joins)
[![Total Downloads](https://img.shields.io/packagist/dt/kirschbaum-development/eloquent-power-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/eloquent-power-joins)

Eloquent is very powerful, but it lacks a bit of the "Laravel way" when using joins. This package lets you write joins the Laravel way: more readable, with less code, and without leaking implementation details where they don't belong.

Instead of writing joins by hand:

```php
User::select('users.*')
    ->join('posts', 'posts.user_id', '=', 'users.id')
    ->join('comments', 'comments.post_id', '=', 'posts.id');
```

You can use the relationships you've already defined:

```php
User::joinRelationship('posts.comments');
```

Polymorphic relationships, soft deletes, model scopes, aliases and extra relationship conditions are all handled for you. You can also query relationship existence using joins and sort results by columns or aggregations from related tables:

```php
// Query relationship existence using joins instead of where exists
User::powerJoinHas('posts');

// Order by a related column
User::orderByPowerJoins('profile.city');

// Order by an aggregation on a related table
Post::orderByPowerJoinsCount('comments.id', 'desc');
```

## Documentation

You'll find the full documentation on [our documentation site](https://docs.kirschbaumdevelopment.com/projects/eloquent-power-joins/).

You can also read a more detailed explanation of the problems this package solves in [this blog post](https://kirschbaumdevelopment.com/insights/power-joins).

## Installation

You can install the package via composer:

```bash
composer require kirschbaum-development/eloquent-power-joins
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@kirschbaumdevelopment.com instead of using the issue tracker.

## Credits

- [Luis Dalmolin](https://github.com/luisdalmolin)
- [All Contributors](../../contributors)

## Sponsorship

Development of this package is sponsored by Kirschbaum Development Group, a developer driven company focused on problem solving, team building, and community. Learn more [about us](https://kirschbaumdevelopment.com) or [join us](https://careers.kirschbaumdevelopment.com)!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
