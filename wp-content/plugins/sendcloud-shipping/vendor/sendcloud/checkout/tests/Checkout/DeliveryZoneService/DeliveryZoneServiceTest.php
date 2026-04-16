<?php

namespace SendCloud\Tests\Checkout\DeliveryZoneService;

use SendCloud\Checkout\Domain\Delivery\DeliveryZone;
use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Checkout\Services\DeliveryZoneService;
use SendCloud\Tests\Checkout\DeliveryZoneService\MockComponents\MockStorage;
use SendCloud\Tests\Common\BaseTest;

class DeliveryZoneServiceTest extends BaseTest
{
    /**
     * @var MockStorage
     */
    private $storage;
    /**
     * @var DeliveryZoneService
     */
    private $service;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Zone\DeliveryZone
     */
    public $dto;

    /**
     * @throws DTOValidationException
     */
    public function setUp()
    {
        $this->storage = new MockStorage();
        $this->service = new DeliveryZoneService($this->storage);

        $payload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true);
        $checkout = Checkout::fromArray($payload['checkout_configuration']);
        $deliveryZones = $checkout->getDeliveryZones();
        $this->dto = $deliveryZones[0];
        $zone1 = DeliveryZone::fromDTO($this->dto);
        $zone1->setId('1');
        $zone1->setSystemId(1);
        $zone2 = DeliveryZone::fromDTO($this->dto);
        $zone2->setId('123');
        $zone2->setSystemId(2);
        $zone2->getCountry()->setName('changed name');

        $this->storage->zones = array($zone1, $zone2);
    }

    public function testIdentifyCreated()
    {
        // arrange
        $created = DeliveryZone::fromDTO($this->dto);
        $created->setId('567');

        // act
        $result = $this->service->findDiff(array($created));

        // assert
        $this->assertEquals(array($created), $result['new']);
    }

    public function testIdentifyDeleted()
    {
        // arrange
        $created = DeliveryZone::fromDTO($this->dto);
        $created->setId('567');

        // act
        $result = $this->service->findDiff(array($created));

        // assert
        $this->assertEquals($this->storage->findAllZoneConfigs(), $result['deleted']);
    }

    public function testIdentifyUpdated()
    {
        // arrange
        $updated = DeliveryZone::fromDTO($this->dto);
        $updated->setId('1');
        $updated->getCountry()->setName('changed name');

        // act
        $result = $this->service->findDiff(array($updated));

        // assert
        $this->assertEquals(array($updated), $result['changed']);
    }

    public function testDeleteSpecific()
    {
        // arrange
        $zone1 = DeliveryZone::fromDTO($this->dto);
        $zone1->setId('1');
        $zone2 = DeliveryZone::fromDTO($this->dto);
        $zone2->setId('2');

        // act
        $this->service->deleteSpecific(array($zone1, $zone2));

        // assert
        $this->assertEquals(array('1', '2'), $this->storage->deletedDeliveryZoneIds);
    }

    public function testUpdate()
    {
        // arrange
        $zone1 = DeliveryZone::fromDTO($this->dto);
        $zone2 = DeliveryZone::fromDTO($this->dto);
        $payload = array($zone1, $zone2);

        // act
        $this->service->update($payload);

        // assert
        $this->assertEquals($payload, $this->storage->updatedZoneConfigs);
    }

    public function testCreated()
    {
        // arrange
        $zone1 = DeliveryZone::fromDTO($this->dto);
        $zone2 = DeliveryZone::fromDTO($this->dto);
        $payload = array($zone1, $zone2);

        // act
        $this->service->create($payload);

        // assert
        $this->assertEquals($payload, $this->storage->createdZoneConfigs);
    }

    public function testDeleteAll()
    {
        // arrange
        $this->service->deleteAll();

        // assert
        $this->assertTrue($this->storage->isAllZonesDeleted);
    }

    public function testSearchFindConfigCalled()
    {
        // arrange
        $query = new Query();

        // act
        $this->service->search($query);

        // assert
        $this->assertTrue($this->storage->isFindAllCalled);
    }

    public function testSearchNoFilter()
    {
        // arrange
        $query = new Query();
        $this->storage->zones = array(
            DeliveryZone::fromDTO($this->dto),
        );

        // act
        $result = $this->service->search($query);

        // assert
        $this->assertEquals($this->storage->zones, $result);
    }

    public function testSearchCountryNotMatching()
    {
        // arrange
        $query = new Query();
        $query->setCountry('country');
        $this->storage->zoneSearchResult = array(
            DeliveryZone::fromDTO($this->dto),
        );

        // act
        $result = $this->service->search($query);

        // assert
        $this->assertEmpty($result);
    }

    public function testSearchCountryMatch()
    {
        // arrange
        $query = new Query();
        $query->setCountry('NL');
        $this->storage->zones = array(
            DeliveryZone::fromDTO($this->dto),
        );

        // act
        $result = $this->service->search($query);

        // assert
        $this->assertEquals($this->storage->zones, $result);
    }

    public function deleteObsoleteConfigsStorageCalled()
    {
        // act
        $this->service->deleteObsoleteConfigs();

        // assert
        $this->assertTrue($this->storage->deleteObsoleteCalled);
    }
}