<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\PowerJoins;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Category;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;
use Kirschbaum\PowerJoins\Tests\Models\PostWithGlobalScope;

class JoinWithGlobalScopeTest extends TestCase
{
    /** @test */
    public function test_join_with_global_scope_applied()
    {
        $user = new class extends User {
            public function posts(): HasMany
            {
                return $this->hasMany(PostWithGlobalScope::class, 'user_id');
            }
        };

        $this->assertCount(0, $user->query()->joinRelationship('posts', fn ($join) => $join->withGlobalScopes())->get());

        $query = $user->query()->joinRelationship('posts', fn ($join) => $join->withGlobalScopes())->toSql();
        $this->assertStringContainsString('"posts"."published" = ?', $query);
    }
}
