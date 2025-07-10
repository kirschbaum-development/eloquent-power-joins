<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\JoinsHelper;
use Kirschbaum\PowerJoins\Tests\Models\User;

class DuplicateAliasGenerationTest extends TestCase
{
    /** @test */
    public function test_multiple_joins_of_same_relationship_generate_unique_aliases()
    {
        $joinsHelper = JoinsHelper::make(new User());
        $user = new User();
        $relation = $user->posts();

        // Generate multiple aliases for the same relationship quickly
        $alias1 = $joinsHelper->generateAliasForRelationship($relation, 'posts');
        $alias2 = $joinsHelper->generateAliasForRelationship($relation, 'posts');
        $alias3 = $joinsHelper->generateAliasForRelationship($relation, 'posts');

        // These should all be unique, but due to time() they will likely be the same
        $this->assertNotEquals($alias1, $alias2, 'First and second aliases should be different');
        $this->assertNotEquals($alias2, $alias3, 'Second and third aliases should be different');
        $this->assertNotEquals($alias1, $alias3, 'First and third aliases should be different');
    }

    /** @test */
    public function test_multiple_joins_of_same_belongs_to_many_relationship_generate_unique_aliases()
    {
        $joinsHelper = JoinsHelper::make(new User());
        $user = new User();
        $relation = $user->groups();

        // Generate multiple aliases for the same BelongsToMany relationship quickly
        $alias1 = $joinsHelper->generateAliasForRelationship($relation, 'groups');
        $alias2 = $joinsHelper->generateAliasForRelationship($relation, 'groups');
        $alias3 = $joinsHelper->generateAliasForRelationship($relation, 'groups');

        // These should all be unique arrays, but due to time() they will likely be the same
        $this->assertNotEquals($alias1, $alias2, 'First and second alias arrays should be different');
        $this->assertNotEquals($alias2, $alias3, 'Second and third alias arrays should be different');
        $this->assertNotEquals($alias1, $alias3, 'First and third alias arrays should be different');

        // Also check individual elements
        $this->assertNotEquals($alias1[0], $alias2[0], 'First elements of alias arrays should be different');
        $this->assertNotEquals($alias1[1], $alias2[1], 'Second elements of alias arrays should be different');
    }

    /** @test */
    public function test_actual_query_with_multiple_auto_generated_aliases_fails()
    {
        // This test demonstrates the real-world issue where multiple joins
        // with auto-generated aliases can overwrite each other

        $query = User::query()
            ->joinRelationshipUsingAlias('posts')
            ->joinRelationshipUsingAlias('posts'); // Second join of same relationship

        $sql = $query->toSql();

        // Count how many times "posts" appears with "as" in the SQL
        $aliasCount = substr_count($sql, '"posts" as');

        // We expect 2 different aliases, but due to the time() issue,
        // we might get the same alias twice, resulting in only 1 unique join
        $this->assertEquals(2, $aliasCount, 'Should have 2 different aliases for posts table, but got: '.$sql);
    }
}
