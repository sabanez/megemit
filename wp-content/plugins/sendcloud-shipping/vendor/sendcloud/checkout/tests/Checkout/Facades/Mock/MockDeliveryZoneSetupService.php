<?php

namespace SendCloud\Tests\Checkout\Facades\Mock;

use SendCloud\Checkout\Contracts\Services\DeliveryZoneSetupService;
use SendCloud\Tests\Common\CallHistory;

/**
 * Class MockDeliveryZoneSetupService
 *
 * @package SendCloud\Tests\Checkout\Facades\Mock
 */
class MockDeliveryZoneSetupService implements DeliveryZoneSetupService
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    /**
     * MockDeliveryZoneSetupService constructor.
     * @param CallHistory $callHistory
     */
    public function __construct(CallHistory $callHistory)
    {
        $this->callHistory = $callHistory;
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
}