<?php

namespace Sendcloud\Shipping\Checkout\Factories;

use RuntimeException;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Nominated_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\SameDay\Same_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Service_Point_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\Standard\Standard_Shipping_Method;

class Shipping_Method_Factory {

	/**
	 * Create instance of shipping method
	 *
	 * @param DeliveryMethod $method
	 *
	 * @return Nominated_Day_Shipping_Method|Same_Day_Shipping_Method|Service_Point_Shipping_Method|Standard_Shipping_Method
	 */
	public static function create( DeliveryMethod $method ) {
		switch ( $method->getType() ) {
			case 'same_day_delivery':
				$instance = new Same_Day_Shipping_Method( $method->getSystemId() );
				break;
			case 'standard_delivery':
				$instance = new Standard_Shipping_Method( $method->getSystemId() );
				break;
			case 'nominated_day_delivery':
				$instance = new Nominated_Day_Shipping_Method( $method->getSystemId() );
				break;
			case 'service_point_delivery':
				$instance = new Service_Point_Shipping_Method( $method->getSystemId() );
				break;
			default:
				throw new RuntimeException( 'Unknown shipping method type.' );
		}

		return $instance;
	}
}
