<?php

namespace SendCloud\Checkout\Domain\Delivery;

use SendCloud\Checkout\Domain\Interfaces\Comparable;
use SendCloud\Checkout\Domain\Interfaces\DTOInstantiable;

class FreeShipping implements Comparable, DTOInstantiable
{
    /**
     * @var bool
     */
    protected $enabled;
    /**
     * @var string
     */
    protected $fromAmount;

    /**
     * @param bool $enabled
     * @param string $fromAmount
     */
    public function __construct($enabled, $fromAmount)
    {
        $this->enabled = $enabled;
        $this->fromAmount = $fromAmount;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getFromAmount()
    {
        return $this->fromAmount;
    }

    /**
     * @param string $fromAmount
     */
    public function setFromAmount($fromAmount)
    {
        $this->fromAmount = $fromAmount;
    }

    /**
     * @param FreeShipping $target
     *
     * @return bool
     */
    public function isEqual($target)
    {
        return $this->isEnabled() === $target->isEnabled()
            && $this->getFromAmount() === $target->getFromAmount();
    }

    /**
     * @param \SendCloud\Checkout\API\Checkout\Delivery\Method\FreeShipping $object
     *
     * @return FreeShipping
     */
    public static function fromDTO($object)
    {
        return new static($object->isEnabled(), $object->getFromAmount());
    }
}
