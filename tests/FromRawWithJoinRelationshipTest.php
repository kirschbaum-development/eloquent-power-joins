<?php

namespace Kirschbaum\PowerJoins\Tests;

use Illuminate\Support\Facades\DB;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;

class FromRawWithJoinRelationshipTest extends TestCase
{
    /**
     * Get the boolean true value for the current database driver.
     */
    protected function getBooleanTrueValue(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'pgsql' => 'true',
            'sqlite' => '1',
            default => '1', // MySQL and others use 1
        };
    }

    /** @test */
    public function test_left_join_relationship_with_from_raw_cte()
    {
        // Skip CTE test for SQLite as it has limited CTE support in subqueries
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite has limited CTE support in subqueries');
        }

        $user = factory(User::class)->create(['name' => 'John Doe']);
        factory(Post::class)->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'published' => true,
        ]);

        $boolTrue = $this->getBooleanTrueValue();

        // Using fromRaw with a CTE (Common Table Expression)
        $query = Post::fromRaw("(
            WITH active_posts AS (
                SELECT * FROM posts WHERE published = {$boolTrue}
            )
            SELECT * FROM active_posts
        ) as posts")
            ->leftJoinRelationship('user');

        // Should not throw TypeError
        $result = $query->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Test Post', $result->first()->title);
    }

    /** @test */
    public function test_join_relationship_with_from_raw_subquery()
    {
        $user = factory(User::class)->create(['name' => 'Jane Doe']);
        factory(Post::class)->create([
            'user_id' => $user->id,
            'title' => 'Another Test',
            'published' => true,
        ]);

        $boolTrue = $this->getBooleanTrueValue();

        // Using fromRaw with a subquery
        $query = Post::fromRaw("(
            SELECT * FROM posts WHERE published = {$boolTrue}
        ) as posts")
            ->joinRelationship('user');

        // Should not throw TypeError
        $result = $query->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Another Test', $result->first()->title);
    }

    /** @test */
    public function test_order_by_left_power_joins_with_from_raw()
    {
        $user = factory(User::class)->create(['name' => 'Alice']);
        factory(Post::class)->create([
            'user_id' => $user->id,
            'title' => 'First Post',
            'published' => true,
        ]);

        $boolTrue = $this->getBooleanTrueValue();

        // Using fromRaw with orderByLeftPowerJoins
        $query = Post::fromRaw("(
            SELECT * FROM posts WHERE published = {$boolTrue}
        ) as posts")
            ->orderByLeftPowerJoins('user.name', 'asc');

        // Should not throw TypeError
        $result = $query->get();

        $this->assertCount(1, $result);
        $this->assertEquals('First Post', $result->first()->title);
    }

    /** @test */
    public function test_right_join_relationship_with_from_raw()
    {
        $user = factory(User::class)->create(['name' => 'Bob']);
        factory(Post::class)->create([
            'user_id' => $user->id,
            'title' => 'Right Join Test',
            'published' => true,
        ]);

        $boolTrue = $this->getBooleanTrueValue();

        // Using fromRaw with rightJoinRelationship
        $query = Post::fromRaw("(
            SELECT * FROM posts WHERE published = {$boolTrue}
        ) as posts")
            ->rightJoinRelationship('user');

        // Should not throw TypeError
        $result = $query->get();

        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    /** @test */
    public function test_nested_join_relationship_with_from_raw()
    {
        $user = factory(User::class)->create(['name' => 'Charlie']);
        $post = factory(Post::class)->create([
            'user_id' => $user->id,
            'title' => 'Nested Test',
            'published' => true,
        ]);
        factory(Comment::class)->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Test comment',
        ]);

        $boolTrue = $this->getBooleanTrueValue();

        // Using fromRaw with nested relationship
        $query = Post::fromRaw("(
            SELECT * FROM posts WHERE published = {$boolTrue}
        ) as posts")
            ->leftJoinRelationship('comments.user');

        // Should not throw TypeError
        $result = $query->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Nested Test', $result->first()->title);
    }
}
