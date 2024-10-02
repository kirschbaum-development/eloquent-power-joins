<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\Post;

class JoinRelationshipWithJoinTypesTest extends TestCase
{
    /**
     * $category_1
     *      |- $post_1_1 (published)
     *      |- $post_1_2 (published).
     *
     * $category_2
     *      |- $post_2_1 (unpublished)
     */
    protected function prepare_test_case_1()
    {
        $category_1 = factory(Category::class)->create();
        $category_2 = factory(Category::class)->create(); // with no posts assigned

        $post_1_1 = factory(Post::class)->create(['category_id' => $category_1->id]);
        $post_1_2 = factory(Post::class)->create(['category_id' => $category_1->id]);

        $post_2_1 = factory(Post::class)->create(['category_id' => $category_2->id, 'published' => false]);

        $post_no_category_1 = factory(Post::class)->create(['category_id' => 0, 'published' => true]);
        $post_no_category_2 = factory(Post::class)->create(['category_id' => 0, 'published' => false]);
        $post_no_category_3 = factory(Post::class)->create(['category_id' => 0, 'published' => false]);
    }

    /**
     * @test
     */
    public function test_categories_inner_join_published_posts()
    {
        $this->prepare_test_case_1();

        $categories_with_published_posts = Category::query()->joinRelationship('posts', function ($join) {
            $join->as('post');
            $join->published();
        });

        $categories_with_UNpublished_posts = Category::query()->joinRelationship('posts', function ($join) {
            $join->where('published', false);
        });

        // dump($categories_with_published_posts->toSql(), $categories_with_published_posts->get()->toArray());
        $this->assertCount(2, $categories_with_published_posts->get());
        $this->assertCount(1, $categories_with_UNpublished_posts->get());
    }

    /**
     * @test
     */
    public function test_categories_left_join_posts_num()
    {
        $this->prepare_test_case_1();

        $categories_with_posts_num = Category::query()->joinRelationship('posts', function ($join) {
            $join->as('post');
            $join->left();
            $join->published();
        })
            ->groupby('categories.id')
            ->select(['categories.*'])
            ->selectRaw('SUM(CASE WHEN post.id IS NULL THEN 0 ELSE 1 END) as posts_num')
            ->orderby('categories.id');

        $rows = $categories_with_posts_num->get()->toArray();
        // $rows = $categories_with_posts_num->get(); //TODO: Array to string conversion
        // dump($categories_with_posts_num->toSql());
        $this->assertCount(2, $rows);
        $this->assertEquals($rows[0]['posts_num'], 2);
        $this->assertEquals($rows[1]['posts_num'], 0);
    }

    /**
     * @test
     */
    public function test_categories_right_join_posts_inexistent_category()
    {
        $this->markTestSkipped('[SKIPPED] Right joins are not supported by SQLite, so unable to test in this environment');

        return;

        $this->prepare_test_case_1();

        // just to test right joins, we'll obtain posts through Category model
        $posts_inexistent_category = Category::query()->joinRelationship('posts', function ($join) {
            $join->as('post');
            $join->right();
            // $join->published();
        });

        $rows = $posts_inexistent_category->get()->toArray();

        $this->assertCount(3, $rows);
        // now only published
        $this->assertCount(1, (clone $posts_inexistent_category)->where('post.published', true)->get()->toArray());
        // now only unpublished
        $this->assertCount(2, (clone $posts_inexistent_category)->where('post.published', false)->get()->toArray());
    }
}
