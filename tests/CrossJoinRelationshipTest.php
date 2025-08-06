<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\Tag;
use Kirschbaum\PowerJoins\Tests\Models\User;

class CrossJoinRelationshipTest extends TestCase
{
    /**
     * @test
     */
    public function test_cross_join_relationship_basic()
    {
        $user1 = factory(User::class)->create(['name' => 'User 1']);
        $user2 = factory(User::class)->create(['name' => 'User 2']);

        $post1 = factory(Post::class)->create(['title' => 'Post 1', 'user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['title' => 'Post 2', 'user_id' => $user2->id]);

        $query = User::crossJoinRelationship('posts');
        $sql = $query->toSql();

        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringNotContainsString('on', $sql);

        $results = $query->get();

        // Cross join should produce cartesian product: 2 users × 2 posts = 4 results
        $this->assertCount(4, $results);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_alias()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationshipUsingAlias('posts');
        $sql = $query->toSql();

        $this->assertStringContainsString('cross join "posts" as', $sql);
        $this->assertStringNotContainsString('on', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_callback()
    {
        $user = factory(User::class)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user->id, 'published' => true]);
        $post2 = factory(Post::class)->create(['user_id' => $user->id, 'published' => false]);

        $query = User::crossJoinRelationship('posts', function ($join) {
            $join->where('posts.published', true);
        });

        $sql = $query->toSql();
        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringContainsString('"posts"."published" = ?', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_model_scope()
    {
        $user = factory(User::class)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user->id, 'published' => true]);
        $post2 = factory(Post::class)->create(['user_id' => $user->id, 'published' => false]);

        $query = User::crossJoinRelationship('posts', function ($join) {
            $join->published();
        });

        $sql = $query->toSql();
        // Debug output
        // echo "SQL: " . $sql . PHP_EOL;
        // echo "Bindings: " . json_encode($query->getBindings()) . PHP_EOL;

        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringContainsString('"posts"."published" = ?', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_nested()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $comment = factory(Comment::class)->create(['post_id' => $post->id]);

        $query = User::crossJoinRelationship('posts.comments');
        $sql = $query->toSql();

        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringContainsString('cross join "comments"', $sql);
        $this->assertStringNotContainsString('on', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_belongs_to_many()
    {
        $post = factory(Post::class)->create();
        $tag1 = factory(Tag::class)->create(['name' => 'Tag 1']);
        $tag2 = factory(Tag::class)->create(['name' => 'Tag 2']);

        $post->tags()->attach([$tag1->id, $tag2->id]);

        $query = Post::crossJoinRelationship('tags');
        $sql = $query->toSql();

        $this->assertStringContainsString('cross join "taggables"', $sql);
        $this->assertStringContainsString('cross join "tags"', $sql);
        $this->assertStringNotContainsString('on', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_alias_callback()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationship('posts', function ($join) {
            $join->as('p');
        });

        $sql = $query->toSql();
        $this->assertStringContainsString('cross join "posts" as "p"', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_cartesian_product()
    {
        // Create test data
        $category1 = factory(Category::class)->create(['title' => 'Category 1']);
        $category2 = factory(Category::class)->create(['title' => 'Category 2']);

        $post1 = factory(Post::class)->create(['title' => 'Post 1', 'category_id' => $category1->id]);
        $post2 = factory(Post::class)->create(['title' => 'Post 2', 'category_id' => $category2->id]);

        $results = Category::crossJoinRelationship('posts')
            ->select('categories.title as category_title', 'posts.title as post_title')
            ->get();

        // Should get cartesian product: 2 categories × 2 posts = 4 results
        $this->assertCount(4, $results);

        // Verify we get all combinations
        $combinations = $results->map(function ($result) {
            return $result->category_title.' - '.$result->post_title;
        })->sort()->values()->toArray();

        $expected = [
            'Category 1 - Post 1',
            'Category 1 - Post 2',
            'Category 2 - Post 1',
            'Category 2 - Post 2',
        ];

        $this->assertEquals($expected, $combinations);
    }

    /**
     * @test
     */
    public function test_cross_join_relation_alias_method()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelation('posts');
        $sql = $query->toSql();

        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringNotContainsString('on', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_join_type_callback()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationship('posts', function ($join) {
            $join->cross(); // Should maintain cross join type
        });

        $sql = $query->toSql();
        $this->assertStringContainsString('cross join "posts"', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_prevents_duplicate_joins()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationship('posts')
            ->crossJoinRelationship('posts'); // Second call should be ignored

        $sql = $query->toSql();

        // Should only have one cross join
        $this->assertEquals(1, substr_count($sql, 'cross join "posts"'));
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_soft_deletes()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationship('posts');
        $sql = $query->toSql();

        // Should include soft delete condition for posts
        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringContainsString('"posts"."deleted_at" is null', $sql);
    }

    /**
     * @test
     */
    public function test_cross_join_relationship_with_trashed()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        $query = User::crossJoinRelationship('posts', function ($join) {
            $join->withTrashed();
        });

        $sql = $query->toSql();

        // Should not include soft delete condition
        $this->assertStringContainsString('cross join "posts"', $sql);
        $this->assertStringNotContainsString('"posts"."deleted_at" is null', $sql);
    }
}
