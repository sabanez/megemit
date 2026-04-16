<?php

namespace Sendcloud\Shipping\Checkout\Factories;

use SendCloud\Checkout\Configurator;
use SendCloud\Checkout\Contracts\Validators\RequestValidator;
use SendCloud\Checkout\Validators\NullRequestValidator;
use SendCloud\Checkout\Validators\UpdateRequestValidator;
use Sendcloud\Shipping\Checkout\Interfaces\Checkout_Configurator_Factory;
use Sendcloud\Shipping\Checkout\Interfaces\Checkout_Service_Factory;
use Sendcloud\Shipping\Checkout\Services\Currency_Service;
use Sendcloud\Shipping\Sendcloud;

/**
 * Class Default_Checkout_Configurator_Factory
 *
 * @package Sendcloud\Shipping\Checkout\Factories
 */
class Default_Checkout_Configurator_Factory implements Checkout_Configurator_Factory {
	/**
	 * Checkout_Service_Factory
	 *
	 * @var Checkout_Service_Factory
	 */
	private $service_factory;
	/**
	 * RequestValidator
	 *
	 * @var RequestValidator
	 */
	private $update_request_validator;
	/**
	 * RequestValidator
	 *
	 * @var RequestValidator
	 */
	private $delete_request_validator;

	/**
	 * Default_Checkout_Configurator_Factory constructor.
	 */
	public function __construct() {
		$this->service_factory          = new Default_Checkout_Service_Factory();
		$this->update_request_validator = new UpdateRequestValidator(Sendcloud::VERSION, new Currency_Service());
		$this->delete_request_validator = new NullRequestValidator();
	}

	/**
	 * Provides configurator instance.
	 *
	 * @return Configurator
	 */
	public function make() {
		return new Configurator(
			$this->update_request_validator,
			$this->delete_request_validator,
			$this->service_factory->make()
		);
	}
}
