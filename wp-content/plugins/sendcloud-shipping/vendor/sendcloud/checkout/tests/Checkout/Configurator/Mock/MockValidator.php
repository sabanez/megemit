<?php

namespace SendCloud\Tests\Checkout\Configurator\Mock;

use SendCloud\Checkout\HTTP\Request;
use SendCloud\Checkout\Contracts\Validators\RequestValidator;
use SendCloud\Tests\Common\CallHistory;

class MockValidator implements RequestValidator
{
    /**
     * @var CallHistory
     */
    public $callHistory;

    public function __construct()
    {
        $this->callHistory = new CallHistory();
    }

    public function validate(Request $request)
    {
        $this->callHistory->record('validate', array($request));
    }
}