<?php

namespace SendCloud\Tests\Checkout\DeliveryMethodService\MockCompoents;

use RuntimeException;
use SendCloud\Checkout\Contracts\Storage\CheckoutStorage;
use SendCloud\Checkout\Domain\Search\Query;

class MockStorage implements CheckoutStorage
{
    public $methods = array();
    public $deleteAllMethodsIds = array();
    public $updatedDeliveryMethods = array();
    public $createdDeliveryMethods = array();
    public $isAllMethodsDeleted = false;
    public $isAllMethodsDataDeleted = false;
    public $deliveryMethodQuery = null;
    public $deliveryZoneIds = array();
    public $deleteObsoleteConfigsCalled = false;

    public function findAllZoneConfigs()
    {
        throw new RuntimeException('Not implemented');
    }

    public function deleteSpecificZoneConfigs(array $ids)
    {
        throw new RuntimeException('Not implemented');
    }

    public function deleteAllZoneConfigs()
    {
        throw new RuntimeException('Not implemented');
    }

    public function updateZoneConfigs(array $zones)
    {
        throw new RuntimeException('Not implemented');
    }

    public function createZoneConfigs(array $zones)
    {
        throw new RuntimeException('Not implemented');
    }

    public function findZoneConfigs(array $ids)
    {
        throw new RuntimeException('Not implemented');
    }

    public function findAllMethodConfigs()
    {
        return $this->methods;
    }

    public function deleteSpecificMethodConfigs(array $ids)
    {
        $this->deleteAllMethodsIds = $ids;
    }

    public function deleteAllMethodConfigs()
    {
        $this->isAllMethodsDeleted = true;
    }

    public function updateMethodConfigs(array $methods)
    {
        $this->updatedDeliveryMethods = $methods;
    }

    public function createMethodConfigs(array $methods)
    {
        $this->createdDeliveryMethods = $methods;
    }

    public function deleteAllMethodData()
    {
        $this->isAllMethodsDataDeleted = true;
    }

    public function findMethodConfigsBy(Query $query)
    {
        $this->deliveryMethodQuery = $query;
    }

    public function findMethodInZones(array $zoneIds)
    {
        $this->deliveryZoneIds = $zoneIds;
    }

    public function deleteObsoleteMethodConfigs()
    {
        $this->deleteObsoleteConfigsCalled = true;
    }

    public function deleteObsoleteZoneConfigs()
    {
        throw new RuntimeException('Not implemented.');
    }
}