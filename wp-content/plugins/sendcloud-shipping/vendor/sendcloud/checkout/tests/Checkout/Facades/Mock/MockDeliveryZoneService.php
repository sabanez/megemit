<?php

namespace SendCloud\Tests\Checkout\Facades\Mock;

use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Checkout\Contracts\Services\DeliveryZoneService;
use SendCloud\Tests\Common\CallHistory;

/**
 * Class MockDeliveryZoneService
 *
 * @package SendCloud\Tests\Checkout\Facades\Mock
 */
class MockDeliveryZoneService implements DeliveryZoneService
{
    public $newZones = array();
    public $updatedZones = array();
    public $deletedZones = array();

    public $zoneSearchResult = array();

    /**
     * @var CallHistory
     */
    public $callHistory;

    /**
     * MockDeliveryZoneService constructor.
     * @param CallHistory $callHistory
     */
    public function __construct(CallHistory $callHistory)
    {
        $this->callHistory = $callHistory;
    }


    public function findDiff(array $newDeliveryZones)
    {
        $this->callHistory->record('findDiff', array($newDeliveryZones));

        return array(
            'new' => $this->newZones,
            'changed' => $this->updatedZones,
            'deleted' => $this->deletedZones,
        );
    }

    public function deleteSpecific(array $zones)
    {
        $this->callHistory->record('deleteSpecific', array($zones));
    }

    public function deleteAll()
    {
        $this->callHistory->record('deleteAll', array());
    }

    public function update(array $zones)
    {
        $this->callHistory->record('update', array($zones));
    }

    public function create(array $zones)
    {
        $this->callHistory->record('create', array($zones));
    }

    public function search(Query $query)
    {
        $this->callHistory->record('search', array($query));

        return $this->zoneSearchResult;
    }

    public function deleteObsoleteConfigs()
    {
        $this->callHistory->record('deleteObsoleteConfigs', array());
    }
}