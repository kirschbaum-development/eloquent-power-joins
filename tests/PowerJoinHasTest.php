<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;

class PowerJoinHasTest extends TestCase
{
    /** @test */
    public function test_has_using_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::has('posts')->get());
        $this->assertCount(1, User::powerJoinHas('posts')->get());
    }

    /** @test */
    public function test_has_using_joins_from_query_builder()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::query()->has('posts')->get());
        $this->assertCount(1, User::query()->powerJoinHas('posts')->get());
    }

    /** @test */
    public function test_where_has_using_joins_and_model_scopes()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $postUser1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $postUser2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function ($query) {
            $query->where('posts.published', true);
        })->get());

        $this->assertCount(1, User::powerJoinWhereHas('posts', function ($join) {
            $join->published();
        })->get());
    }

    /** @test */
    public function test_where_has_from_model_scope()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $postUser1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $postUser2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::query()->hasPublishedPosts()->get());
    }

    /** @test */
    public function test_has_using_joins_on_belongs_to_many()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $group = factory(Group::class)->create();
        $user1->groups()->attach($group);

        $this->assertCount(1, User::has('groups')->get());
        $this->assertCount(1, User::powerJoinHas('groups')->get());
    }

    /** @test */
    public function test_has_using_joins_using_different_operators()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $user1Posts = factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        $user2Posts = factory(Post::class)->times(3)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::has('posts', '>=', 3)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '>=', 3)->get());

        $this->assertCount(1, User::has('posts', '>', 2)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '>', 2)->get());

        $this->assertCount(1, User::has('posts', '<=', 2)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '<=', 2)->get());
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

        $this->assertCount(1, User::powerJoinWhereHas('posts', function ($builder) use ($user1) {
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
        $this->assertCount(2, User::powerJoinHas('posts.comments')->get());
    }

    /** @test */
    public function test_where_has_with_joins_on_has_many_through_relationship()
    {
        [$user1, $user2, $user3] = factory(User::class)->times(3)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['user_id' => $user2->id]);
        $post3 = factory(Post::class)->create(['user_id' => $user3->id]);
        $commentsPost1 = factory(Comment::class)->times(2)->create(['post_id' => $post1->id]);
        $commentsPost2 = factory(Comment::class)->times(2)->create(['post_id' => $post2->id]);
        $commentsPost3 = factory(Comment::class)->times(1)->create(['post_id' => $post3->id]);
        $commentsPost1[0]->body = 'a';
        $commentsPost1[1]->body = 'a';
        $commentsPost1[0]->save();
        $commentsPost1[1]->save();
        $commentsPost2[0]->body = 'a';
        $commentsPost2[0]->save();

        $closure = function($query) {
            $query->where('body', 'a');
        };

        $this->assertCount(2, User::whereHas('commentsThroughPosts', $closure)->get());
        $this->assertCount(2, User::powerJoinWhereHas('commentsThroughPosts', $closure)->get());
    }

    /** @test */
    public function test_doesnt_have()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::doesntHave('posts')->get());
        $this->assertCount(1, User::powerJoinDoesntHave('posts')->get());
    }
}
