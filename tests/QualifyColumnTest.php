<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\PowerJoinClause;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Post;

class QualifyColumnTest extends TestCase
{
    /**
     * @test
     */
    public function test_where_keeps_a_column_qualified_by_the_alias()
    {
        $query = Post::joinRelationship('comments', function (PowerJoinClause $join) {
            $join->as('c')->where('c.approved', true);
        });

        $this->assertQueryContains('and "c"."approved" = ?', $query->toSql());
        $this->assertCount(1, $this->postWithApprovedComment($query));
    }

    /**
     * Create a post with an approved comment and one with an unapproved comment, then run the query.
     */
    protected function postWithApprovedComment($query)
    {
        factory(Comment::class)->create(['approved' => true]);
        factory(Comment::class)->create(['approved' => false]);

        return $query->get();
    }
}
