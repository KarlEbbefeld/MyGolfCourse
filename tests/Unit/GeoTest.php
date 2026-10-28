<?php

namespace Tests\Unit;

use App\Core\Support\Geo;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    public function test_destination_and_distance_are_consistent(): void
    {
        [$lat, $lng] = Geo::destination(51.68, 7.80, 35, 352);

        $this->assertEqualsWithDelta(352, Geo::distance(51.68, 7.80, $lat, $lng), 0.5);
        $this->assertEqualsWithDelta(35, Geo::bearing(51.68, 7.80, $lat, $lng), 0.1);
    }

    public function test_bearing_points_north_and_east(): void
    {
        $this->assertEqualsWithDelta(0, Geo::bearing(51.0, 7.0, 51.01, 7.0), 0.01);
        $this->assertEqualsWithDelta(90, Geo::bearing(51.0, 7.0, 51.0, 7.01), 0.1);
    }
}
