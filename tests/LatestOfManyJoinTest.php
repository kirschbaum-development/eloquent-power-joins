<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Address;
use Kirschbaum\PowerJoins\Tests\Models\RequestedAddress;

class LatestOfManyJoinTest extends TestCase
{
    /** @test */
    public function test_left_join_relationship_with_latest_of_many_uses_correct_foreign_key()
    {
        // Create test data
        $address = Address::create([
            'kvh_code' => 'KVH123',
            'name' => 'Test Address',
        ]);

        RequestedAddress::create([
            'kvh_code' => 'KVH123',
            'requested_at' => now()->subDays(2),
            'status' => 'pending',
        ]);

        RequestedAddress::create([
            'kvh_code' => 'KVH123',
            'requested_at' => now()->subDay(),
            'status' => 'approved',
        ]);

        // This should generate a query that uses the correct foreign key relationship
        $query = Address::query()
            ->leftJoinRelationship('latest_requested_address')
            ->toSql();

        // The issue: the generated query incorrectly uses addresses.id instead of addresses.kvh_code
        // in the subquery for latestOfMany
        // Expected: all joins should use kvh_code
        // Actual: subquery uses addresses.id instead of addresses.kvh_code
        
        // This assertion will fail because the subquery incorrectly uses addresses.id
        $this->assertStringNotContainsString('"requested_addresses"."kvh_code" = "addresses"."id"', $query);
    }

    /** @test */
    public function test_left_join_relationship_with_latest_of_many_returns_correct_data()
    {
        // Create test data
        $address = Address::create([
            'kvh_code' => 'KVH456',
            'name' => 'Test Address 2',
        ]);

        $oldRequest = RequestedAddress::create([
            'kvh_code' => 'KVH456',
            'requested_at' => now()->subDays(2),
            'status' => 'pending',
        ]);

        $latestRequest = RequestedAddress::create([
            'kvh_code' => 'KVH456',
            'requested_at' => now()->subDay(),
            'status' => 'approved',
        ]);

        // Execute the query
        $result = Address::query()
            ->leftJoinRelationship('latest_requested_address')
            ->where('addresses.kvh_code', 'KVH456')
            ->first();

        // This should work correctly if the join uses the right foreign key
        $this->assertNotNull($result);
        $this->assertEquals('KVH456', $result->kvh_code);
    }
}