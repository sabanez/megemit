<?php

namespace SendCloud\Checkout\Domain\Interfaces;

/**
 * Interface Updateable
 *
 * @package SendCloud\Checkout\Domain\Contracts
 */
interface Updateable
{
    /**
     * Checks whether the instance is different enough from target to require an update.
     *
     * @param object $target
     * @return boolean
     */
    public function canBeUpdated($target);
}