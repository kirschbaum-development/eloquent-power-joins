<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;

class HasUsingJoinsTest extends TestCase
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
    public function test_has_with_joins_and_nested_relations()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['user_id' => $user2->id]);
        $commentsPost1 = factory(Comment::class)->times(2)->create(['post_id' => $post1->id]);
        $commentsPost2 = factory(Comment::class)->times(5)->create(['post_id' => $post2->id]);

        $this->assertCount(2, User::has('posts.comments')->get());
        $this->assertCount(2, User::hasWithJoins('posts.comments')->get());
    }

    /** @test */
    public function test_doesnt_have()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::doesntHave('posts')->get());
        $this->assertCount(1, User::doesntHaveWithJoins('posts')->get());
    }
}
