<?php

namespace SendCloud\Tests\Checkout\Domain\DeliveryZone;

use SendCloud\Checkout\Domain\Delivery\DeliveryZone;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Tests\Common\BaseTest;

class DeliveryZoneTest extends BaseTest
{
    /**
     * @var Checkout
     */
    public $checkout;

    /**
     * @throws DTOValidationException
     */
    public function setUp()
    {
        $payload = json_decode(file_get_contents(__DIR__ . '/../../../Common/APIDataExamples/checkoutWithRates.json'), true);
        $this->checkout = Checkout::fromArray($payload['checkout_configuration']);
    }

    public function testInstantiationFromDto()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];

        // act
        $zone = DeliveryZone::fromDTO($deliveryZone);

        // assert
        $this->assertInstanceOf('SendCloud\Checkout\Domain\Delivery\DeliveryZone', $zone);
        $this->assertEquals($deliveryZone->getId(), $zone->getId());
        $this->assertEquals($deliveryZone->getLocation()->getName(), $zone->getCountry()->getName());
        $this->assertEquals($deliveryZone->getLocation()->getIsoCode(), $zone->getCountry()->getIsoCode());
    }

    public function testCanBeUpdatedNoChanges()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($deliveryZone);
        $zone2 = DeliveryZone::fromDTO($deliveryZone);

        // act
        $result = $zone1->canBeUpdated($zone2);

        // assert
        $this->assertFalse($result);
    }

    public function testCanBeUpdatedIdChanged()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($deliveryZone);
        $zone2 = DeliveryZone::fromDTO($deliveryZone);
        $zone1->setId('11123123');

        // act
        $result = $zone1->canBeUpdated($zone2);

        // assert
        $this->assertFalse($result);
    }

    public function testCanBeUpdatedSystemIdChanged()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($deliveryZone);
        $zone2 = DeliveryZone::fromDTO($deliveryZone);
        $zone1->setSystemId(11123123);

        // act
        $result = $zone1->canBeUpdated($zone2);

        // assert
        $this->assertFalse($result);
    }

    public function testUpdatedCountryNameChanged()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($deliveryZone);
        $zone2 = DeliveryZone::fromDTO($deliveryZone);
        $zone2->getCountry()->setName('New Name');

        // act
        $result = $zone1->canBeUpdated($zone2);

        // assert
        $this->assertTrue($result);
    }

    public function testUpdatedCountryCodeChanged()
    {
        // arrange
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($deliveryZone);
        $zone2 = DeliveryZone::fromDTO($deliveryZone);
        $zone2->getCountry()->setIsoCode('New Code');

        // act
        $result = $zone1->canBeUpdated($zone2);

        // assert
        $this->assertTrue($result);
    }
}