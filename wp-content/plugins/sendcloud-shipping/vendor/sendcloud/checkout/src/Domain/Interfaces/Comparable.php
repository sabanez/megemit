<?php

namespace SendCloud\Checkout\Domain\Interfaces;

/**
 * Interface Comparable
 *
 * @package SendCloud\Checkout\Domain\Contracts
 */
interface Comparable
{
    /**
     * Compares current instance to a target.
     *
     * @param object $target
     * @return boolean
     */
    public function isEqual($target);
}