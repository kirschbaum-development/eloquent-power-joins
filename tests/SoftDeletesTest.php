<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class SoftDeletesTest extends TestCase
{
    /** @test */
    public function it_can_disable_soft_deletes()
    {
        // making sure the query doesn't fail
        UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->withTrashed();
            })->get();

        $query = UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->withTrashed();
            })
            ->toSql();

        $this->assertQueryContains(
            'inner join "users" on "user_profiles"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringNotContainsString(
            '"users"."deleted_at" is null',
            $query
        );
    }

    /** @test */
    public function it_can_include_only_trashed()
    {
        UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->onlyTrashed();
            })
            ->get();

        $query = UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->onlyTrashed();
            })
            ->toSql();

        $this->assertQueryContains(
            'inner join "users" on "user_profiles"."user_id" = "users"."id"',
            $query
        );

        $this->assertQueryContains(
            '"users"."deleted_at" is not null',
            $query
        );
    }

    /** @test */
    public function it_can_disable_soft_deletes_when_using_an_alias()
    {
        // making sure the query doesn't fail
        UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->withTrashed();
            })->get();

        $query = UserProfile::query()
            ->joinRelationship('user', function ($join) {
                $join->as('myAlias');
                $join->withTrashed();
            })
            ->toSql();

        $this->assertQueryContains(
            'inner join "users" as "myAlias" on "user_profiles"."user_id" = "myAlias"."id"',
            $query
        );

        $this->assertStringNotContainsString(
            '"users"."deleted_at" is null',
            $query
        );
    }

    public function test_it_respects_with_trashed()
    {
        User::query()->joinRelationship('postsWithTrashed')->get();
        $sql = User::query()->joinRelationship('postsWithTrashed')->toSql();

        $this->assertQueryContains(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $sql
        );

        $this->assertStringNotContainsString(
            '"posts"."deleted_at" is null',
            $sql
        );
    }
}
