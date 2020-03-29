<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Group;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;

class HasUsingJoinsTest extends TestCase
{
    /** @test */
    public function test_has_using_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::has('posts')->get());
        $this->assertCount(1, User::hasUsingJoins('posts')->get());
    }

    /** @test */
    public function test_has_using_joins_and_model_scopes()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $postUser1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $postUser2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function ($query) {
            $query->where('posts.published', true);
        })->get());

        $this->assertCount(1, User::whereHasUsingJoins('posts', function ($join) {
            $join->published();
        })->get());
    }

    /** @test */
    public function test_has_using_joins_on_belongs_to_many()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $group = factory(Group::class)->create();
        $user1->groups()->attach($group);

        $this->assertCount(1, User::has('groups')->get());
        $this->assertCount(1, User::hasUsingJoins('groups')->get());
    }

    /** @test */
    public function test_has_using_joins_using_different_operators()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $user1Posts = factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        $user2Posts = factory(Post::class)->times(3)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::has('posts', '>=', 3)->get());
        $this->assertCount(1, User::hasUsingJoins('posts', '>=', 3)->get());

        $this->assertCount(1, User::has('posts', '>', 2)->get());
        $this->assertCount(1, User::hasUsingJoins('posts', '>', 2)->get());

        $this->assertCount(1, User::has('posts', '<=', 2)->get());
        $this->assertCount(1, User::hasUsingJoins('posts', '<=', 2)->get());
    }

    /** @test */
    public function test_where_has_using_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);
        $posts = factory(Post::class)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function ($builder) use ($user1) {
            $builder->where('posts.user_id', '=', $user1->id);
        })->get());

        $this->assertCount(1, User::whereHasUsingJoins('posts', function ($builder) use ($user1) {
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
        $this->assertCount(2, User::hasUsingJoins('posts.comments')->get());
    }

    /** @test */
    public function test_doesnt_have()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::doesntHave('posts')->get());
        $this->assertCount(1, User::doesntHaveUsingJoins('posts')->get());
    }
}
