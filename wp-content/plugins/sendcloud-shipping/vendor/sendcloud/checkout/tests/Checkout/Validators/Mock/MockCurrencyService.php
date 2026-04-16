<?php

namespace SendCloud\Tests\Checkout\Validators\Mock;

use SendCloud\Checkout\Contracts\Services\CurrencyService;

class MockCurrencyService implements CurrencyService
{

    public $defaultCurrencyCode = 'EUR';

    /**
     * @inheritDoc
     */
    public function getDefaultCurrencyCode()
    {
        return $this->defaultCurrencyCode;
    }
}