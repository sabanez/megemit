<?php

namespace SendCloud\Tests\Checkout\DeliveryMethodService;

use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Checkout\Services\DeliveryMethodService;
use SendCloud\Tests\Checkout\DeliveryMethodService\MockCompoents\MockStorage;
use SendCloud\Tests\Common\BaseTest;

class DeliveryMethodServiceTest extends BaseTest
{
    /**
     * @var MockStorage
     */
    private $storage;
    /**
     * @var DeliveryMethodService
     */
    private $service;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethod
     */
    private $dto;

    /**
     * @throws DTOValidationException
     */
    public function setUp()
    {
        $this->storage = new MockStorage();
        $this->service = new DeliveryMethodService($this->storage);

        $payload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true);
        $checkout = Checkout::fromArray($payload['checkout_configuration']);
        $deliveryZones = $checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $deliveryMethods = $deliveryZone->getDeliveryMethods();
        $this->dto = $deliveryMethods[0];
        $method1 = DeliveryMethod::fromDTO($this->dto);
        $method1->setId('1');
        $method1->setSystemId(1);
        $method1->setDeliveryZoneId('1');
        $method2 = DeliveryMethod::fromDTO($this->dto);
        $method2->setId('2');
        $method2->setSystemId(2);
        $method2->setDeliveryZoneId('2');
        $method2->setInternalTitle('test');

        $this->storage->methods = array($method1, $method2);
    }

    public function testIdentifyCreated()
    {
        // arrange
        $created = DeliveryMethod::fromDTO($this->dto);
        $created->setId('3');

        // act
        $result = $this->service->findDiff(array($created));

        // assert
        $this->assertEquals(array($created), $result['new']);
    }

    public function testIdentifyDeleted()
    {
        // arrange
        $created = DeliveryMethod::fromDTO($this->dto);
        $created->setId('3');

        // act
        $result = $this->service->findDiff(array($created));

        // assert
        $this->assertEquals($this->storage->methods, $result['deleted']);
    }

    public function testIdentifyUpdated()
    {
        // arrange
        $updated = DeliveryMethod::fromDTO($this->dto);
        $updated->setId('1');
        $updated->setInternalTitle('new changed title');

        // act
        $result = $this->service->findDiff(array($updated));

        // assert
        $this->assertEquals(array($updated), $result['changed']);
    }

    public function testDeleteSpecific()
    {
        // arrange
        $method1 = DeliveryMethod::fromDTO($this->dto);
        $method1->setId('1');
        $method2 = DeliveryMethod::fromDTO($this->dto);
        $method2->setId('2');

        // act
        $this->service->deleteSpecific(array($method1, $method2));

        // assert
        $this->assertEquals(array('1', '2'), $this->storage->deleteAllMethodsIds);
    }

    public function testUpdate()
    {
        // arrange
        $method1 = DeliveryMethod::fromDTO($this->dto);
        $method2 = DeliveryMethod::fromDTO($this->dto);
        $payload = array($method1, $method2);

        // act
        $this->service->update($payload);

        // arrange
        $this->assertEquals($payload, $this->storage->updatedDeliveryMethods);
    }

    public function testCreate()
    {
        // arrange
        $method1 = DeliveryMethod::fromDTO($this->dto);
        $method2 = DeliveryMethod::fromDTO($this->dto);
        $payload = array($method1, $method2);

        // act
        $this->service->create($payload);

        // arrange
        $this->assertEquals($payload, $this->storage->createdDeliveryMethods);
    }

    public function testDeleteAll()
    {
        // act
        $this->service->deleteAll();

        // assert
        $this->assertTrue($this->storage->isAllMethodsDeleted);
    }

    public function testDeleteAllData()
    {
        // act
        $this->service->deleteAllData();

        // assert
        $this->assertTrue($this->storage->isAllMethodsDataDeleted);
    }

    public function testFindInZonesStorageCalled()
    {
        // arrange
        $zoneIds = array(1, 2, 3);

        // act
        $this->service->findInZones($zoneIds);

        // assert
        $this->assertEquals($zoneIds, $this->storage->deliveryZoneIds);
    }

    public function deleteObsoleteConfigsStorageCalled()
    {
        // act
        $this->service->deleteObsoleteConfigs();

        // assert
        $this->assertTrue($this->storage->deleteObsoleteConfigsCalled);
    }
}