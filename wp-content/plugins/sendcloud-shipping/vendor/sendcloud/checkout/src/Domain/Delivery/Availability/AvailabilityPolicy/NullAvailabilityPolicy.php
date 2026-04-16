<?php

namespace SendCloud\Checkout\Domain\Delivery\Availability\AvailabilityPolicy;

use SendCloud\Checkout\Domain\Delivery\Availability\AvailabilityPolicy;

class NullAvailabilityPolicy extends AvailabilityPolicy
{
    /**
     * @return bool
     */
    public function isAvailable()
    {
        return true;
    }
}