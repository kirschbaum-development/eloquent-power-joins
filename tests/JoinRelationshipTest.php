<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;

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
        $query = Post::query()->joinRelationship('images')->toSql();

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
        $query = User::query()->joinRelationship('posts', function ($join) {
            $join->where('posts.published', true);
        })->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_nested_joins()
    {
        $query = User::query()->joinRelationship('posts.comments', [
            'posts' => function ($join) {
                $join->where('posts.published', true);
            },
            'comments' => function ($join) {
                $join->where('comments.approved', true);
            },
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."published" = ?',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."approved" = ?',
            $query
        );
    }
}
