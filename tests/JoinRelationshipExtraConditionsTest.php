<?php

namespace Kirschbaum\EloquentPowerJoins\Tests;

use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\User;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Comment;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Category;
use Kirschbaum\EloquentPowerJoins\Tests\Models\UserProfile;

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
    public function test_join_has_many_relationship_with_additional_conditions()
    {
        [$category1, $category2] = factory(Category::class, 2)->create();
        factory(Post::class)->states('published')->create(['category_id' => $category1->id]);

        $query = Category::joinRelationship('publishedPosts')->toSql();
        $categories = Category::joinRelationship('publishedPosts')->get();

        $this->assertCount(1, $categories);
        $this->assertEquals($category1->id, $categories->first()->id);

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."category_id" = "categories"."id" and "posts"."published" = ?',
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
            'inner join "user_profiles" on "user_profiles"."user_id" = "users"."id" and "city" is not null',
            $query
        );
    }
}
