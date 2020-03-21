<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Post;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\User;
use KirschbaumDevelopment\LaravelWhereHasWithJoins\Tests\Models\Comment;

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
}
