<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use KirschbaumDevelopment\EloquentJoins\Tests\Models\Category;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;

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
}
