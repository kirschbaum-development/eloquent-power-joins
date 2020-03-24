<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests;

use Illuminate\Database\Eloquent\Builder;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Post;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\User;
use KirschbaumDevelopment\EloquentJoins\Tests\Models\Comment;

class SortTest extends TestCase
{
    /** @test */
    public function test_sort_by_relationship()
    {
        $this->markTestIncomplete('@TODO');

        $users = factory(User::class)->times(5)->create()->each(function (User $user) {
            // create user profile or some relation
        });

        $user = User::sortBy();
    }
}
