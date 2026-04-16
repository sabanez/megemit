<?php

namespace SendCloud\Tests\Checkout\DeliveryZoneService\MockComponents;

use RuntimeException;
use SendCloud\Checkout\Contracts\Storage\CheckoutStorage;
use SendCloud\Checkout\Domain\Search\Query;

class MockStorage implements CheckoutStorage
{
    public $isFindAllCalled = false;
    public $zones = array();
    public $deletedDeliveryZoneIds = array();
    public $updatedZoneConfigs = array();
    public $createdZoneConfigs = array();
    public $isAllZonesDeleted = false;
    public $zoneIds = array();
    public $zoneSearchResult = array();
    public $deleteObsoleteCalled = false;

    public function findAllZoneConfigs()
    {
        $this->isFindAllCalled = true;

        return $this->zones;
    }

    public function deleteSpecificZoneConfigs(array $ids)
    {
        $this->deletedDeliveryZoneIds = $ids;
    }

    public function deleteAllZoneConfigs()
    {
        $this->isAllZonesDeleted = true;
    }

    public function updateZoneConfigs(array $zones)
    {
        $this->updatedZoneConfigs = $zones;
    }

    public function createZoneConfigs(array $zones)
    {
        $this->createdZoneConfigs = $zones;
    }

    public function findAllMethodConfigs()
    {
        throw new RuntimeException('Not implemented.');
    }

    public function deleteSpecificMethodConfigs(array $ids)
    {
        throw new RuntimeException('Not implemented.');
    }

    public function deleteAllMethodConfigs()
    {
        throw new RuntimeException('Not implemented.');
    }

    public function updateMethodConfigs(array $methods)
    {
        throw new RuntimeException('Not implemented.');
    }

    public function createMethodConfigs(array $methods)
    {
        throw new RuntimeException('Not implemented.');
    }

    public function deleteAllMethodData()
    {
        throw new RuntimeException('Not implemented.');
    }

    public function findMethodConfigsBy(Query $query)
    {
        throw new RuntimeException('Not implemented.');
    }

    public function findZoneConfigs(array $ids)
    {
        $this->zoneIds = $ids;

        return $this->zoneSearchResult;
    }

    public function findMethodInZones(array $zoneIds)
    {
        throw new RuntimeException('Not implemented.');
    }

    public function deleteObsoleteMethodConfigs()
    {
        throw new RuntimeException('Not implemented.');
    }

    public function deleteObsoleteZoneConfigs()
    {
        $this->deleteObsoleteCalled = true;
    }
}