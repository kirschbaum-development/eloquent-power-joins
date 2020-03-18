<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Post;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;

class HasManyTest extends TestCase
{
    /** @test */
    public function test_has_with_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::has('posts')->get());
        $this->assertCount(1, User::hasWithJoins('posts')->get());
    }

    /** @test */
    public function test_has_with_joins_using_different_operators()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $user1Posts = factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        $user2Posts = factory(Post::class)->times(3)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::has('posts', '>=', 3)->get());
        $this->assertCount(1, User::hasWithJoins('posts', '>=', 3)->get());

        $this->assertCount(1, User::has('posts', '>', 2)->get());
        $this->assertCount(1, User::hasWithJoins('posts', '>', 2)->get());

        $this->assertCount(1, User::has('posts', '<=', 2)->get());
        $this->assertCount(1, User::hasWithJoins('posts', '<=', 2)->get());
    }

    /** @test */
    public function test_where_has_with_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);
        $posts = factory(Post::class)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function (Builder $builder) use ($user1) {
            $builder->where('posts.user_id', '=', $user1->id);
        })->get());

        $this->assertCount(1, User::whereHasWithJoins('posts', function (Builder $builder) use ($user1) {
            $builder->where('posts.user_id', '=', $user1->id);
        })->get());
    }

    /** @test */
    public function test_has_many_with_nested_relations()
    {
        $this->markTestIncomplete('TODO');

        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::whereHas('posts')->get());
        $this->assertCount(1, User::whereHasWithJoins('posts')->get());
    }
}
