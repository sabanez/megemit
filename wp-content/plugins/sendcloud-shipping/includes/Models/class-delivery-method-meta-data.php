<?php

namespace Sendcloud\Shipping\Models;

use DateTime;

class Delivery_Method_Meta_Data {

	const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

	/**
	 * Delivery date
	 *
	 * @var DateTime
	 */
	private $delivery_date;

	/**
	 * Formatted delivery date
	 *
	 * @var string
	 */
	private $formatted_delivery_date;

	/**
	 * Parcel handover date
	 *
	 * @var DateTime
	 */
	private $parcel_handover_date;

	/**
	 * Formatted parcel handover date
	 *
	 * @var string
	 */
	private $formatted_parcel_handover_date;

	/**
	 * Return object as array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'delivery_date'                  => date_format( $this->get_delivery_date(), self::DATE_FORMAT ),
			'formatted_delivery_date'        => $this->get_formatted_delivery_date(),
			'parcel_handover_date'           => date_format( $this->get_parcel_handover_date(), self::DATE_FORMAT ),
			'formatted_parcel_handover_date' => $this->get_formatted_parcel_handover_date(),
		);
	}

	/**
	 * Creates object from array
	 *
	 * @param $data
	 *
	 * @return Delivery_Method_Meta_Data
	 */
	public static function from_array( $data ) {
		$instance = new self();

		$formatted_delivery_date = array_key_exists( 'formatted_delivery_date',
			$data ) && $data['formatted_delivery_date'] ? $data['formatted_delivery_date'] : $instance->get_delivery_date()->format( self::DATE_FORMAT );
		$formatted_parcel_handover_date = array_key_exists( 'formatted_parcel_handover_date',
			$data ) && $data['formatted_parcel_handover_date'] ? $data['formatted_parcel_handover_date'] : $instance->get_parcel_handover_date()->format( self::DATE_FORMAT );

		$instance->set_delivery_date( $instance->createDateObject( $data, 'delivery_date' ) );
		$instance->set_formatted_delivery_date( $formatted_delivery_date );
		$instance->set_parcel_handover_date( $instance->createDateObject( $data, 'parcel_handover_date' ) );
		$instance->set_formatted_parcel_handover_date( $formatted_parcel_handover_date );

		return $instance;
	}

	/**
	 * Get delivery date
	 *
	 * @return DateTime
	 */
	public function get_delivery_date() {
		return $this->delivery_date;
	}

	/**
	 * Set delivery date
	 *
	 * @param DateTime $delivery_date
	 */
	public function set_delivery_date( $delivery_date ) {
		$this->delivery_date = $delivery_date;
	}

	/**
	 * Get formatted delivery date
	 *
	 * @return string
	 */
	public function get_formatted_delivery_date() {
		return $this->formatted_delivery_date;
	}

	/**
	 * Set formatted delivery date
	 *
	 * @param string $formatted_delivery_date
	 */
	public function set_formatted_delivery_date( $formatted_delivery_date ) {
		$this->formatted_delivery_date = $formatted_delivery_date;
	}

	/**
	 * Get parcel handover date
	 *
	 * @return DateTime
	 */
	public function get_parcel_handover_date() {
		return $this->parcel_handover_date;
	}

	/**
	 * Set parcel handover date
	 *
	 * @param DateTime $parcel_handover_date
	 */
	public function set_parcel_handover_date( $parcel_handover_date ) {
		$this->parcel_handover_date = $parcel_handover_date;
	}

	/**
	 * Get formatted parcel handover date
	 *
	 * @return string
	 */
	public function get_formatted_parcel_handover_date() {
		return $this->formatted_parcel_handover_date;
	}

	/**
	 * Set formatted parcel handover date
	 *
	 * @param string $formatted_parcel_handover_date
	 */
	public function set_formatted_parcel_handover_date( $formatted_parcel_handover_date ) {
		$this->formatted_parcel_handover_date = $formatted_parcel_handover_date;
	}

	/**
	 * Created date object based on array and key value
	 *
	 * @param array $data
	 * @param $key
	 *
	 * @return DateTime|false
	 */
	private function createDateObject( array $data, $key ) {
		if ( array_key_exists( 'delivery_date', $data ) && $data[ $key ] ) {
			$date = DateTime::createFromFormat( self::DATE_FORMAT,
				$data[ $key ] )->setTimezone( new \DateTimeZone( 'UTC' ) );
		} else {
			$date = DateTime::createFromFormat( self::DATE_FORMAT,
				new DateTime() )->setTimezone( new \DateTimeZone( 'UTC' ) );
		}

		return $date;
	}
}
