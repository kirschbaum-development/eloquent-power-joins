<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;

class PowerJoinHasTest extends TestCase
{
    /** @test */
    public function test_has_using_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::has('posts')->get());
        $this->assertCount(1, User::powerJoinHas('posts')->get());
    }

    /** @test */
    public function test_has_using_joins_from_query_builder()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::query()->has('posts')->get());
        $this->assertCount(1, User::query()->powerJoinHas('posts')->get());
    }

    /** @test */
    public function test_where_has_using_joins_and_model_scopes()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $postUser1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $postUser2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function ($query) {
            $query->where('posts.published', true);
        })->get());

        $this->assertCount(1, User::powerJoinWhereHas('posts', function ($join) {
            $join->published();
        })->get());
    }

    /** @test */
    public function test_where_has_from_model_scope()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $postUser1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $postUser2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::query()->hasPublishedPosts()->get());
    }

    /** @test */
    public function test_has_using_joins_on_belongs_to_many()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $group = factory(Group::class)->create();
        $user1->groups()->attach($group);

        $this->assertCount(1, User::has('groups')->get());
        $this->assertCount(1, User::powerJoinHas('groups')->get());
    }

    /** @test */
    public function test_has_using_joins_using_different_operators()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $user1Posts = factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        $user2Posts = factory(Post::class)->times(3)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::has('posts', '>=', 3)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '>=', 3)->get());

        $this->assertCount(1, User::has('posts', '>', 2)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '>', 2)->get());

        $this->assertCount(1, User::has('posts', '<=', 2)->get());
        $this->assertCount(1, User::powerJoinHas('posts', '<=', 2)->get());
    }

    /** @test */
    public function test_where_has_using_joins()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $posts = factory(Post::class)->create(['user_id' => $user1->id]);
        $posts = factory(Post::class)->create(['user_id' => $user2->id]);

        $this->assertCount(1, User::whereHas('posts', function ($builder) use ($user1) {
            $builder->where('posts.user_id', '=', $user1->id);
        })->get());

        $this->assertCount(1, User::powerJoinWhereHas('posts', function ($builder) use ($user1) {
            $builder->where('posts.user_id', '=', $user1->id);
        })->get());
    }

    /** @test */
    public function test_has_with_joins_and_nested_relations()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->state('published')->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->state('unpublished')->create(['user_id' => $user2->id]);
        $commentsPost1 = factory(Comment::class)->times(2)->create(['post_id' => $post1->id]);
        $commentsPost2 = factory(Comment::class)->times(5)->create(['post_id' => $post2->id]);

        $this->assertCount(2, User::has('posts.comments')->get());
        $this->assertCount(2, User::powerJoinHas('posts.comments')->get());
        $this->assertCount(1, User::powerJoinWhereHas('posts.comments', callback: [
            'posts' => fn ($query) => $query->where('posts.published', true),
        ])->get());
    }

    /** @test */
    public function test_doesnt_have()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);

        $this->assertCount(1, User::doesntHave('posts')->get());
        $this->assertCount(1, User::powerJoinDoesntHave('posts')->get());
    }

    /** @test */
    public function test_where_has_with_joins_on_has_many_through_relationship()
    {
        [$user1, $user2, $user3] = factory(User::class)->times(3)->create();
        $post1 = factory(Post::class)->create(['user_id' => $user1->id]);
        $post2 = factory(Post::class)->create(['user_id' => $user2->id]);
        $post3 = factory(Post::class)->create(['user_id' => $user3->id]);
        $commentsPost1 = factory(Comment::class)->times(2)->create(['post_id' => $post1->id]);
        $commentsPost2 = factory(Comment::class)->times(2)->create(['post_id' => $post2->id]);
        $commentsPost3 = factory(Comment::class)->times(1)->create(['post_id' => $post3->id]);
        $commentsPost1[0]->body = 'a';
        $commentsPost1[1]->body = 'a';
        $commentsPost1[0]->save();
        $commentsPost1[1]->save();
        $commentsPost2[0]->body = 'a';
        $commentsPost2[0]->save();

        $closure = fn ($query) => $query->where('body', 'a');

        $this->assertCount(2, User::whereHas('commentsThroughPosts', $closure)->get());
        $this->assertCount(2, User::powerJoinWhereHas('commentsThroughPosts', ['comments' => $closure])->get());
    }

    /** @test */
    public function test_where_has_with_joins_on_belongs_to_many_relationship()
    {
        [$user1, $user2] = factory(User::class)->times(2)->create();
        $group = factory(Group::class)->create();
        $highAccessLevelGroup = factory(Group::class)->create([
            'access_level' => 999,
        ]);

        $user1->groups()->attach($group);
        $user1->groups()->attach($highAccessLevelGroup);
        $user2->groups()->attach($group);

        $closure = fn ($query) => $query->where('access_level', 999);

        $powerJoinQuery = User::powerJoinWhereHas('groups', [
            'groups' => $closure,
        ]);

        $this->assertCount(1, User::whereHas('groups', $closure)->get());
        $this->assertQueryContains(
            'left join "groups" on "groups"."id" = "group_members"."group_id" and "access_level" = ?',
            $powerJoinQuery->toSql()
        );
        $this->assertCount(1, $powerJoinQuery->get());
    }

    public function test_power_join_has_with_morph_to()
    {
        $post = factory(Post::class)->state('published')->create();
        $postImage = factory(Image::class)->state('owner:post')->create(['imageable_id' => $post->id]);
        $user = factory(Post::class)->create();
        $userImage = factory(Image::class)->state('owner:user')->create(['imageable_id' => $user->id]);

        $postImagesQueried = Image::query()
            ->powerJoinHas('imageable', morphable: Post::class)
            ->get();

        $userImagesQueried = Image::query()
            ->powerJoinHas('imageable', morphable: User::class)
            ->get();

        $this->assertCount(1, $postImagesQueried);
        $this->assertCount(1, Image::powerJoinHas('imageable', morphable: Post::class, callback: fn ($query) => $query->where('posts.published', true))->get());
        $this->assertCount(0, Image::powerJoinHas('imageable', morphable: Post::class, callback: fn ($query) => $query->where('posts.published', false))->get());
        $this->assertCount(0, Image::powerJoinHas('imageable', count: 2, morphable: Post::class)->get());
        $this->assertTrue($postImage->is($postImagesQueried->sole()));

        $this->assertCount(1, $userImagesQueried);
        $this->assertTrue($userImage->is($userImagesQueried->sole()));
    }

    public function test_power_join_has_one_of_many()
    {
        $post = factory(Post::class)->create();
        factory(Comment::class)->state('approved')->create(['post_id' => $post->id, 'body' => 'best comment', 'votes' => 2]);

        $post2 = factory(Post::class)->create();
        factory(Comment::class)->state('approved')->create(['post_id' => $post2->id, 'body' => '2 best comment 2', 'votes' => 3]);
        $post3 = factory(Post::class)->create();

        $posts = Post::query()
            ->select('posts.*')
            ->powerJoinHas('bestComment')
            ->get();

        $postsLatest = Post::query()
            ->select('posts.*')
            ->powerJoinHas('lastComment')
            ->get();

        $this->assertCount(2, $posts);
        $this->assertCount(2, $postsLatest);
    }
}
