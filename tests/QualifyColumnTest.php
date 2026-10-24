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
    public function test_qualify_column_uses_the_table_name_without_an_alias()
    {
        $query = Post::joinRelationship('comments', function (PowerJoinClause $join) {
            $join->where($join->qualifyColumn('approved'), true);
        });

        $this->assertQueryContains('and "comments"."approved" = ?', $query->toSql());
        $this->assertCount(1, $this->postWithApprovedComment($query));
    }

    /**
     * @test
     */
    public function test_qualify_column_uses_the_alias_set_before()
    {
        $query = Post::joinRelationship('comments', function (PowerJoinClause $join) {
            $join->as('c')
                ->where($join->qualifyColumn('approved'), true)
                ->whereColumn($join->qualifyColumn('created_at'), '>=', 'posts.created_at');
        });

        $this->assertQueryContains('and "c"."approved" = ? and "c"."created_at" >= "posts"."created_at"', $query->toSql());
        $this->assertCount(1, $this->postWithApprovedComment($query));
    }

    /**
     * @test
     */
    public function test_qualify_column_uses_the_alias_set_after()
    {
        $query = Post::joinRelationship('comments', function (PowerJoinClause $join) {
            $join->where($join->qualifyColumn('approved'), true)
                ->whereColumn($join->qualifyColumn('created_at'), '>=', 'posts.created_at')
                ->as('c');
        });

        $this->assertQueryContains('and "c"."approved" = ? and "c"."created_at" >= "posts"."created_at"', $query->toSql());
        $this->assertCount(1, $this->postWithApprovedComment($query));
    }

    /**
     * @test
     */
    public function test_qualify_column_uses_the_generated_alias()
    {
        $query = Post::joinRelationshipUsingAlias('comments', function (PowerJoinClause $join) {
            $join->where($join->qualifyColumn('approved'), true)
                ->whereColumn($join->qualifyColumn('created_at'), '>=', 'posts.created_at');
        });

        $alias = $query->getQuery()->joins[0]->alias;

        $this->assertQueryContains("and \"{$alias}\".\"approved\" = ? and \"{$alias}\".\"created_at\" >= \"posts\".\"created_at\"", $query->toSql());
        $this->assertCount(1, $this->postWithApprovedComment($query));
    }

    /**
     * @test
     */
    public function test_qualify_column_leaves_a_qualified_column_alone()
    {
        $query = Post::joinRelationship('comments', function (PowerJoinClause $join) {
            $join->as('c')->whereColumn($join->qualifyColumn('posts.created_at'), '<=', $join->qualifyColumn('created_at'));
        });

        $this->assertQueryContains('and "posts"."created_at" <= "c"."created_at"', $query->toSql());
    }

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
