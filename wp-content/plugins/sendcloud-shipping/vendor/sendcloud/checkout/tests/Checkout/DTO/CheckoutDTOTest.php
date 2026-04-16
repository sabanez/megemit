<?php


namespace SendCloud\Tests\Checkout\DTO;


use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Tests\Common\BaseTest;

class CheckoutDTOTest extends BaseTest
{
    public $payload;
    public $payloadWithRates;

    protected function setUp()
    {
        $this->payload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true);
        $this->payloadWithRates = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkoutWithRates.json'), true);
    }

    /**
     * @throws DTOValidationException
     */
    public function testSerializationWithShippingRates()
    {
        // arrange
        $checkout = Checkout::fromArray($this->payloadWithRates['checkout_configuration']);

        // act
        $array = $checkout->toArray();

        // assert
        $this->assertArrayHasKey('shipping_rate_data', $array['delivery_zones'][0]['delivery_methods'][0]);
        $this->assertEquals(
            $this->payloadWithRates['checkout_configuration']['delivery_zones'][0]['delivery_methods'][0],
            $checkout->getDeliveryZones()[0]->getDeliveryMethods()[0]->getRawData()
        );
    }

    /**
     * @throws DTOValidationException
     */
    public function testSerializationWithoutShippingRates()
    {
        // arrange
        $checkout = Checkout::fromArray($this->payload['checkout_configuration']);

        // act
        $array = $checkout->toArray();

        // assert
        $this->assertArrayHasKey('shipping_rate_data', $array['delivery_zones'][0]['delivery_methods'][0]);
        $this->assertEquals(
            $this->payload['checkout_configuration']['delivery_zones'][0]['delivery_methods'][0],
            $checkout->getDeliveryZones()[0]->getDeliveryMethods()[0]->getRawData()
        );
    }
}