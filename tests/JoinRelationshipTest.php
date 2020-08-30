<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class JoinRelationshipTest extends TestCase
{
    /** @test */
    public function test_join_first_level_relationship()
    {
        $query = User::query()->joinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_left_join_first_level_relationship()
    {
        $query = User::query()->leftJoinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'left join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_right_join_first_level_relationship()
    {
        $query = User::query()->rightJoinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'right join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_second_level_relationship()
    {
        $query = User::query()->joinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_left_join_second_level_relationship()
    {
        $query = User::query()->leftJoinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'left join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'left join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_right_join_second_level_relationship()
    {
        $query = User::query()->rightJoinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'right join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'right join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_morph_relationship()
    {
        factory(Image::class, 5)->state('owner:post')->create();

        $query = Post::query()->joinRelationship('images')->toSql();
        $posts = Post::query()->joinRelationship('images')->get();

        $this->assertCount(5, $posts);
        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_morph_nested_relationship()
    {
        $query = User::query()->joinRelationship('posts.images')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_join()
    {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            $join->where('posts.published', true);
        });

        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_join_using_related_model_scopes()
    {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            // published() is an scope in the Post model
            // how awesome is that?
            $join->published();
        });

        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_nested_joins()
    {
        $queryBuilder = User::query()->joinRelationship('posts.comments', [
            'posts' => function ($join) {
                $join->where('posts.published', true);
            },
            'comments' => function ($join) {
                $join->where('comments.approved', true);
            },
        ]);
        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "comments"."approved" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many()
    {
        $query = User::query()->joinRelationship('groups')->toSql();

        $this->assertStringContainsString(
            'inner join "group_members" on "group_members"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "groups" on "groups"."id" = "group_members"."group_id"',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many_with_callback()
    {
        $query = User::query()->joinRelationship('groups', [
            'groups' => function ($join) {
                $join->where('groups.name', 'Test');
            },
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "group_members" on "group_members"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "groups" on "groups"."id" = "group_members"."group_id" and "groups"."name" = ?',
            $query
        );
    }

    /** @test */
    public function test_it_doesnt_join_the_same_relationship_twice()
    {
        $query = User::query()
            ->select('users.*')
            ->joinRelationship('posts.comments')
            ->joinRelationship('posts.images')
            ->toSql();

        // making sure it doesn't throw any errors
        User::query()->select('users.*')->joinRelationship('posts.comments')->joinRelationship('posts.images')->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertEquals(
            1,
            substr_count($query, 'inner join "posts" on "posts"."user_id" = "users"."id"'),
            'It should only make 1 join with the posts table'
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_it_join_belongs_to_relationship()
    {
        $posts = factory(Post::class)->times(2)->create();

        $queriesPosts = Post::query()
            ->select('posts.id', 'users.name')
            ->joinRelationship('user')
            ->get();

        $this->assertCount(2, $queriesPosts);
        $this->assertEquals($posts->get(0)->user->name, $queriesPosts->get(0)->name);
        $this->assertEquals($posts->get(1)->user->name, $queriesPosts->get(1)->name);
    }

    /** @test */
    public function test_it_join_nested_belongs_to_relationship()
    {
        [$comment1, $comment2, $comment3] = factory(Comment::class, 3)->create();

        // deleting this user, which will make the user soft deleted
        // this should make the user NOT come in the query, since we are INNER JOINING
        $comment3->post->user->delete();

        $comments = Comment::query()
            ->select('posts.title', 'users.name')
            ->joinRelationship('post.user')
            ->get();

        $this->assertCount(2, $comments);
        $this->assertEquals($comment1->post->user->name, $comments->get(0)->name);
        $this->assertEquals($comment2->post->user->name, $comments->get(1)->name);
    }

    /** @test */
    public function test_join_model_with_soft_deletes()
    {
        $query = UserProfile::query()->joinRelationship('user')->toSql();

        $this->assertStringContainsString(
            'inner join "users" on "user_profiles"."user_id" = "users"."id" and "users"."deleted_at" is null',
            $query
        );
    }

    /** @test */
    public function test_it_automatically_includes_select_statement_if_not_defined()
    {
        $query = User::joinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'select "users".* from "users"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_it_does_notautomatically_includes_select_statement_if_already_defined()
    {
        $query = User::select('users.id')->joinRelationship('posts')->toSql();

        $this->assertStringNotContainsString(
            'select "users".* from "users"',
            $query
        );

        $this->assertStringContainsString(
            'select "users"."id" from "users"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_has_many_through_relationship()
    {
        // just making sure it runs fine
        User::joinRelationship('commentsThroughPosts')->get();

        $query = User::joinRelationship('commentsThroughPosts')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }
}
