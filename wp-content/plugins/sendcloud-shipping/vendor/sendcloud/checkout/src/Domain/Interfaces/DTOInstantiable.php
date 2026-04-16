<?php

namespace SendCloud\Checkout\Domain\Interfaces;

use SendCloud\Checkout\DTO\DataTransferObject;

/**
 * Interface DTOInstantiable
 *
 * @package SendCloud\Checkout\Domain\Contracts
 */
interface DTOInstantiable
{
    /**
     * Makes an instance from dto.
     *
     * @param DataTransferObject $object
     *
     * @return mixed
     */
    public static function fromDTO($object);
}