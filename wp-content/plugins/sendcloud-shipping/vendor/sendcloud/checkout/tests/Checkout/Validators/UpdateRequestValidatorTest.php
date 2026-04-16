<?php

namespace SendCloud\Tests\Checkout\Validators;

use SendCloud\Checkout\Contracts\Services\CurrencyService;
use SendCloud\Checkout\Exceptions\ValidationException;
use SendCloud\Checkout\HTTP\Request;
use SendCloud\Checkout\Validators\UpdateRequestValidator;
use SendCloud\Tests\Checkout\Validators\Mock\MockCurrencyService;
use SendCloud\Tests\Common\BaseTest;

/**
 * Class UpdateRequestValidatorTest
 *
 * @package SendCloud\Tests\Checkout\Validators
 */
class UpdateRequestValidatorTest extends BaseTest
{
    const PLUGIN_VERSION = "2.0.0";

    /**
     * @var UpdateRequestValidator
     */
    public $validator;
    /**
     * @var mixed
     */
    public $payload;
    /**
     * @var mixed
     */
    public $invalidCurrencyPayload;
    /**
     * @var mixed
     */
    public $invalidServicePointShippingMethod;
    /**
     * @var CurrencyService
     */
    public $currencyService;

    public function setUp()
    {
        $this->currencyService = new MockCurrencyService();
        $this->validator = new UpdateRequestValidator(self::PLUGIN_VERSION, $this->currencyService);
        $this->payload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/invalidCheckout.json'), true);
        $this->invalidCurrencyPayload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/invalidPayloadCurrency.json'), true);
        $this->invalidServicePointShippingMethod = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/invalidServicePointShippingMethod.json'), true);
    }

    /**
     * @expectedException \SendCloud\Checkout\Exceptions\ValidationException
     */
    public function testEmptyBody()
    {
        // arrange
        $request = new Request(array(), array());

        // act
        $this->validator->validate($request);
    }

    /**
     * @expectedException \SendCloud\Checkout\Exceptions\ValidationException
     */
    public function testInvalidPayload()
    {
        // arrange
        $request = new Request($this->payload, array());

        // act
        $this->validator->validate($request);
    }

    public function testPluginVersionMismatch()
    {
        $payload['checkout_configuration']['minimal_plugin_version'] = "2.0.1";

        // arrange
        $request = new Request($payload, array());

        // act
        $expected = array(
            0 =>
                array(
                    'path' => array('checkout_configuration', 'minimal_plugin_version'),
                    'message' => 'Plugin version mismatch detected. Requested to publish a checkout configuration for plugin version 2.0.1, but the plugin version in use is ' . self::PLUGIN_VERSION . '.',
                )
        );
        $actual = array();

        // act
        try {
            $this->validator->validate($request);
        } catch (ValidationException $e) {
            $actual = $e->getValidationErrors();
        }

        // assert
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidPayloadErrors()
    {
        // Arrange.
        $request = new Request($this->payload, array());
        $expected = array(
            0 =>
                array(
                    'path' => array('checkout_configuration', 'id'),
                    'message' => 'Field is required.',
                ),
            1 =>
                array(
                    'path' => array('checkout_configuration', 'version'),
                    'message' => 'Field cannot be null.',
                ),
            2 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'id'),
                    'message' => 'Field is required.',
                ),
            3 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'location', 'country', 'iso_2'),
                    'message' => 'Field is required.',
                ),
            4 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'location', 'country', 'name'),
                    'message' => 'Field cannot be null.',
                ),
            5 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'delivery_methods'),
                    'message' => 'Collection cannot contain null values.',
                ),
            6 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'id'),
                    'message' => 'Field cannot be null.',
                ),
            7 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'delivery_method_type'),
                    'message' => 'Field cannot be null.',
                ),
            8 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'external_title'),
                    'message' => 'Field is required.',
                ),
            9 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'internal_title'),
                    'message' => 'Field is required.',
                ),
            10 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'description'),
                    'message' => 'Field is required.',
                ),
            11 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'carrier', 'name'),
                    'message' => 'Field is required.',
                ),
            12 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'sender_address_id'),
                    'message' => 'Field is required.',
                ),
            13 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'time_zone_name'),
                    'message' => 'Field is required.',
                ),
            14 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'carrier', 'name'),
                    'message' => 'Field is required.',
                ),
            15 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'carrier', 'code'),
                    'message' => 'Field cannot be null.',
                ),
            16 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'carrier', 'logo_url'),
                    'message' => 'Field is required.',
                ),
            17 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 2, 'delivery_methods', 0, 'shipping_product'),
                    'message' => 'Field cannot be null.',
                ),
            18 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 3, 'delivery_methods', 0, 'parcel_handover_days', 'monday', 'cut_off_time_minutes'),
                    'message' => 'Field is required.',
                ),
            19 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 3, 'delivery_methods', 0, 'parcel_handover_days', 'friday', 'enabled'),
                    'message' => 'Field cannot be null.',
                ),
            20 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 3, 'delivery_methods', 0, 'parcel_handover_days', 'friday', 'cut_off_time_hours'),
                    'message' => 'Field is required.',
                ),
            21 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 4, 'delivery_methods', 0, 'show_carrier_information_in_checkout'),
                    'message' => 'Field is required.',
                ),
        );

        $actual = array();

        // act
        try {
            $this->validator->validate($request);
        } catch (ValidationException $e) {
            $actual = $e->getValidationErrors();
        }

        // assert
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidCurrency()
    {
        // Arrange.
        $request = new Request($this->invalidCurrencyPayload, array());
        $expected = array(
            0 =>
                array(
                    'path' => array('checkout_configuration', 'currency'),
                    'message' => 'Configured currency does not match the default currency.',
                    'context' => array(
                        'default_currency' => $this->currencyService->getDefaultCurrencyCode()
                    ),
                    'code' => 'currency_mismatch',
                ),
            1 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'delivery_methods', 0, 'shipping_rate_data', 'currency'),
                    'message' => 'Configured currency does not match the default currency.',
                    'context' => array(
                        'default_currency' => $this->currencyService->getDefaultCurrencyCode()
                    ),
                    'code' => 'currency_mismatch',
                ),
        );

        $actual = array();

        // act
        try {
            $this->validator->validate($request);
        } catch (ValidationException $e) {
            $actual = $e->getValidationErrors();
        }

        // assert
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidServicePointShippingMethod()
    {
        // Arrange.
        $request = new Request($this->invalidServicePointShippingMethod, array());
        $expected = array(
            0 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'delivery_methods', 0, 'external_title'),
                    'message' => 'Field is required.',
                ),
            1 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 0, 'delivery_methods', 0, 'internal_title'),
                    'message' => 'Field is required.',
                ),
            2 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'sender_address_id'),
                    'message' => 'Field is required.',
                ),
            3 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'time_zone_name'),
                    'message' => 'Field is required.',
                ),
            4 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'carriers', 0, 'name'),
                    'message' => 'Field is required.',
                ),
            5 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'carriers', 0, 'logo_url'),
                    'message' => 'Field is required.',
                ),
            6 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 0, 'service_point_data', 'api_key'),
                    'message' => 'Field is required.',
                ),
            7 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 1, 'show_carrier_information_in_checkout'),
                    'message' => 'Field is required.',
                ),
            8 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 1, 'carriers', 0, 'code'),
                    'message' => 'Field cannot be null.',
                ),
            9 =>
                array(
                    'path' => array('checkout_configuration', 'delivery_zones', 1, 'delivery_methods', 1, 'service_point_data', 'country_iso_2'),
                    'message' => 'Field cannot be null.',
                ),
        );

        $actual = array();

        // act
        try {
            $this->validator->validate($request);
        } catch (ValidationException $e) {
            $actual = $e->getValidationErrors();
        }

        // assert
        $this->assertEquals($expected, $actual);
    }
}
