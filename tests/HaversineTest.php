<?php

namespace App\Tests;

use App\Controller\MainController;
use PHPUnit\Framework\TestCase;

class HaversineTest extends TestCase
{
    public function testHaversineDistance()
    {
        $haversine = new MainController();
        
        $result = $haversine->haversineDistance(
            46, 3, 45.70750046, 5.91529989
        );
        $this->assertEquals(228, $result);

        $result = $haversine->haversineDistance(
            48.00279999, 6.65829992, 48.59299850, 7.71490002
        );
        $this->assertEquals(102, $result);

        $result = $haversine->haversineDistance(
            49.80199814, 11.03240013, 50.02239990, 10.82999992
        );
        $this->assertEquals(28, $result);
    }
}