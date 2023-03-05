<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class JoinRelationshipWithConditionsReportedIssueTest extends TestCase {

    public function test_conditions_inside_join_plain_callback() {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            $join->published();
        });
        $query = $queryBuilder->toSql();
        // dump($query);
        $this->assertStringContainsString('"posts"."published" = ?', $query);
    }

    public function test_conditions_inside_nested_join_callback_array_callback() {
        $queryBuilder = User::query()->joinRelationship('posts.comments', [
            'posts' => function ($join) {
                $join->published();
            }
        ]);
        $query = $queryBuilder->toSql();
        // dump($query);
        $this->assertStringContainsString('"posts"."published" = ?', $query);

    }

    public function test_conditions_inside_simple_join_array_callback() {
        $this->markTestSkipped('[SKIPPED] Reported inconsistent conditioning of joins: https://github.com/kirschbaum-development/eloquent-power-joins/issues/105');
        return;

        $queryBuilder = User::query()->joinRelationship('posts', [
            'posts' => function ($join) {
                $join->published();
            }
        ]);
        $query = $queryBuilder->toSql();
        // dump($query);
        $this->assertStringContainsString('"posts"."published" = ?', $query);
    }

}
