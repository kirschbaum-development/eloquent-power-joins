<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\PowerJoinClause;
use Kirschbaum\PowerJoins\PowerJoins;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class JoinRelationshipTest extends TestCase
{
    /** @test */
    public function test_join_first_level_relationship()
    {
        $query = User::query()->joinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_left_join_first_level_relationship()
    {
        $query = User::query()->leftJoinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'left join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_right_join_first_level_relationship()
    {
        $query = User::query()->rightJoinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'right join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_second_level_relationship()
    {
        $query = User::query()->joinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_left_join_second_level_relationship()
    {
        $query = User::query()->leftJoinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'left join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'left join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_right_join_second_level_relationship()
    {
        $query = User::query()->rightJoinRelationship('posts.comments')->toSql();

        $this->assertStringContainsString(
            'right join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'right join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_morph_relationship()
    {
        factory(Image::class, 5)->state('owner:post')->create();

        $query = Post::query()->joinRelationship('images')->toSql();
        $posts = Post::query()->joinRelationship('images')->get();

        $this->assertCount(5, $posts);
        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_morph_nested_relationship()
    {
        $query = User::query()->joinRelationship('posts.images')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_join()
    {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            $join->where('posts.published', true);
        });

        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_join_using_related_model_scopes()
    {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            // published() is an scope in the Post model
            // how awesome is that?
            $join->published();
        });

        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_join_using_custom_eloquent_builder_model_method()
    {
        $queryBuilder = User::query()->joinRelationship('posts', function ($join) {
            // whereReviewed() is an method of the custom in the PostBuilder builder in the Post model
            $join->whereReviewed();
        });

        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "reviewed" = ?',
            $query
        );
    }

    /** @test */
    public function test_apply_condition_to_nested_joins()
    {
        $queryBuilder = User::query()->joinRelationship('posts.comments', [
            'posts' => function ($join) {
                $join->where('posts.published', true);
            },
            'comments' => function ($join) {
                $join->where('comments.approved', true);
            },
        ]);
        $query = $queryBuilder->toSql();

        // running to make sure it doesn't throw any exceptions
        $queryBuilder->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "posts"."published" = ?',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'and "comments"."approved" = ?',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many()
    {
        $query = User::query()->joinRelationship('groups')->toSql();

        $this->assertStringContainsString(
            'inner join "group_members" on "group_members"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "groups" on "groups"."id" = "group_members"."group_id"',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many_with_callback()
    {
        $query = User::query()->joinRelationship('groups', [
            'groups' => function ($join) {
                $join->where('groups.name', 'Test');
            },
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "group_members" on "group_members"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "groups" on "groups"."id" = "group_members"."group_id" and "groups"."name" = ?',
            $query
        );
    }

    /** @test */
    public function test_it_doesnt_join_the_same_relationship_twice()
    {
        $query = User::query()
            ->select('users.*')
            ->joinRelationship('posts')
            ->joinRelationship('posts')
            ->toSql();

        // making sure it doesn't throw any errors
        User::query()->select('users.*')->joinRelationship('posts')->joinRelationship('posts')->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertEquals(
            1,
            substr_count($query, 'inner join "posts" on "posts"."user_id" = "users"."id"'),
            'It should only make 1 join with the posts table'
        );
    }

    /** @test */
    public function test_it_doesnt_join_the_same_relationship_twice_with_nested()
    {
        $query = User::query()
            ->select('users.*')
            ->joinRelationship('posts')
            ->joinRelationship('posts.comments')
            ->joinRelationship('posts.images')
            ->toSql();

        // making sure it doesn't throw any errors
        User::query()->select('users.*')
            ->joinRelationship('posts')
            ->joinRelationship('posts.comments')
            ->joinRelationship('posts.images')
            ->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertEquals(
            1,
            substr_count($query, 'inner join "posts" on "posts"."user_id" = "users"."id"'),
            'It should only make 1 join with the posts table'
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?',
            $query
        );
    }

    /** @test */
    public function test_it_doesnt_join_the_same_relationship_twice_with_complex_nested()
    {
        $query = User::query()
            ->select('users.*')
            ->leftJoinRelationship('posts')
            ->rightJoinRelationship('posts.comments')
            ->leftJoinRelationship('posts.images')
            ->joinRelationship('posts.category')
            ->leftJoinRelationship('posts.category.parent', [
                'parent' => function ($join) {
                    $join->as('category_parent');
                },
            ])
            ->toSql();

        // making sure it doesn't throw any errors
        User::query()
            ->select('users.*')
            ->leftJoinRelationship('posts.comments')
            ->leftJoinRelationship('posts.images')
            ->leftJoinRelationship('posts.category')
            ->leftJoinRelationship('posts.category.parent', [
                'parent' => function ($join) {
                    $join->as('category_parent');
                },
            ])->get();

        $this->assertStringContainsString(
            'left join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertEquals(
            1,
            substr_count($query, 'left join "posts" on "posts"."user_id" = "users"."id"'),
            'It should only make 1 join with the posts table'
        );

        $this->assertStringContainsString(
            'right join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'left join "images" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ?',
            $query
        );

        $this->assertStringContainsString(
            'inner join "categories" on "posts"."category_id" = "categories"."id"',
            $query
        );

        $this->assertStringContainsString(
            'left join "categories" as "category_parent" on "categories"."parent_id" = "category_parent"."id"',
            $query
        );
    }

    /** @test */
    public function test_it_runs_same_relationship_twice_if_defined_in_different_places()
    {
        $query = User::query()
            ->joinRelationship('comments')
            ->joinRelationship('posts.comments', [
                'comments' => function ($join) {
                    $join->as('post_comments');
                }
            ])
            ->toSql();

        User::query()
            ->joinRelationship('comments')
            ->joinRelationship('posts.comments', [
                'comments' => function ($join) {
                    $join->as('post_comments');
                }
            ])
            ->get();

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" as "post_comments" on "post_comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_it_join_belongs_to_relationship()
    {
        $posts = factory(Post::class)->times(2)->create();

        $queriesPosts = Post::query()
            ->select('posts.id', 'users.name')
            ->joinRelationship('user')
            ->get();

        $this->assertCount(2, $queriesPosts);
        $this->assertEquals($posts->get(0)->user->name, $queriesPosts->get(0)->name);
        $this->assertEquals($posts->get(1)->user->name, $queriesPosts->get(1)->name);
    }

    /** @test */
    public function test_it_join_nested_belongs_to_relationship()
    {
        [$comment1, $comment2, $comment3] = factory(Comment::class, 3)->create();

        // deleting this user, which will make the user soft deleted
        // this should make the user NOT come in the query, since we are INNER JOINING
        $comment3->post->user->delete();

        $comments = Comment::query()
            ->select('posts.title', 'users.name')
            ->joinRelationship('post.user')
            ->get();

        $this->assertCount(2, $comments);
        $this->assertEquals($comment1->post->user->name, $comments->get(0)->name);
        $this->assertEquals($comment2->post->user->name, $comments->get(1)->name);
    }

    /** @test */
    public function test_join_model_with_soft_deletes()
    {
        $query = UserProfile::query()->joinRelationship('user')->toSql();

        $this->assertStringContainsString(
            'inner join "users" on "user_profiles"."user_id" = "users"."id" and "users"."deleted_at" is null',
            $query
        );
    }

    /** @test */
    public function test_join_model_has_one_with_alias_and_select()
    {
        $profile = factory(UserProfile::class)->create();

        $user = User::query()
            ->select('profile.city')
            ->leftJoinRelationship('profile', function ($join) {
                $join->as('profile');
            })
            ->first();

        $this->assertEquals($profile->city, $user->city);
    }

    /** @test */
    public function test_join_with_alias_using_alias_as_string()
    {
        $innerJoinQuery = User::query()->select('profile.city')->joinRelationship('profile', 'p')->toSql();
        $leftJoinQuery = User::query()->select('profile.city')->leftJoinRelationship('profile', 'p')->toSql();

        $this->assertStringContainsString('inner join "user_profiles" as "p"', $innerJoinQuery);
        $this->assertStringContainsString('left join "user_profiles" as "p"', $leftJoinQuery);
    }

    /** @test */
    public function test_it_automatically_includes_select_statement_if_not_defined()
    {
        $query = User::joinRelationship('posts')->toSql();

        $this->assertStringContainsString(
            'select "users".* from "users"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_it_does_notautomatically_includes_select_statement_if_already_defined()
    {
        $query = User::select('users.id')->joinRelationship('posts')->toSql();

        $this->assertStringNotContainsString(
            'select "users".* from "users"',
            $query
        );

        $this->assertStringContainsString(
            'select "users"."id" from "users"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_has_many_through_relationship()
    {
        // just making sure it runs fine
        User::joinRelationship('commentsThroughPosts')->get();

        $query = User::joinRelationship('commentsThroughPosts')->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $query
        );
    }

    /** @test */
    public function test_join_has_many_through_relationship_with_alias()
    {
        $query = User::joinRelationship('commentsThroughPosts.user', [
            'commentsThroughPosts' => [
                'posts' => fn ($join) => $join->as('posts_alias'),
                'comments' => fn ($join) => $join->as('comments_alias'),
            ],
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "posts" as "posts_alias"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "comments" as "comments_alias"',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many_relationship_with_alias()
    {
        $query = Group::joinRelationship('posts.user', [
            'posts' => [
                'posts' => fn ($join) => $join->as('posts_alias'),
                'post_groups' => fn ($join) => $join->as('post_groups_alias'),
            ],
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "posts" as "posts_alias"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "post_groups" as "post_groups_alias"',
            $query
        );
    }

    /** @test */
    public function test_join_belongs_to_many_with_alias()
    {
        $query = Group::joinRelationship('posts', [
            'posts' => [
                'posts' => fn ($join) => $join->as('posts_alias'),
                'post_groups' => fn ($join) => $join->as('post_groups_not_nested'),
            ],
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "posts" as "posts_alias"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "post_groups" as "post_groups_not_nested"',
            $query
        );
    }

    /** @test */
    public function test_it_joins_different_tables_with_same_relationship_name()
    {
        $query = Post::query()
            ->joinRelationship('translations')
            ->joinRelationship('images.translations')
            ->toSql();

        $this->assertStringContainsString(
            'inner join "post_translations" on "post_translations"."post_id" = "posts"."id"',
            $query
        );

        $this->assertStringContainsString(
            'inner join "image_translations" on "image_translations"."image_id" = "images"."id"',
            $query
        );
    }

    /** @test */
    public function test_passing_where_closure_inside_join_callback()
    {
        $query = Post::query()
            ->joinRelationship('category', function ($join) {
                $join->as('category_alias')
                    ->where(function ($query) {
                        $query->whereNull('category_alias.parent_id')
                            ->orWhere('category_alias.parent_id', 3);
                    });
            });

        $sql = $query->toSql();

        // executing to make sure it does not throw exceptions
        $query->get();

        $this->assertStringContainsString(
            'inner join "categories" as "category_alias" on "posts"."category_id" = "category_alias"."id" and ("category_alias"."parent_id" is null or "category_alias"."parent_id" = ?)',
            $sql
        );
    }

    /** @test */
    public function test_nested_join_with_aliases()
    {
        $query = Post::query()
            ->where('posts.id', '>', 10)
            ->joinRelationship('category.parent', [
                'category' => fn ($join) => $join->as('category_alias'),
                'parent' => fn ($join) => $join->as('parent_alias'),
            ])
            ->get();

        // if it does not throw any exceptions, we are good
        $this->assertTrue(true);
    }

    /** @test */
    public function test_join_same_relationship_using_named_alias()
    {
        $query = Post::query()
            ->where('posts.id', '>', 10)
            ->joinRelationship('category', function ($join) {
                $join->as('category_1');
            })
            ->joinRelationship('category', function ($join) {
                $join->as('category_2');
            })
            ->toSql();

        $this->assertStringContainsString('inner join "categories" as "category_1" on "posts"."category_id" = "category_1"."id"', $query);
        $this->assertStringContainsString('inner join "categories" as "category_2" on "posts"."category_id" = "category_2"."id"', $query);
    }

    /** @test */
    public function test_join_same_nested_relationship_using_named_alias()
    {
        $this->markTestSkipped('Still to implement this using the array syntax');

        $query = Post::query()
            ->where('posts.id', '>', 10)
            ->joinRelationship('category.parent', [
                'category' => fn ($join) => $join->as('category_alias_1'),
                'parent' => fn ($join) => $join->as('parent_alias_1'),
            ])
            ->joinRelationship('category.parent', [
                'category' => fn ($join) => $join->as('category_alias_2'),
                'parent' => fn ($join) => $join->as('parent_alias_2'),
            ])
            ->toSql();

        $this->assertStringContainsString('inner join "categories" as "category_alias_1" on "posts"."category_id" = "category_alias_1"."id"', $query);
        $this->assertStringContainsString('inner join "categories" as "category_alias_2" on "posts"."category_id" = "category_alias_2"."id"', $query);
    }

    public function test_union_query()
    {
        $query1 = User::joinRelationship('posts');
        $query2 = User::joinRelationship('posts.comments');

        $sql = $query1->union($query2)->toSql();

        // making sure it doesn't trigger any exceptions
        $query1->union($query2)->get();

        $this->assertStringContainsString(
            'inner join "posts" on "posts"."user_id" = "users"."id"',
            $sql
        );

        $this->assertStringContainsString(
            'inner join "comments" on "comments"."post_id" = "posts"."id"',
            $sql
        );
    }

    public function test_scope_inside_nested_where()
    {
        Comment::query()->joinRelationship('post', function ($join) {
            $join->where(fn ($query) => $query->published());
        })->get();

        $sql = Comment::query()->joinRelationship('post', function ($join) {
            $join->where(fn ($query) => $query->published());
        })->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "comments"."post_id" = "posts"."id" and "posts"."deleted_at" is null and ("posts"."published" = ?)',
            $sql
        );
    }

    public function test_it_can_type_hint_power_join_clause()
    {
        Comment::query()->joinRelationship('post', function ($join) {
            $join->where(fn (PowerJoinClause $query) => $query->published());
        })->get();

        $sql = Comment::query()->joinRelationship('post', function ($join) {
            $join->where(fn (PowerJoinClause $query) => $query->published());
        })->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "comments"."post_id" = "posts"."id" and "posts"."deleted_at" is null and ("posts"."published" = ?)',
            $sql
        );
    }

    public function test_it_can_alias_belongs_to_many()
    {
        Group::query()->joinRelationship('posts', [
            'posts' => fn ($join) => $join->as('posts_1')->where('id', 2),
            'post_groups' => fn ($join) => $join->as('pivot_posts_1'),
        ])->get();

        $sql = Group::query()->joinRelationship('posts', [
            'posts' => fn ($join) => $join->as('posts_1')->where('id', 2),
            'post_groups' => fn ($join) => $join->as('pivot_posts_1'),
        ])->toSql();

        $this->assertStringContainsString(
            'inner join "post_groups" as "pivot_posts_1" on "pivot_posts_1"."group_id" = "groups"."id"',
            $sql
        );

        $this->assertStringContainsString(
            'inner join "posts" as "posts_1" on "posts_1"."id" = "pivot_posts_1"."post_id" and "posts_1"."id" = ?',
            $sql
        );
    }

    public function test_has_one_of_many()
    {
        $post = factory(Post::class)->create();
        $bestComment = factory(Comment::class)->state('approved')->create(['body' => 'best comment', 'votes' => 2]);
        $lastComment = factory(Comment::class)->state('approved')->create(['body' => 'worst comment', 'votes' => 0]);

        $bestCommentSql = Post::query()
            ->select('posts.*', 'comments.body')
            ->joinRelationship('bestComment')
            ->toSql();

        $bestCommentPost = Post::query()
            ->select('posts.*', 'comments.body')
            ->joinRelationship('bestComment')
            ->first();

        $this->assertStringContainsString(
            'max("comments"."votes") as "votes_aggregate"',
            $bestCommentSql
        );

        $this->assertEquals($bestComment->body, $bestCommentPost->body);

        $lastCommentSql = Post::query()
            ->select('posts.*', 'comments.body')
            ->joinRelationship('lastComment')
            ->toSql();

        $lastCommentPost = Post::query()
            ->select('posts.*', 'comments.body')
            ->joinRelationship('lastComment')
            ->first();

        $this->assertStringContainsString(
            'max("comments"."id") as "id_aggregate"',
            strtolower($lastCommentSql)
        );

        $this->assertEquals($lastComment->body, $lastCommentPost->body);
    }

    public function test_join_with_clone_does_not_duplicate()
    {
        $query = Post::query();

        $query->leftJoinRelationship('user');
        $clonedSql = $query->clone()->leftJoinRelationship('user')->toSql();
        $sql = $query->toSql();

        $this->assertEquals($clonedSql, $sql);
    }

    public function test_it_doesnt_fail_to_join_the_same_query_repeatedly()
    {
        for ($i = 0; $i < 12; $i++) {
            try {
                (new Post)->query()
                    ->selectRaw('users.id as user_id')
                    ->joinRelationship('user')
                    ->get();

                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->assertTrue(false, 'If it throws an exceptions, means the already joined checks are failing');
            }
        }
    }

    public function test_join_morph_to_morphable_class()
    {
        $publishedPost = factory(Post::class)->state('published')->create();
        $unpublishedPost = factory(Post::class)->state('unpublished')->create();
        factory(Image::class, 3)->state('owner:post')->create(['imageable_id' => $unpublishedPost->id]);
        factory(Image::class, 2)->state('owner:post')->create(['imageable_id' => $publishedPost->id]);
        factory(Image::class, 4)->state('owner:user')->create();

        $publishedPostImages = Image::query()
            ->joinRelationship('imageable', callback: fn ($join) => $join->published(), morphable: Post::class)
            ->get();

        $postImages = Image::query()
            ->joinRelationship('imageable', morphable: Post::class)
            ->get();

        $sql = Image::query()
            ->joinRelationship('imageable', morphable: Post::class)
            ->toSql();

        $this->assertStringContainsString(
            'inner join "posts" on "images"."imageable_id" = "posts"."id" and "images"."imageable_type" = ? and "posts"."deleted_at" is null',
            $sql
        );

        $this->assertCount(5, $postImages);
        $this->assertCount(2, $publishedPostImages);
    }
}
