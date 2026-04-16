<?php

namespace Sendcloud\Shipping\Utility;

/**
 * Class Response
 *
 * @package Sendcloud\Shipping\Utility
 */
class Response {
	/**
	 * Provides json response.
	 *
	 * @param array $data
	 * @param int $status
	 */
	public static function json( array $data, $status = 200) {
		echo json_encode($data);
		header('Content-Type: application/json');
		status_header($status);

		exit;
	}
}
