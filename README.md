![Eloquent Power Joins](screenshots/eloquent-power-joins.jpg "Eloquent Power Joins")

![Laravel Supported Versions](https://img.shields.io/badge/laravel-8.x/9.x/10.x/11.x-green.svg)
[![run-tests](https://github.com/kirschbaum-development/eloquent-power-joins/actions/workflows/ci.yaml/badge.svg)](https://github.com/kirschbaum-development/eloquent-power-joins/actions/workflows/ci.yaml)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/kirschbaum-development/eloquent-power-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/eloquent-power-joins)
[![Total Downloads](https://img.shields.io/packagist/dt/kirschbaum-development/eloquent-power-joins.svg?style=flat-square)](https://packagist.org/packages/kirschbaum-development/eloquent-power-joins)

The Laravel magic you know, now applied to joins.

Joins are very useful in a lot of ways. If you are here, you most likely know about and use them. Eloquent is very powerful, but it lacks a bit of the "Laravel way" when using joins. This package make your joins in a more Laravel way, with more readable with less code while hiding implementation details from places they don't need to be exposed.

A few things we consider is missing when using joins which are very powerful Eloquent features:

* Ability to use relationship definitions to make joins;
* Ability to use model scopes inside different contexts;
* Ability to query relationship existence using joins instead of where exists;
* Ability to easily sort results based on columns or aggregations from related tables;

You can read a more detailed explanation on the problems this package solves on [this blog post](https://kirschbaumdevelopment.com/news-articles/adding-some-laravel-magic-to-your-eloquent-joins).

## Installation

You can install the package via composer:

```bash
composer require kirschbaum-development/eloquent-power-joins
```

For Laravel versions < 8, use the 2.* version:

```bash
composer require kirschbaum-development/eloquent-power-joins:2.*
```

## Usage

This package provides a few features.

### 1 - Join Relationship

Let's say you have a `User` model with a `hasMany` relationship to the `Post` model. If you want to join the tables, you would usually write something like:

```php
User::select('users.*')->join('posts', 'posts.user_id', '=', 'users.id');
```

This package provides you with a new `joinRelationship()` method, which does the exact same thing.

```php
User::joinRelationship('posts');
```

Both options produce the same results. In terms of code, you didn't save THAT much, but you are now using the relationship between the `User` and the `Post` models to join the tables. This means that you are now hiding how this relationship works behind the scenes (implementation details). You also don't need to change the code if the relationship type changes. You now have more readable and less overwhelming code.

But, **it gets better** when you need to **join nested relationships**. Let's assume you also have a `hasMany` relationship between the `Post` and `Comment` models and you need to join these tables, you can simply write:

```php
User::joinRelationship('posts.comments');
```

So much better, wouldn't you agree?! You can also `left` or `right` join the relationships as needed.

```php
User::leftJoinRelationship('posts.comments');
User::rightJoinRelationship('posts.comments');
```

#### Joining polymorphic relationships

Let's imagine, you have a `Image` model that is a polymorphic relationship (`Post -> morphMany -> Image`). Besides the regular join, you would also need to apply the `where imageable_type = Post::class` condition, otherwise you could get messy results.

Turns out, if you join a polymorphic relationship, Eloquent Power Joins automatically applies this condition for you. You simply need to call the same method.

```php
Post::joinRelationship('images');
```

You can also join MorphTo relationships.

```php
Image::joinRelationship('imageable', morphable: Post::class);
```

Note: Querying morph to relationships only supports one morphable type at a time.

**Applying conditions & callbacks to the joins**

Now, let's say you want to apply a condition to the join you are making. You simply need to pass a callback as the second parameter to the `joinRelationship` method.

```php
User::joinRelationship('posts', fn ($join) => $join->where('posts.approved', true))->toSql();
```

For **nested calls**, you simply need to pass an array referencing the relationship names.

```php
User::joinRelationship('posts.comments', [
    'posts' => fn ($join) => $join->where('posts.published', true),
    'comments' => fn ($join) => $join->where('comments.approved', true),
]);
```

For **belongs to many** calls, you need to pass an array with the relationship, and then an array with the table names.

```php
User::joinRelationship('groups', [
    'groups' => [
        'groups' => function ($join) {
            // ...
        },
        // group_members is the intermediary table here
        'group_members' => fn ($join) => $join->where('group_members.active', true),
    ]
]);
```

#### Using model scopes inside the join callbacks 🤯

We consider this one of the most useful features of this package. Let's say, you have a `published` scope on your `Post` model:

```php
    public function scopePublished($query)
    {
        $query->where('published', true);
    }
```

When joining relationships, you **can** use the scopes defined in the model being joined. How cool is this?

```php
User::joinRelationship('posts', function ($join) {
    // the $join instance here can access any of the scopes defined in Post 🤯
    $join->published();
});
```

When using model scopes inside a join clause, you **can't** type hint the `$query` parameter in your scope. Also, keep in mind you are inside a join, so you are limited to use only conditions supported by joins.

#### Using aliases

Sometimes, you are going to need to use table aliases on your joins because you are joining the same table more than once. One option to accomplish this is to use the `joinRelationshipUsingAlias` method.

```php
Post::joinRelationshipUsingAlias('category.parent')->get();
```

In case you need to specify the name of the alias which is going to be used, you can do in two different ways:

1. Passing a string as the second parameter (this won't work for nested joins):

```php
Post::joinRelationshipUsingAlias('category', 'category_alias')->get();
```

2. Calling the `as` function inside the join callback.

```php
Post::joinRelationship('category.parent', [
    'category' => fn ($join) => $join->as('category_alias'),
    'parent' => fn ($join) => $join->as('category_parent'),
])->get()
```

For *belongs to many* or *has many through* calls, you need to pass an array with the relationship, and then an array with the table names.

```php
Group::joinRelationship('posts.user', [
    'posts' => [
        'posts' => fn ($join) => $join->as('posts_alias'),
        'post_groups' => fn ($join) => $join->as('post_groups_alias'),
    ],
])->toSql();
```

#### Select * from table

When making joins, using `select * from ...` can be dangerous as fields with the same name between the parent and the joined tables could conflict. Thinking on that, if you call the `joinRelationship` method without previously selecting any specific columns, Eloquent Power Joins will automatically include that for you. For instance, take a look at the following examples:

```php
User::joinRelationship('posts')->toSql();
// select users.* from users inner join posts on posts.user_id = users.id
```

And, if you specify the select statement:

```php
User::select('users.id')->joinRelationship('posts')->toSql();
// select users.id from users inner join posts on posts.user_id = users.id
```

#### Soft deletes

When joining any models which uses the `SoftDeletes` trait, the following condition will be also automatically applied to all your joins:

```sql
and "users"."deleted_at" is null
```

In case you want to include trashed models, you can call the `->withTrashed()` method in the join callback.

```php
UserProfile::joinRelationship('users', fn ($join) => $join->withTrashed());
```

You can also call the `onlyTrashed` model as well:

```php
UserProfile::joinRelationship('users', ($join) => $join->onlyTrashed());
```

#### Extra conditions defined in relationships

If you have extra conditions in your relationship definitions, they will get automatically applied for you.

```php
class User extends Model
{
    public function publishedPosts()
    {
        return $this->hasMany(Post::class)->published();
    }
}
```

If you call `User::joinRelationship('publishedPosts')->get()`, it will also apply the additional published scope to the join clause. It would produce an SQL more or less like this:

```sql
select users.* from users inner join posts on posts.user_id = posts.id and posts.published = 1
```

#### Global Scopes

If your model have global scopes applied to it, you can enable the global scopes by calling the `withGlobalScopes` method in your join clause, like this:

```php
UserProfile::joinRelationship('users', fn ($join) => $join->withGlobalScopes());
```

There's, though, a gotcha here. Your global scope **cannot** type-hint the `Eloquent\Builder` class in the first parameter of the `apply` method, otherwise you will get errors.

### 2 - Querying relationship existence (Using Joins)

[Querying relationship existence](https://laravel.com/docs/7.x/eloquent-relationships#querying-relationship-existence) is a very powerful and convenient feature of Eloquent. However, it uses the `where exists` syntax which is not always the best and may not be the more performant choice, depending on how many records you have or the structure of your tables.

This packages implements the same functionality, but instead of using the `where exists` syntax, it uses **joins**. Below, you can see the methods this package implements and also the Laravel equivalent.

Please note that although the methods are similar, you will not always get the same results when using joins, depending on the context of your query. You should be aware of the differences between querying the data with `where exists` vs `joins`.

**Laravel Native Methods**

``` php
User::has('posts');
User::has('posts.comments');
User::has('posts', '>', 3);
User::whereHas('posts', fn ($query) => $query->where('posts.published', true));
User::whereHas('posts.comments', ['posts' => fn ($query) => $query->where('posts.published', true));
User::doesntHave('posts');
```

**Package equivalent, but using joins**

```php
User::powerJoinHas('posts');
User::powerJoinHas('posts.comments');
User::powerJoinHas('posts.comments', '>', 3);
User::powerJoinWhereHas('posts', function ($join) {
    $join->where('posts.published', true);
});
User::powerJoinDoesntHave('posts');
```

When using the `powerJoinWhereHas` method with relationships that involves more than 1 table (One to Many, Many to Many, etc.), use the array syntax to pass the callback:

```php
User::powerJoinWhereHas('commentsThroughPosts', [
    'comments' => fn ($query) => $query->where('body', 'a')
])->get());
```

### 3 - Order by

You can also sort your query results using a column from another table using the `orderByPowerJoins` method.

```php
User::orderByPowerJoins('profile.city');
```

If you need to pass some raw values for the order by function, you can do like this:

```php
User::orderByPowerJoins(['profile', DB::raw('concat(city, ", ", state)']);
```

This query will sort the results based on the `city` column on the `user_profiles` table. You can also sort your results by aggregations (`COUNT`, `SUM`, `AVG`, `MIN` or `MAX`).

For instance, to sort users with the highest number of posts, you can do this:

```php
$users = User::orderByPowerJoinsCount('posts.id', 'desc')->get();
```

Or, to get the list of posts where the comments contain the highest average of votes.

```php
$posts = Post::orderByPowerJoinsAvg('comments.votes', 'desc')->get();
```

You also have methods for `SUM`, `MIN` and `MAX`:

```php
Post::orderByPowerJoinsSum('comments.votes');
Post::orderByPowerJoinsMin('comments.votes');
Post::orderByPowerJoinsMax('comments.votes');
```

In case you want to use left joins in sorting, you also can:

```php
Post::orderByLeftPowerJoinsCount('comments.votes');
Post::orderByLeftPowerJoinsAvg('comments.votes');
Post::orderByLeftPowerJoinsSum('comments.votes');
Post::orderByLeftPowerJoinsMin('comments.votes');
Post::orderByLeftPowerJoinsMax('comments.votes');
```

***

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email security@kirschbaumdevelopment.com instead of using the issue tracker.

## Credits

- [Luis Dalmolin](https://github.com/luisdalmolin)

## Sponsorship

Development of this package is sponsored by Kirschbaum Development Group, a developer driven company focused on problem solving, team building, and community. Learn more [about us](https://kirschbaumdevelopment.com) or [join us](https://careers.kirschbaumdevelopment.com)!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
