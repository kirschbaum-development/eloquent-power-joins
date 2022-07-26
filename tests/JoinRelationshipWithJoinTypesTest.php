<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class JoinRelationshipWithJoinTypesTest extends TestCase
{

    /**
     * $category_1
     *      |- $post_1_1 (published)
     *      |- $post_1_2 (published)
     *
     * $category_2
     *      |- $post_2_1 (unpublished)
     */
    protected function prepare_test_case_1(){
        $category_1 = factory(Category::class)->create();
        $category_2 = factory(Category::class)->create(); // with no posts assigned

        $post_1_1 = factory(Post::class)->create(['category_id' => $category_1->id]);
        $post_1_2 = factory(Post::class)->create(['category_id' => $category_1->id]);

        $post_2_1 = factory(Post::class)->create(['category_id' => $category_2->id, 'published' => false]);
    }

    /**
     * @test
     */
    public function test_categoreis_inner_join_published_posts() {
        $this->prepare_test_case_1();

        $categories = Category::joinRelationship('posts', [
            'posts' => function($join){
                $join->where('posts.published', true);
            }
        ]);
        // should only get categories with assigned posts.
        dump($categories->toSql(), $categories->get()->toArray());
        $this->assertCount(2, $categories);
    }

    public function test_categoreis_left_join_published_posts() {
        // $this->prepare_test_case_1();

        $categories = Category::joinRelationship('posts', [
            'posts' => function($join){
                $join->where('posts.published', true);
                $join->left();
            }
        ]);

        dump($categories->toSql(), $categories->get()->toArray());
        $this->assertCount(3, $categories);
    }

}
