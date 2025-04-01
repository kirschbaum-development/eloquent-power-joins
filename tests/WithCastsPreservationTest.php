<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\User;

class WithCastsPreservationTest extends TestCase
{
    /** @test */
    public function test_withcasts_values_preserved_when_joining_relationship()
    {
        $query = User::query()
            ->withCasts(['created_at' => 'date:Y-m']) // Format date as year-month only
            ->joinRelationship('posts');

        $model = $query->getModel();

        $this->assertArrayHasKey('created_at', $model->getCasts());
        $this->assertSame('date:Y-m', $model->getCasts()['created_at']);
    }

    /** @test */
    public function test_withcasts_values_preserved_after_query_is_cloned()
    {
        $query = User::query()
            ->joinRelationship('posts')
            ->withCasts(['another_field' => 'date:Y-m']);

        $clonedQuery = $query->clone();
        $clonedQuery->joinRelationship('images');

        $model = $clonedQuery->getModel();
        $this->assertArrayHasKey('another_field', $model->getCasts());
        $this->assertSame('date:Y-m', $model->getCasts()['another_field']);
    }
}
