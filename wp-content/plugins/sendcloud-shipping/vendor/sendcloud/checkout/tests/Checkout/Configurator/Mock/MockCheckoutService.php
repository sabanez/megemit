<?php

namespace SendCloud\Tests\Checkout\Configurator\Mock;

use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Contracts\Facades\CheckoutService;
use SendCloud\Tests\Common\CallHistory;

class MockCheckoutService implements CheckoutService
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    /**
     * MockCheckoutService constructor.
     */
    public function __construct()
    {
        $this->callHistory = new CallHistory();
    }

    public function update(Checkout $checkout)
    {
        $this->callHistory->record('update', array($checkout));
    }

    public function delete()
    {
        $this->callHistory->record('delete', array());
    }

    public function uninstall()
    {
        $this->callHistory->record('uninstall', array());
    }

    public function search(Query $query)
    {
        $this->callHistory->record('search', array($query));
    }
}