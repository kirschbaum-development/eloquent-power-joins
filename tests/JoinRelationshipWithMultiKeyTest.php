<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Post;

class JoinRelationshipWithMultiKeyTest extends TestCase
{
    /** @test */
    public function test_left_join_has_many()
    {
        $query = Post::query()->leftJoinRelationship('userComments')->toSql();

        $this->assertStringContainsString(
            'left join "comments" on "comments"."id" = "posts"."post_id" and "comments"."user_id" = "posts"."user_id"',
            $query
        );
    }

    /** @test */
    public function test_left_join_belongs_to()
    {
        $query = Comment::query()->leftJoinRelationship('userPost')->toSql();

        $this->assertStringContainsString(
            'left join "posts" on "comments"."post_id" = "posts"."id" and "comments"."user_id" = "posts"."user_id"',
            $query
        );
    }
}
