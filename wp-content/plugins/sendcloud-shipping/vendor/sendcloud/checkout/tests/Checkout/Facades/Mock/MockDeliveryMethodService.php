<?php

namespace SendCloud\Tests\Checkout\Facades\Mock;

use SendCloud\Checkout\Contracts\Services\DeliveryMethodService;
use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Tests\Common\CallHistory;

/**
 * Class MockDeliveryMethodService
 *
 * @package SendCloud\Tests\Checkout\Facades\Mock
 */
class MockDeliveryMethodService implements DeliveryMethodService
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    public $newMethods = array();
    public $updatedMethods = array();
    public $deletedMethods = array();
    public $methodSearchResult = array();

    /**
     * MockDeliveryMethodService constructor.
     *
     * @param CallHistory $callHistory
     */
    public function __construct(CallHistory $callHistory)
    {
        $this->callHistory = $callHistory;
    }

    public function findDiff(array $newDeliveryMethods)
    {
        $this->callHistory->record('findDiff', array($newDeliveryMethods));

        return array(
            'new' => $this->newMethods,
            'changed' => $this->updatedMethods,
            'deleted' => $this->deletedMethods,
        );
    }

    public function deleteSpecific(array $methods)
    {
        $this->callHistory->record('deleteSpecific', array($methods));
    }

    public function deleteAll()
    {
        $this->callHistory->record('deleteAll', array());
    }

    public function update(array $methods)
    {
        $this->callHistory->record('update', array($methods));
    }

    public function create(array $methods)
    {
        $this->callHistory->record('create', array($methods));
    }

    public function deleteAllData()
    {
        $this->callHistory->record('deleteAllData', array());
    }

    public function search(Query $query)
    {
        $this->callHistory->record('search', array($query));

        return $this->methodSearchResult;
    }

    public function findInZones(array $zoneIds)
    {
        $this->callHistory->record('findInZones', array($zoneIds));

        return $this->methodSearchResult;
    }

    public function deleteObsoleteConfigs()
    {
        $this->callHistory->record('deleteObsoleteConfigs', array());
    }
}