<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use KirschbaumDevelopment\EloquentJoins\Tests\Models\Category;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;

class JoinRelationshipUsingAliasTest extends TestCase
{
    /**
     * @test
     */
    public function test_joining_using_auto_generated_alias()
    {
        $category = factory(Category::class)->state('with:parent')->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        $posts = Post::joinRelationshipUsingAlias('category')->get();

        $this->assertCount(1, $posts);
    }

    /**
     * @test
     */
    public function test_joining_the_same_table_twice_using_aliases()
    {
        $category = factory(Category::class)->state('with:parent')->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);
        $posts = Post::joinRelationshipUsingAlias('category.parent')->get();

        $this->assertCount(1, $posts);
    }

    /**
     * @test
     */
    public function test_alias_for_has_many()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $post = factory(Post::class)->create(['user_id' => $user1->id]);

        $users = User::joinRelationshipUsingAlias('posts')->get();
        $query = User::joinRelationshipUsingAlias('posts')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"posts" as', $query);
        $this->assertStringNotContainsString('"posts"."user_id"', $query);
    }

    /**
     * @test
     */
    public function test_alias_for_has_many_nested()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $post = factory(Post::class)->create(['user_id' => $user1->id]);
        $comment = factory(Comment::class)->create(['post_id' => $post->id]);

        $users = User::joinRelationshipUsingAlias('posts.comments')->get();
        $query = User::joinRelationshipUsingAlias('posts.comments')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"posts" as', $query);
        $this->assertStringContainsString('"comments" as', $query);
        $this->assertStringNotContainsString('"posts"."user_id"', $query);
        $this->assertStringNotContainsString('"posts"."id"', $query);
        $this->assertStringNotContainsString('"comments"."post_id"', $query);
    }
}
