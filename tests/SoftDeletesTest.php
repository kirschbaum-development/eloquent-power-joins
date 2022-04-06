<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Category;
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

        $this->assertStringContainsString(
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

        $this->assertStringContainsString(
            'inner join "users" on "user_profiles"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
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

        $this->assertStringContainsString(
            'inner join "users" as "myAlias" on "user_profiles"."user_id" = "myAlias"."id"',
            $query
        );

        $this->assertStringNotContainsString(
            '"users"."deleted_at" is null',
            $query
        );
    }
}
