<?php

namespace Kirschbaum\PowerJoins\Tests;

use Illuminate\Support\Facades\DB;
use Kirschbaum\PowerJoins\FakeJoinCallback;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

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

        $users = User::with('profile')->orderByPowerJoins('profile.city')->get();

        // making sure left join do not throw exceptions
        User::with('profile')->orderByLeftPowerJoins('profile.city')->get();

        $this->assertEquals('Atlanta', $users->get(0)->profile->city);
        $this->assertEquals('Los Angeles', $users->get(1)->profile->city);
        $this->assertEquals('Manchester', $users->get(2)->profile->city);
        $this->assertEquals('New York', $users->get(3)->profile->city);
        $this->assertEquals('Veneza', $users->get(4)->profile->city);
    }

    /** @test */
    public function test_order_by_relationship_with_concat()
    {
        User::with('profile')
            ->select('user_profiles.*', DB::raw('printf("%s, %s", user_profiles.city, user_profiles.state) as locale'))
            ->orderByPowerJoins(['profile', DB::raw('locale')])
            ->get();

        User::with('profile')
            ->orderByPowerJoins(['profile', DB::raw('printf("%s, %s", user_profiles.city, user_profiles.state)')])
            ->get();

        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function test_can_call_twice_in_a_row()
    {
        $user = new User;

        $user->posts()->orderByPowerJoins('comments.created_at', 'desc')->get();
        $user->posts()->orderByPowerJoins('comments.created_at', 'desc')->get();

        $this->assertTrue(true, 'No exceptions, we are good :)');
    }

    /** @test */
    public function test_order_by_nested_relationship()
    {
        // just making sure the query doesn't throw any exceptions
        User::orderByPowerJoins('posts.category.title', 'desc')->get();

        $query = User::orderByPowerJoins('posts.category.title', 'desc')->toSql();

        // making sure left join do not throw exceptions
        User::orderByLeftPowerJoins('posts.category.title', 'desc')->toSql();

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
     * @covers \Kirschbaum\PowerJoins\PowerJoins::scopeOrderByPowerJoinsCount
     */
    public function test_order_by_relationship_count()
    {
        [$user1, $user2, $user3] = factory(User::class, 3)->create();
        factory(Post::class)->times(2)->create(['user_id' => $user1->id]);
        factory(Post::class)->times(4)->create(['user_id' => $user2->id]);
        factory(Post::class)->times(6)->create(['user_id' => $user3->id]);

        $users = User::orderByPowerJoinsCount('posts.id', 'desc')->get();

        // making sure left join do not throw exceptions
        User::orderByLeftPowerJoinsCount('posts.id', 'desc')->get();

        $this->assertEquals($user3->id, $users->get(0)->id);
        $this->assertEquals($user2->id, $users->get(1)->id);
        $this->assertEquals($user1->id, $users->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\PowerJoins\PowerJoins::scopeOrderByPowerJoinsSum
     */
    public function test_order_by_relationship_sum()
    {
        [$post1, $post2, $post3] = factory(Post::class, 3)->create();
        factory(Comment::class)->times(6)->create(['post_id' => $post1->id, 'votes' => 1]);  // 6 SUM
        factory(Comment::class)->times(2)->create(['post_id' => $post2->id, 'votes' => 10]); // 20 SUM
        factory(Comment::class)->times(4)->create(['post_id' => $post3->id, 'votes' => 3]);  // 12 SUM

        $posts = Post::orderByPowerJoinsSum('comments.votes', 'desc')->get();

        // making sure left join do not throw exceptions
        User::orderByLeftPowerJoinsSum('posts.id', 'desc')->get();

        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\PowerJoins\PowerJoins::scopeOrderByPowerJoinsAvg
     */
    public function test_order_by_relationship_avg()
    {
        [$post1, $post2, $post3] = factory(Post::class, 3)->create();
        factory(Comment::class)->times(6)->create(['post_id' => $post1->id, 'votes' => 1]);  // 6 SUM
        factory(Comment::class)->times(2)->create(['post_id' => $post2->id, 'votes' => 10]); // 20 SUM
        factory(Comment::class)->times(4)->create(['post_id' => $post3->id, 'votes' => 3]);  // 12 SUM

        $posts = Post::orderByPowerJoinsAvg('comments.votes', 'desc')->get();

        // making sure left join do not throw exceptions
        User::orderByLeftPowerJoinsAvg('posts.id', 'desc')->get();

        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);
    }

    /**
     * @test
     * @covers \Kirschbaum\PowerJoins\PowerJoins::scopeOrderByPowerJoinsMin
     * @covers \Kirschbaum\PowerJoins\PowerJoins::scopeOrderByPowerJoinsMax
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

        $posts = Post::orderByPowerJoinsMin('comments.votes')->get();
        $this->assertCount(3, $posts);
        $this->assertEquals($post1->id, $posts->get(0)->id);
        $this->assertEquals($post2->id, $posts->get(1)->id);
        $this->assertEquals($post3->id, $posts->get(2)->id);

        $posts = Post::orderByPowerJoinsMax('comments.votes')->get();
        $this->assertCount(3, $posts);
        $this->assertEquals($post2->id, $posts->get(0)->id);
        $this->assertEquals($post3->id, $posts->get(1)->id);
        $this->assertEquals($post1->id, $posts->get(2)->id);

        // making sure left join do not throw exceptions
        User::orderByLeftPowerJoinsMin('comments.votes')->get();
        User::orderByLeftPowerJoinsMax('comments.votes')->get();
    }

    /** @test */
    public function test_order_by_relationship_with_relationship_alias()
    {
        $query = User::orderByPowerJoins('posts.category.title', 'desc', aliases: [
            'posts' => fn ($join) => $join->as('posts_alias'),
            'category' => fn ($join) => $join->as('category_alias'),
        ]);

        $this->assertStringContainsString(
            'select "users".* from "users" inner join "posts" as "posts_alias" on "posts_alias"."user_id" = "users"."id" inner join "categories" as "category_alias" on "posts_alias"."category_id" = "category_alias"."id" where "users"."deleted_at" is null order by "category_alias"."title" desc',
            $query->toSql()
        );

        $query->get();

        $this->assertTrue(true, 'No exceptions, we are good :)');
    }

    /** @test */
    public function test_order_by_relationship_aggregation_with_relationship_alias()
    {
        $query = User::orderByPowerJoins('comments.votes', 'desc', 'sum', 'leftJoin', aliases: 'comments_alias');

        $this->assertStringContainsString(
            'select "users".*, sum(comments_alias.votes) as comments_alias_votes_sum from "users" left join "comments" as "comments_alias" on "comments_alias"."user_id" = "users"."id" where "users"."deleted_at" is null group by "users"."id" order by comments_alias_votes_sum desc',
            $query->toSql()
        );

        $query->get();

        $this->assertTrue(true, 'No exceptions, we are good :)');
    }
}
