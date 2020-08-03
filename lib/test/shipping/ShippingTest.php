<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once __DIR__ . '/../../shipping/shippinglib.php';

class ShippingTest extends TikiTestCase implements ShippingProvider
{
    private $from;
    private $to;
    private $packages;

    protected function setUp() : void
    {
        $this->from = null;
        $this->to = null;
        $this->packages = null;
    }

    public function testWithoutProvider(): void
    {
        $lib = new ShippingLib;

        $this->assertEquals([], $lib->getRates(['zip' => '12345'], ['zip' => '23456'], [['weight' => 5]]));
    }

    public function testCountryPreserved(): void
    {
        $lib = new ShippingLib;
        $lib->addProvider($this);

        $lib->getRates(['zip' => '12345', 'country' => 'FR'], ['zip' => '23456'], [['weight' => 5]]);

        $this->assertEquals('FR', $this->from['country']);
    }

    public function testCountryCompleted(): void
    {
        $lib = new ShippingLib;
        $lib->addProvider($this);

        $lib->getRates(['zip' => '12345'], ['zip' => 'A1B 2C3'], [['weight' => 5]]);

        $this->assertEquals('US', $this->from['country']);
        $this->assertEquals('CA', $this->to['country']);
    }

    public function testZipUpperCased(): void
    {
        $lib = new ShippingLib;
        $lib->addProvider($this);

        $lib->getRates(['zip' => '12345'], ['zip' => 'a1b 2c3'], [['weight' => 5]]);

        $this->assertEquals('A1B 2C3', $this->to['zip']);
        $this->assertEquals('CA', $this->to['country']);
    }

    public function testUnknownFormat(): void
    {
        $lib = new ShippingLib;
        $lib->addProvider($this);

        $lib->getRates(['zip' => '12345678900X'], ['zip' => 'A1B 2C3'], [['weight' => 5]]);

        $this->assertArrayNotHasKey('country', $this->from);
    }

    public function testPackageExpansion(): void
    {
        $lib = new ShippingLib;
        $lib->addProvider($this);

        $lib->getRates(['zip' => '12345678900X'], ['zip' => 'A1B 2C3'], [['weight' => 5, 'count' => 2], ['weight' => 10]]);

        $this->assertEquals(
            [
                ['weight' => 5],
                ['weight' => 5],
                ['weight' => 10],
            ],
            $this->packages
        );
    }

    public function getRates(array $from, array $to, array $packages): array
    {
        $this->from = $from;
        $this->to = $to;
        $this->packages = $packages;

        return [];
    }
}
