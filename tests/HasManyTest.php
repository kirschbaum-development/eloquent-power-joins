<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Post;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;

class HasManyTest extends TestCase
{
    /** @test */
    public function test_has_many()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::whereHas('posts')->get());
        $this->assertCount(1, User::whereHasWithJoins('posts')->get());
    }

    /** @test */
    public function test_has_many_with_sub_condition()
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
}
