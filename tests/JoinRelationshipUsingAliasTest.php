<?php

namespace Kirschbaum\EloquentPowerJoins\Tests;

use Kirschbaum\EloquentPowerJoins\Tests\Models\Category;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Comment;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Group;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\User;
use Kirschbaum\EloquentPowerJoins\Tests\Models\UserProfile;

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
    public function test_joining_the_same_table_twice_using_alias_with_join_as()
    {
        $category = factory(Category::class)->state('with:parent')->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        $posts = Post::joinRelationship('category.parent', [
            'parent' => function ($join) {
                $join->as('category_parent');
            },
        ])->get();

        $query = Post::joinRelationship('category.parent', [
            'parent' => function ($join) {
                $join->as('category_parent');
            },
        ])->toSql();

        $this->assertCount(1, $posts);
        $this->assertStringContainsString(
            'inner join "categories" as "category_parent" on "categories"."parent_id" = "category_parent"."id"',
            $query
        );
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

    /**
     * @test
     */
    public function test_alias_for_belongs_to_many()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $group = factory(Group::class)->create();
        $user1->groups()->attach($group);

        $users = User::query()->joinRelationshipUsingAlias('groups')->get();
        $query = User::query()->joinRelationshipUsingAlias('groups')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"group_members" as', $query);
        $this->assertStringContainsString('"groups" as', $query);
    }

    /**
     * @test
     */
    public function test_alias_for_belongs_to_many_nested()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $post = factory(Post::class)->create();
        $group = factory(Group::class)->create();
        $user1->groups()->attach($group);
        $group->posts()->attach($post);

        $users = User::query()->joinRelationshipUsingAlias('groups.posts')->get();
        $query = User::query()->joinRelationshipUsingAlias('groups.posts')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"group_members" as', $query);
        $this->assertStringContainsString('"groups" as', $query);
        $this->assertStringContainsString('"post_groups" as', $query);
        $this->assertStringContainsString('"posts" as', $query);
    }

    /**
     * @test
     */
    public function test_alias_for_has_one()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $profile = factory(UserProfile::class)->create(['user_id' => $user1->id]);

        $users = User::joinRelationshipUsingAlias('profile')->get();
        $query = User::joinRelationshipUsingAlias('profile')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"user_profiles" as', $query);
        $this->assertStringNotContainsString('"user_profiles"."user_id"', $query);
    }

    /**
     * @test
     */
    public function test_alias_for_has_many_through()
    {
        [$user1, $user2] = factory(User::class, 2)->create();
        $post = factory(Post::class)->create(['user_id' => $user1->id]);
        $comment = factory(Comment::class)->create(['post_id' => $post->id]);

        $users = User::joinRelationshipUsingAlias('commentsThroughPosts')->get();
        $query = User::joinRelationshipUsingAlias('commentsThroughPosts')->toSql();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertStringContainsString('"posts" as', $query);
        $this->assertStringNotContainsString('"posts"."user_id"', $query);
        $this->assertStringContainsString('"comments" as', $query);
        $this->assertStringNotContainsString('"comments"."post_id"', $query);
    }

    /**
     * @test
     */
    public function test_joining_the_same_table_twice_with_belongs_to_many()
    {
        $query = User::joinRelationship('groups.parentGroups', [
            'parentGroups' => [
                'groups' => function ($join) {
                    $join->as('groups_2');
                },
            ],
        ])->toSql();

        $this->assertStringContainsString('inner join "groups" as "groups_2" on "groups_2"."id" = "group_parent"."group_id"', $query);
    }
}
