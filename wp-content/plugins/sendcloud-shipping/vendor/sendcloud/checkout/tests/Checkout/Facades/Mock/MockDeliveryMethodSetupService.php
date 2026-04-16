<?php

namespace SendCloud\Tests\Checkout\Facades\Mock;

use SendCloud\Checkout\Contracts\Services\DeliveryMethodSetupService;
use SendCloud\Tests\Common\CallHistory;

/**
 * Class MockDeliveryMethodSetupService
 *
 * @package SendCloud\Tests\Checkout\Facades\Mock
 */
class MockDeliveryMethodSetupService implements DeliveryMethodSetupService
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    /**
     * MockDeliveryMethodSetupService constructor.
     * @param CallHistory $callHistory
     */
    public function __construct(CallHistory $callHistory)
    {
        $this->callHistory = $callHistory;
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
}