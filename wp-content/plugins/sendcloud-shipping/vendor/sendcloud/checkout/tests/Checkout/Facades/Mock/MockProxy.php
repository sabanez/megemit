<?php

namespace SendCloud\Tests\Checkout\Facades\Mock;

use SendCloud\Checkout\Contracts\Proxies\Proxy;
use SendCloud\Tests\Common\CallHistory;

class MockProxy implements Proxy
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    /**
     * MockProxy constructor.
     * @param CallHistory $callHistory
     */
    public function __construct(CallHistory $callHistory)
    {
        $this->callHistory = $callHistory;
    }

    public function delete()
    {
        $this->callHistory->record('delete', array());
    }
}