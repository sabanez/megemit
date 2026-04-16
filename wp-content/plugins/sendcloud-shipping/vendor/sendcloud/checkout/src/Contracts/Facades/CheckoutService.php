<?php

namespace SendCloud\Checkout\Contracts\Facades;

use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\Domain\FailedToDeleteCheckoutConfigurationException;
use SendCloud\Checkout\Exceptions\Domain\FailedToUpdateCheckoutConfigurationException;

/**
 * Interface CheckoutService
 *
 * @package SendCloud\Checkout\Contracts\Facades
 */
interface CheckoutService
{
    /**
     * Updates checkout configuration.
     *
     * @param Checkout $checkout
     *
     * @return void
     *
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function update(Checkout $checkout);

    /**
     * Deletes locally saved configuration.
     *
     * @return void
     *
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function delete();

    /**
     * Deletes all data when the uninstall is called.
     *
     * @return void
     *
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function uninstall();

    /**
     * Provides delivery method matching the
     *
     * @param Query $query
     *
     * @return DeliveryMethod[]
     */
    public function search(Query $query);
}