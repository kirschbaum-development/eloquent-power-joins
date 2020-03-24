# Eloquent Super Joins

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/laravel-where-has-with-joins) -->
[![Actions Status](https://github.com/kirschbaum-development/laravel-where-has-with-joins/workflows/CI/badge.svg)](https://github.com/kirschbaum-development/laravel-where-has-with-joins/actions)
<!-- [![Quality Score](https://img.shields.io/scrutinizer/g/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://scrutinizer-ci.com/g/kirschbaum-development/laravel-where-has-with-joins) -->
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/kirschbaum-development/laravel-where-has-with-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/laravel-where-has-with-joins) -->

Joins are very useful in a lot of ways. If you are here, you most likely know about and use them. This package gives you some extra powers making your joins more readable with less code while hiding implementation details from places they don't need to be exposed.

## Installation

You can install the package via composer:

```bash
composer require kirschbaum-development/eloquent-joins-with-extra-powers
```

## Usage

This package provides a few different methods you can use.

### Join Relationship

Let's say you have a `User` model with a `hasMany` relationship to the `Post` model. If you want to join the tables, you would usually write something like:

```php
User::select('users.*')->join('posts', 'posts.user_id', '=', 'users.id')->toSql();
// select users.* from users inner join "posts" on "posts"."user_id" = "users"."id"
```

This package provides you with a new `joinRelationship()` method:

```php
User::select('users.*')->joinRelationship('posts')->toSql();
// select users.* from users inner join "posts" on "posts"."user_id" = "users"."id"
```

Both options produce the same results. In terms of code, you didn't save much, but you are now using the relationship between users and posts do join the tables. This means that you are now hiding how this relationship works behind the scenes (implementation details). You also don't need to change the code if the relationship type changes. You now have more readable and less overwhelming code.

But, **it gets better** when you need to **join nested relationships**. Let's assume you have a `hasMany` relationship between the `Post` and `Comment` models and you need to join these tables.

```php
User::select('users.*')->join('posts', 'posts.user_id', '=', 'users.id')->join('posts', 'posts.user_id', '=', 'users.id')->toSql();
// select users.* from users inner join "posts" on "posts"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "posts"."id"
```

Instead of writing all this, you can simply write:

```php
User::select('users.*')->joinRelationship('posts.comments')->toSql();
```

So much better, wouldn't you agree?! You can also `left` or `right` join the relationships.

```php
User::select('users.*')->leftJoinRelationship('posts.comments')->toSql();
User::select('users.*')->rightJoinRelationship('posts.comments')->toSql();
```

**Applying conditions to the join**

Now, let's say you want to apply a condition to the join you are making.

```php
User::select('users.*')->joinRelationship('posts', function ($join) {
    $join->where('posts.approved', true);
})->toSql();
```

You simply need to pass a callback as the second parameter to the `joinRelationship` method. And, for nested relationship, pass an array referencing the relation name.

```php
User::select('users.*')->joinRelationship('posts.comments', [
    'posts' => function ($join) {
        $join->where('posts.published', true);
    },
    'comments' => function ($join) {
        $join->where('comments.approved', true);
    }
])->toSql();
```

### Querying relationship existence (Using Joins)

[Querying relationship existence](https://laravel.com/docs/7.x/eloquent-relationships#querying-relationship-existence) is a very powerful and convenient feature of Eloquent. However, it uses the `where exists` syntax which is not always the best and may not be the more performant choice, depending on how many records you have or the structure of your tables.

This packages implements the same functionality, but instead of using the `where exists` syntax, it uses **joins**.

Below, you can see the methods this package implements and also the Laravel equivalent.

**Laravel Native Methods**

``` php
User::has('posts');
User::has('posts.comments');
User::has('posts', '>', 3);
User::whereHas('posts', function ($query) {
    $query->where('posts.published', true);
});
User::doesntHave('posts');
```

**Package implementations**

```php
User::hasUsingJoins('posts');
User::hasUsingJoins('posts.comments');
User::hasUsingJoins('posts.comments', '>', 3);
User::whereHasUsingJoins('posts', function ($query) {
    $query->where('posts.published', true);
});
User::doesntHaveUsingJoins('posts');
```

### Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email luis@kirschbaumdevelopment.com or nathan@kirschbaumdevelopment.com instead of using the issue tracker.

## Credits

- [Luis Dalmolin](https://github.com/luisdalmolin)

## Sponsorship

Development of this package is sponsored by Kirschbaum Development Group, a developer driven company focused on problem solving, team building, and community. Learn more [about us](https://kirschbaumdevelopment.com) or [join us](https://careers.kirschbaumdevelopment.com)!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
