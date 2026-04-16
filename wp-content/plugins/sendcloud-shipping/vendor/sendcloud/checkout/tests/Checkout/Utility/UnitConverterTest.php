<?php

namespace SendCloud\Tests\Checkout\Utility;

use SendCloud\Checkout\Contracts\Utility\WeightUnits;
use SendCloud\Checkout\Utility\UnitConverter;
use SendCloud\Tests\Common\BaseTest;

class UnitConverterTest extends BaseTest
{

    public function testKGConverter()
    {
        $weight = 2.3;
        $this->assertEquals(2.3 * 1000, UnitConverter::toGrams(WeightUnits::KILOGRAM, $weight));
    }

    public function testLBSConverter()
    {
        $weight = 2.3;
        $this->assertEquals(2.3 * 453.592, UnitConverter::toGrams(WeightUnits::POUNDS, $weight));
    }

    public function testOZConverter()
    {
        $weight = 2.3;
        $this->assertEquals(2.3 * 28.3495, UnitConverter::toGrams(WeightUnits::OUNCES, $weight));
    }

    public function testGConverter()
    {
        $weight = 2.3;
        $this->assertEquals(2.3, UnitConverter::toGrams(WeightUnits::GRAM, $weight));
    }

    public function testToKGConverter()
    {
        $weight = 2000;
        $this->assertEquals($weight / 1000, UnitConverter::fromGrams(WeightUnits::KILOGRAM, $weight));
    }


    public function testToLBSConverter()
    {
        $weight = 2000;
        $this->assertEquals($weight / 453.592, UnitConverter::fromGrams(WeightUnits::POUNDS, $weight));
    }

    public function testToOZConverter()
    {
        $weight = 2000;
        $this->assertEquals($weight / 28.3495, UnitConverter::fromGrams(WeightUnits::OUNCES, $weight));
    }

    public function testToGConverter()
    {
        $weight = 2000;
        $this->assertEquals($weight, UnitConverter::fromGrams(WeightUnits::GRAM, $weight));
    }
}
