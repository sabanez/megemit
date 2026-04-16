<?php

namespace Sendcloud\Shipping\Utility;

class Logging_Callable {

	/**
	 * Callable
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * Logging_Callable constructor.
	 *
	 * @param callable $callback
	 */
	public function __construct( $callback ) {

		$this->callback = $callback;
	}

	public function __invoke() {
		$args = func_get_args();

		try {
			return call_user_func_array( $this->callback, $args );
		} catch ( \Exception $ex ) {
			Logger::critical( $ex->getMessage(), array( 'trace' => $ex->getTraceAsString() ) );
			throw $ex;
		}
	}

}
