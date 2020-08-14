<?php

namespace Kirschbaum\EloquentPowerJoins\Tests;

use Kirschbaum\EloquentPowerJoins\Tests\Models\Comment;
use Kirschbaum\EloquentPowerJoins\Tests\Models\Post;
use Kirschbaum\EloquentPowerJoins\Tests\Models\User;
use Kirschbaum\EloquentPowerJoins\Tests\Models\UserProfile;

class OrderByTest extends TestCase
{
    /** @test */
    public function test_order_by_relationship()
    {
        $cities = ['Veneza', 'New York', 'Manchester', 'Los Angeles', 'Atlanta'];

        $users = factory(User::class)->times(5)->create()->each(function (User $user, $index) use ($cities) {
            factory(UserProfile::class)->create([
                'user_id' => $user->id,
                'city' => $cities[$index],
            ]);
        });

        $users = User::with('profile')->orderByUsingJoins('profile.city')->get();

        $this->assertEquals('Atlanta', $users->get(0)->profile->city);
        $this->assertEquals('Los Angeles', $users->get(1)->profile->city);
        $this->assertEquals('Manchester', $users->get(2)->profile->city);
        $this->assertEquals('New York', $users->get(3)->profile->city);
        $this->assertEquals('Veneza', $users->get(4)->profile->city);
    }

    /** @test */
    public function test_order_by_nested_relationship()
    {
        // just making sure the query doesn't throw any exceptions
        User::orderByUsingJoins('posts.category.title', 'desc')->get();

        $query = User::orderByUsingJoins('posts.category.title', 'desc')->toSql();

        $this->assertStringContainsString(
            'select "users".* from "users"',
            $query
        );

        $this->assertStringContainsString(
            'order by "categories"."title" desc',
            $query
        );
    }

    /**
     * @test
     * @covers \Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship::orderByCountUsingJoins
     */
    public function test_order_by_relationship_count()
    {
        [$user1, $user2, $user3] = factory(User::class, 3)->create();
        factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        factory(Post::class)->times(4)->create(['user_id' => $user2->id]);
        factory(Post::class)->times(6)->create(['user_id' => $user3->id]);

        $users = User::orderByCountUsingJoins('posts.id', 'desc')->get();

        $this->assertEquals($user3->id, $users->get(0)->id);
        $this->assertEquals($user2->id, $users->get(1)->id);
        $this->assertEquals($user1->id, $users->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship::orderBySumUsingJoins
     */
    public function test_order_by_relationship_sum()
    {
        [$post1, $post2, $post3] = factory(Post::class, 3)->create();
        factory(Comment::class)->times(6)->create(['post_id' => $post1->id, 'votes' => 1]);  // 6 SUM
        factory(Comment::class)->times(2)->create(['post_id' => $post2->id, 'votes' => 10]); // 20 SUM
        factory(Comment::class)->times(4)->create(['post_id' => $post3->id, 'votes' => 3]);  // 12 SUM

        $posts = Post::orderBySumUsingJoins('comments.votes', 'desc')->get();

        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship::orderByAvgUsingJoins
     */
    public function test_order_by_relationship_avg()
    {
        [$post1, $post2, $post3] = factory(Post::class, 3)->create();
        factory(Comment::class)->times(6)->create(['post_id' => $post1->id, 'votes' => 1]);  // 6 SUM
        factory(Comment::class)->times(2)->create(['post_id' => $post2->id, 'votes' => 10]); // 20 SUM
        factory(Comment::class)->times(4)->create(['post_id' => $post3->id, 'votes' => 3]);  // 12 SUM

        $posts = Post::orderByAvgUsingJoins('comments.votes', 'desc')->get();

        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship::orderByMinUsingJoins
     * @covers \Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship::orderByMaxUsingJoins
     */
    public function test_order_by_relationship_min_and_max()
    {
        [$post1, $post2, $post3] = factory(Post::class, 3)->create();
        factory(Comment::class)->create(['post_id' => $post3->id, 'votes' => 5]);
        factory(Comment::class)->create(['post_id' => $post3->id, 'votes' => 3]);
        factory(Comment::class)->create(['post_id' => $post2->id, 'votes' => 1]);
        factory(Comment::class)->create(['post_id' => $post2->id, 'votes' => 2]);
        factory(Comment::class)->create(['post_id' => $post1->id, 'votes' => 10]);
        factory(Comment::class)->create(['post_id' => $post1->id, 'votes' => 1]);

        $posts = Post::orderByMinUsingJoins('comments.votes')->get();
        $this->assertCount(3, $posts);
        $this->assertEquals($post1->id, $posts->get(0)->id);
        $this->assertEquals($post2->id, $posts->get(1)->id);
        $this->assertEquals($post3->id, $posts->get(2)->id);

        $posts = Post::orderByMaxUsingJoins('comments.votes')->get();
        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);
    }
}
