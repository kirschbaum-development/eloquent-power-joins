<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\Address;
use Kirschbaum\PowerJoins\Tests\Models\City;
use Kirschbaum\PowerJoins\Tests\Models\Country;
use Kirschbaum\PowerJoins\Tests\Models\CountryEnum;

class LaravelScopeAttributeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_use_laravel_scope_attribute_in_join_relationship_callback()
    {
        if (version_compare(app()->version(), '12.0.0', '<')) {
            $this->markTestSkipped('Laravel 12+ is required for this test');
        }

        // Create test data
        $denmark = Country::create(['name' => 'Denmark', 'iso' => 'DK']);
        $usa = Country::create(['name' => 'United States', 'iso' => 'US']);

        $copenhagen = City::create(['name' => 'Copenhagen', 'country_id' => $denmark->id]);
        $newyork = City::create(['name' => 'New York', 'country_id' => $usa->id]);

        $addressInDenmark = Address::create([
            'kvh_code' => 'DK001',
            'name' => 'Danish Address',
            'city_id' => $copenhagen->id,
        ]);

        $addressInUSA = Address::create([
            'kvh_code' => 'US001',
            'name' => 'US Address',
            'city_id' => $newyork->id,
        ]);

        // This should work but currently fails with "Method inCountry does not exist in PowerJoinClause class"
        $result = Address::query()
            ->joinRelationship('city', function ($join) {
                $join->inCountry(CountryEnum::DK);
            })
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals('DK001', $result->kvh_code);
    }
}
