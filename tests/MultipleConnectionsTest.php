<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\PowerJoins;
use Kirschbaum\PowerJoins\Tests\Models\Post;
use Kirschbaum\PowerJoins\Tests\Models\User;
use Kirschbaum\PowerJoins\Tests\Models\Group;
use Kirschbaum\PowerJoins\Tests\Models\Image;
use Kirschbaum\PowerJoins\Tests\Models\Comment;
use Kirschbaum\PowerJoins\Tests\Models\PostStat;
use Kirschbaum\PowerJoins\Tests\Models\UserProfile;

class MultipleConnectionsTest extends TestCase
{
    /** @test */
    public function test_join_with_multiple_connections()
    {
        $post = factory(Post::class)->create();
        $postStat = factory(PostStat::class)->create(['post_id' => $post->id]);

        $sql = Post::query()
            ->joinRelationship('stats', fn ($join) => $join->includeDatabaseName())
            ->toSql();

        $posts = Post::query()
            ->joinRelationship('stats')
            ->get();

        dd($sql);
    }
}
