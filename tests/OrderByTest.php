<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\UserProfile;

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

        $users = User::select('users.*')->with('profile')->orderByUsingJoins('profile.city')->get();

        $this->assertEquals('Atlanta', $users->get(0)->profile->city);
        $this->assertEquals('Los Angeles', $users->get(1)->profile->city);
        $this->assertEquals('Manchester', $users->get(2)->profile->city);
        $this->assertEquals('New York', $users->get(3)->profile->city);
        $this->assertEquals('Veneza', $users->get(4)->profile->city);
    }
}
