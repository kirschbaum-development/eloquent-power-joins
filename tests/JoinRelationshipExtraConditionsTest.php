<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class JoinRelationshipExtraConditionsTest extends TestCase
{
    /** @test */
    public function test_join_belongs_to_with_additional_conditions()
    {
        $user1 = factory(User::class)->state('trashed')->create();
        $user2 = factory(User::class)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['user_id' => $user2->id]);

        $query = Post::query()->joinRelationship('userWithTrashed')->toSql();
        $posts = Post::query()->joinRelationship('userWithTrashed')->get();

        $this->assertCount(1, $posts);

        $this->assertStringContainsString(
            'inner join "users" on "posts"."user_id" = "users"."id" and "users"."deleted_at" is null',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_with_additional_conditions_and_alias()
    {
        $user1 = factory(User::class)->state('rockstar')->create();
        $user2 = factory(User::class)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['user_id' => $user2->id]);

        $query = Post::query()->joinRelationshipUsingAlias('rockstarUser')->toSql();
        $posts = Post::query()->joinRelationshipUsingAlias('rockstarUser')->get();

        $this->assertCount(1, $posts);

        $this->assertStringContainsString(
            '."rockstar" = ?',
            $query
        );

        $this->assertStringNotContainsString(
            'and "users"."rockstar" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_has_many_relationship_with_additional_conditions()
    {
        [$category1, $category2] = factory(Category::class, 2)->create();
        factory(Post::class)->states('published')->create(['category_id' => $category1->id]);

        $query = Category::joinRelationship('publishedPosts')->toSql();
        $categories = Category::joinRelationship('publishedPosts')->get();

        $this->assertCount(1, $categories);
        $this->assertEquals($category1->id, $categories->first()->id);

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."category_id" = "categories"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_has_many_relationship_with_additional_conditions_and_alias()
    {
        [$category1, $category2] = factory(Category::class, 2)->create();
        factory(Post::class)->states('published')->create(['category_id' => $category1->id]);

        $query = Category::joinRelationshipUsingAlias('publishedPosts')->toSql();
        $categories = Category::joinRelationshipUsingAlias('publishedPosts')->get();

        $this->assertCount(1, $categories);
        $this->assertEquals($category1->id, $categories->first()->id);

        $this->assertStringNotContainsString(
            'inner join "posts" on "posts"."category_id" = "categories"."id"',
            $query
        );

        $this->assertStringContainsString(
            '."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_has_one_relationship_with_additional_conditions()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        factory(UserProfile::class)->create(['user_id' => $user1->id, 'city' => 'Porto Alegre']);
        factory(UserProfile::class)->create(['user_id' => $user2->id, 'city' => null]);

        $query = User::joinRelationship('profileWithCity')->toSql();
        $users = User::joinRelationship('profileWithCity')->get();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);

        $this->assertStringContainsString(
            'inner join "user_profiles" on "user_profiles"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "city" is not null',
            $query
        );
    }

    /** @test */
    public function test_extra_conditions_with_belongs_to_many()
    {
        $publishedPost = factory(Post::class)->state('published')->create();
        $group1 = factory(Group::class)->create();
        $group1->posts()->attach($publishedPost);

        $unpublishedPost = factory(Post::class)->state('unpublished')->create();
        $group2 = factory(Group::class)->create();
        $group2->posts()->attach($unpublishedPost);

        $this->assertCount(2, Group::joinRelationship('posts')->get());
        $this->assertCount(1, Group::joinRelationship('publishedPosts')->get());

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."id" = "post_groups"."post_id" and "posts"."published" = ?',
            Group::joinRelationship('publishedPosts')->toSql()
        );
    }

    /** @test */
    public function test_extra_conditions_in_pivot_with_belongs_to_many_in()
    {
        $publishedPosts = factory(Post::class, 2)->state('published')->create();
        $group1 = factory(Group::class)->create();
        $publishedPosts->each(function ($publishedPost) use ($group1) {
            $group1->posts()->attach($publishedPost, ['assigned_at' => now()]);
        });

        $oldPost = factory(Post::class)->state('unpublished')->create();
        $group2 = factory(Group::class)->create();
        $group1->posts()->attach($oldPost, ['assigned_at' => now()->subWeeks(2)]);

        $this->assertCount(3, Group::joinRelationship('posts')->get());
        $this->assertCount(2, Group::joinRelationship('recentPosts')->get());

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."id" = "post_groups"."post_id" and "post_groups"."assigned_at" >= ?',
            Group::joinRelationship('recentPosts')->toSql()
        );
    }

    /** @test */
    public function test_extra_conditions_in_morph_many()
    {
        factory(Image::class)->states(['owner:post', 'cover'])->create();
        factory(Image::class)->states(['owner:post'])->create();

        $query = Post::joinRelationship('coverImages')->toSql();
        $posts = Post::joinRelationship('coverImages')->get();

        $this->assertCount(1, $posts);

        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ? and "cover" = ?',
            $query
        );
    }

    /** @test */
    public function test_extra_conditions_with_closure()
    {
        $query = User::joinRelationship('publishedPosts')->toSql();
        User::joinRelationship('publishedPosts')->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id" and ("published" = ?)',
            $query
        );
    }
}
