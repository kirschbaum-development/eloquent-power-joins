<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Category;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

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
    public function test_joining_using_provided_alias()
    {
        $category = factory(Category::class)->state('with:parent')->create();
        $post = factory(Post::class)->create(['category_id' => $category->id]);

        $sql = Post::joinRelationshipUsingAlias('category', 'my_alias')->toSql();

        $this->assertQueryContains('my_alias', $sql);
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

        $posts = Post::query()->joinRelationship('category.parent', [
            'parent' => function ($join) {
                $join->as('category_parent');
            },
        ])->get();

        $query = Post::query()->joinRelationship('category.parent', [
            'parent' => function ($join) {
                $join->as('category_parent');
            },
        ])->toSql();

        $this->assertCount(1, $posts);
        $this->assertQueryContains(
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
        $this->assertQueryContains('"posts" as', $query);
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
        $this->assertQueryContains('"posts" as', $query);
        $this->assertQueryContains('"comments" as', $query);
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
        $this->assertQueryContains('"group_members" as', $query);
        $this->assertQueryContains('"groups" as', $query);
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

        $query = User::query()->joinRelationshipUsingAlias('groups.posts')->toSql();
        $users = User::query()->joinRelationshipUsingAlias('groups.posts')->get();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->id, $users->first()->id);
        $this->assertQueryContains('"group_members" as', $query);
        $this->assertQueryContains('"groups" as', $query);
        $this->assertQueryContains('"post_groups" as', $query);
        $this->assertQueryContains('"posts" as', $query);
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
        $this->assertQueryContains('"user_profiles" as', $query);
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
        $this->assertQueryContains('"posts" as', $query);
        $this->assertStringNotContainsString('"posts"."user_id"', $query);
        $this->assertQueryContains('"comments" as', $query);
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

        $this->assertQueryContains('inner join "groups" as "groups_2" on "groups_2"."id" = "group_parent"."group_id"', $query);
    }

    /** @test */
    public function test_joining_deep_relation_using_same_base_table()
    {
        $alias = [
            'posts' => function ($join) {
                $join->as('post_alias');
            },
            'comments' => function ($join) {
                $join->as('comments_alias');
            },
            'category' => function ($join) {
                $join->as('categories_alias');
            },
        ];
        $query = User::joinRelationship('posts.comments', $alias)->joinRelationship('posts.category', $alias);
        $sql = $query->toSql();
        $query->get();

        $this->assertQueryContains('inner join "categories" as "categories_alias" on "post_alias"."category_id" = "categories_alias"."id"', $sql);
    }

    /** @test */
    public function test_joining_deep_many_to_many_relation_using_same_base_table()
    {
        $alias = [
            'groups' => function ($join) {
                $join->as('groups_alias');
            },
            'posts' => [
                'post_groups' => function ($join) {
                    $join->as('post_groups_alias');
                },
                'posts' => function ($join) {
                    $join->as('post_alias');
                },
            ],
        ];
        $query = User::joinRelationship('groups', $alias)->joinRelationship('groups.posts', $alias);
        $sql = $query->toSql();
        $this->assertQueryContains('inner join "posts" as "post_alias" on "post_alias"."id" = "post_groups_alias"."post_id"', $sql);
    }

    /** @test */
    public function test_morph_join_using_alias()
    {
        $query = Post::query()
            ->with(['images'])
            ->joinRelationshipUsingAlias('images', 'foo')
            ->toSql();

        Post::query()
            ->with(['images'])
            ->joinRelationshipUsingAlias('images', 'foo')
            ->get();

        $this->assertQueryContains(
            'inner join "images" as "foo" on "foo"."imageable_id" = "posts"."id" and "foo"."imageable_type" = ?',
            $query
        );
    }
}
