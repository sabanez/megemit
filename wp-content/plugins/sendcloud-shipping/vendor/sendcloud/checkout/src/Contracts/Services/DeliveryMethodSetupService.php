<?php

namespace SendCloud\Checkout\Contracts\Services;

use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;

/**
 * Interface DeliveryMethodSetupService
 *
 * @package SendCloud\Checkout\Contracts\Services
 */
interface DeliveryMethodSetupService
{
    /**
     * Deletes delivery methods specified in the provided batch.
     *
     * @param DeliveryMethod[] $methods
     *
     * @return void
     */
    public function deleteSpecific(array $methods);

    /**
     * Deletes all delivery methods.
     *
     * @return void
     */
    public function deleteAll();

    /**
     * Updates delivery methods.
     *
     * @param DeliveryMethod[] $methods
     *
     * @return void
     */
    public function update(array $methods);

    /**
     * Creates delivery methods.
     *
     * @param DeliveryMethod[] $methods
     *
     * @return void
     */
    public function create(array $methods);
}