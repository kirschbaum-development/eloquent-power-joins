<?php

namespace Kirschbaum\PowerJoins\Tests;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Kirschbaum\PowerJoins\Tests\Models\PostWithClosureGlobalScope;
use Kirschbaum\PowerJoins\Tests\Models\PostWithGlobalScope;
use Kirschbaum\PowerJoins\Tests\Models\User;

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
        $this->assertQueryContains('"posts"."published" = ?', $query);
    }

    public function test_join_with_closure_global_scope_applied()
    {
        $user = new class extends User {
            public function posts(): HasMany
            {
                return $this->hasMany(PostWithClosureGlobalScope::class, 'user_id');
            }
        };

        $this->assertCount(0, $user->query()->joinRelationship('posts', fn ($join) => $join->withGlobalScopes())->get());

        $query = $user->query()->joinRelationship('posts', fn ($join) => $join->withGlobalScopes())->toSql();
        $this->assertQueryContains('"posts"."published" = ?', $query);
    }
}
