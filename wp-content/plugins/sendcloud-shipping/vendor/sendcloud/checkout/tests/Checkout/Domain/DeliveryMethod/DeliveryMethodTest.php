<?php

namespace SendCloud\Tests\Checkout\Domain\DeliveryMethod;

use DateTime;
use DateTimeZone;
use RuntimeException;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethods\NominatedDayDelivery;
use SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethods\SameDayDelivery;
use SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethods\StandardDelivery;
use SendCloud\Checkout\Contracts\Utility\WeightUnits;
use SendCloud\Checkout\Domain\Delivery\Availability\Order;
use SendCloud\Checkout\Domain\Delivery\Availability\OrderItem;
use SendCloud\Checkout\Domain\Delivery\Availability\Weight;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Utility\UnitConverter;
use SendCloud\Tests\Common\BaseTest;

class DeliveryMethodTest extends BaseTest
{
    /**
     * @var Checkout
     */
    public $checkout;
    public $checkoutWithRates;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethod[]
     */
    public $deliveryMethods;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethod[]
     */
    public $deliveryMethodsWithShippingRates;

    public function setUp()
    {
        $payload = json_decode(file_get_contents(__DIR__ . '/../../../Common/APIDataExamples/checkout.json'), true);
        $payloadWithRates = json_decode(file_get_contents(__DIR__ . '/../../../Common/APIDataExamples/checkoutWithRates.json'), true);
        $this->checkout = Checkout::fromArray($payload['checkout_configuration']);
        $this->checkoutWithRates = Checkout::fromArray($payloadWithRates['checkout_configuration']);
        $deliveryZones = $this->checkout->getDeliveryZones();
        $deliveryZone = $deliveryZones[0];
        $this->deliveryMethods = $deliveryZone->getDeliveryMethods();
        $this->deliveryMethodsWithShippingRates = $this->checkoutWithRates->getDeliveryZones()[0]->getDeliveryMethods();
    }

    public function testInstantiationFromDtoWithoutShippingRates()
    {
        // arrange
        $dto = $this->deliveryMethods[0];

        // act
        $method = DeliveryMethod::fromDTO($dto);

        // assert
        $this->assertInstanceOf('SendCloud\Checkout\Domain\Delivery\DeliveryMethod', $method);
        $this->assertEquals($method->getType(), $dto->getType());
        $this->assertEquals($method->getInternalTitle(), $dto->getInternalTitle());
        $this->assertEquals($method->getExternalTitle(), $dto->getExternalTitle());
        $this->assertEquals($method->getRawConfig(), json_encode($dto->getRawData()));
        $this->assertFalse($method->getShippingRateData()->isEnabled());
        $this->assertEmpty($method->getShippingRateData()->getShippingRates());
        $carrier = $method->getCarrier();
        $this->assertEquals($carrier->getName(), $dto->getCarrier()->getName());
        $this->assertEquals($carrier->getCode(), $dto->getCarrier()->getCode());
        $this->assertEquals($carrier->getLogoUrl(), $dto->getCarrier()->getLogoUrl());
        $shippingProduct = $method->getShippingProduct();
        $this->assertEquals($shippingProduct->getCode(), $dto->getShippingProduct()->getCode());
        $this->assertEquals($shippingProduct->getName(), $dto->getShippingProduct()->getName());
        $this->assertEquals($shippingProduct->getLeadTimeHours(), $dto->getShippingProduct()->getLeadTimeHours());
        $this->assertEquals($shippingProduct->getSelectedFunctionalities(), $dto->getShippingProduct()->getSelectedFunctionalities());
        $targetDeliveryDays = $dto->getShippingProduct()->getCarrierDeliveryDays();
        foreach ($shippingProduct->getDeliveryDays() as $index => $deliveryDay) {
            $targetDay = $targetDeliveryDays[$index];
            if ($deliveryDay === null) {
                $this->assertNull($targetDay);

                continue;
            }

            $this->assertEquals($deliveryDay->getEndingMinute(), $targetDay->getEndingMinute());
            $this->assertEquals($deliveryDay->getEndingHour(), $targetDay->getEndingHour());
            $this->assertEquals($deliveryDay->getStartingMinute(), $targetDay->getStartingMinute());
            $this->assertEquals($deliveryDay->getStartingHour(), $targetDay->getStartingHour());
        }

        if ($dto instanceof NominatedDayDelivery || $dto instanceof SameDayDelivery) {
            $processingDays = $dto->getHandoverDays();
        } elseif ($dto instanceof StandardDelivery) {
            $processingDays = $dto->getOrderPlacementDays();
        } else {
            throw new RuntimeException('Unknown delivery method.');
        }
        foreach ($method->getProcessingDays() as $index => $processingDay) {
            $targetProcessingDay = $processingDays[$index];
            if ($processingDay === null) {
                $this->assertNull($targetProcessingDay);

                continue;
            }

            $this->assertEquals($processingDay->isEnabled(), $targetProcessingDay->isEnabled());
            $this->assertEquals($processingDay->getCutOffMinute(), $targetProcessingDay->getCutOffTimeMinutes());
            $this->assertEquals($processingDay->getCutOffHour(), $targetProcessingDay->getCutOffTimeHours());
        }
    }

    public function testInstantiationFromDtoWithShippingRates()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];

        // act
        $method = DeliveryMethod::fromDTO($dto);

        // assert
        $this->assertInstanceOf('SendCloud\Checkout\Domain\Delivery\DeliveryMethod', $method);
        $this->assertEquals($method->getType(), $dto->getType());
        $this->assertEquals($method->getInternalTitle(), $dto->getInternalTitle());
        $this->assertEquals($method->getExternalTitle(), $dto->getExternalTitle());
        $this->assertEquals($method->getRawConfig(), json_encode($dto->getRawData()));
        $this->assertTrue($method->getShippingRateData()->isEnabled());
        $this->assertNotEmpty($method->getShippingRateData()->getShippingRates());
        $carrier = $method->getCarrier();
        $this->assertEquals($carrier->getName(), $dto->getCarrier()->getName());
        $this->assertEquals($carrier->getCode(), $dto->getCarrier()->getCode());
        $this->assertEquals($carrier->getLogoUrl(), $dto->getCarrier()->getLogoUrl());
        $shippingProduct = $method->getShippingProduct();
        $this->assertEquals($shippingProduct->getCode(), $dto->getShippingProduct()->getCode());
        $this->assertEquals($shippingProduct->getName(), $dto->getShippingProduct()->getName());
        $this->assertEquals($shippingProduct->getLeadTimeHours(), $dto->getShippingProduct()->getLeadTimeHours());
        $this->assertEquals($shippingProduct->getSelectedFunctionalities(), $dto->getShippingProduct()->getSelectedFunctionalities());
        $targetDeliveryDays = $dto->getShippingProduct()->getCarrierDeliveryDays();
        foreach ($shippingProduct->getDeliveryDays() as $index => $deliveryDay) {
            $targetDay = $targetDeliveryDays[$index];
            if ($deliveryDay === null) {
                $this->assertNull($targetDay);

                continue;
            }

            $this->assertEquals($deliveryDay->getEndingMinute(), $targetDay->getEndingMinute());
            $this->assertEquals($deliveryDay->getEndingHour(), $targetDay->getEndingHour());
            $this->assertEquals($deliveryDay->getStartingMinute(), $targetDay->getStartingMinute());
            $this->assertEquals($deliveryDay->getStartingHour(), $targetDay->getStartingHour());
        }

        if ($dto instanceof NominatedDayDelivery || $dto instanceof SameDayDelivery) {
            $processingDays = $dto->getHandoverDays();
        } elseif ($dto instanceof StandardDelivery) {
            $processingDays = $dto->getOrderPlacementDays();
        } else {
            throw new RuntimeException('Unknown delivery method.');
        }
        foreach ($method->getProcessingDays() as $index => $processingDay) {
            $targetProcessingDay = $processingDays[$index];
            if ($processingDay === null) {
                $this->assertNull($targetProcessingDay);

                continue;
            }

            $this->assertEquals($processingDay->isEnabled(), $targetProcessingDay->isEnabled());
            $this->assertEquals($processingDay->getCutOffMinute(), $targetProcessingDay->getCutOffTimeMinutes());
            $this->assertEquals($processingDay->getCutOffHour(), $targetProcessingDay->getCutOffTimeHours());
        }
    }

    public function testServicePointDeliveryMethodInstantiation()
    {
        // arrange
        $dto = $this->deliveryMethods[3];

        // act
        $method = DeliveryMethod::fromDTO($dto);

        $this->assertInstanceOf('SendCloud\Checkout\Domain\Delivery\DeliveryMethod', $method);
        $this->assertEquals($method->getType(), $dto->getType());
        $this->assertEquals($method->getInternalTitle(), $dto->getInternalTitle());
        $this->assertEquals($method->getExternalTitle(), $dto->getExternalTitle());
        $this->assertEquals($method->getRawConfig(), json_encode($dto->getRawData()));
        $this->assertTrue($method->getShippingRateData()->isEnabled());
        $this->assertNotEmpty($method->getShippingRateData()->getShippingRates());
        $carriers = $method->getCarriers();
        $targetCarriers = $dto->getCarriers();
        foreach ($carriers as $index => $carrier) {
            $targetCarrier = $targetCarriers[$index];
            $this->assertEquals($carrier->getName(), $targetCarrier->getName());
            $this->assertEquals($carrier->getCode(), $targetCarrier->getCode());
            $this->assertEquals($carrier->getLogoUrl(), $targetCarrier->getLogoUrl());
        }
        $servicePointData = $method->getServicePointData();
        $this->assertEquals($servicePointData->getApiKey(), $dto->getServicePointData()->getApiKey());
        $this->assertEquals($servicePointData->getCountry(), $dto->getServicePointData()->getCountry());
    }

    public function testCanBeUpdatedNothingChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertFalse($result);
    }

    public function testCanBeUpdatedIdChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setId('123123123');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertFalse($result);
    }

    public function testCanBeUpdatedSystemIdChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setSystemId(123123123);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertFalse($result);
    }

    public function testCanBeUpdatedDeliveryZoneIdChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setDeliveryZoneId('123123123');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedCarrierCodeChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getCarrier()->setCode('123123');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedCarrierNameChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getCarrier()->setName('name changed');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedInternalTitleChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setInternalTitle('title changed');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedExternalTitleChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setExternalTitle('title changed');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedSenderAddressIdChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setSenderAddressId(234234);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }


    public function testCanBeUpdatedDisplayCarrierInfoChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setShowCarrierInfoOnCheckout(!$method1->isShowCarrierInfoOnCheckout());

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedSelectedFunctionalitiesChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getShippingProduct()->setSelectedFunctionalities(array('signature' => false));

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedCarrierLogoUrlChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getCarrier()->setLogoUrl('name changed');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductNameChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getShippingProduct()->setName('new name');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductCodeChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getShippingProduct()->setCode('new test');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductDeliveryEnabledChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $deliveryDays = $method1->getShippingProduct()->getDeliveryDays();
        $day = $deliveryDays['friday'];
        $day->setEnabled(false);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductDeliveryDateEndingMinuteChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $deliveryDays = $method1->getShippingProduct()->getDeliveryDays();
        $day = $deliveryDays['friday'];
        $day->setEndingMinute(23);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductDeliveryDateEndingHourChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $deliveryDays = $method1->getShippingProduct()->getDeliveryDays();
        $day = $deliveryDays['monday'];
        $day->setEndingHour(23);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductDeliveryDateStartingHourChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $deliveryDays = $method1->getShippingProduct()->getDeliveryDays();
        $day = $deliveryDays['friday'];
        $day->setStartingHour(11);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedShippingProductDeliveryDateStartingMinuteChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $deliveryDays = $method1->getShippingProduct()->getDeliveryDays();
        $day = $deliveryDays['thursday'];
        $day->setStartingMinute(21);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedLeadHourChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->getShippingProduct()->setLeadTimeHours(23);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedTypeChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setType('75');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedTimeZoneNameChanged()
    {
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $method1->setTimeZoneName('Europe/Athens');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHandoverDayEnabledChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $handoverDays = $method1->getProcessingDays();
        $day = $handoverDays['monday'];
        $day->setEnabled(false);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHandoverCutOffMinuteChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $handoverDays = $method1->getProcessingDays();
        $day = $handoverDays['tuesday'];
        $day->setCutOffMinute(12);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHandoverDayCutOffHourChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $handoverDays = $method1->getProcessingDays();
        $day = $handoverDays['tuesday'];
        $day->setCutOffHour(21);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedOrderPlacementDayEnabledChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[1];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $orderPlacementDay = $method1->getProcessingDays();
        $day = $orderPlacementDay['monday'];
        $day->setEnabled(false);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedOrderPlacementCutOffMinuteChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[1];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $orderPlacementDay = $method1->getProcessingDays();
        $day = $orderPlacementDay['tuesday'];
        $day->setCutOffMinute(12);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedOrderPlacementDayCutOffHourChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[1];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $orderPlacementDay = $method1->getProcessingDays();
        $day = $orderPlacementDay['tuesday'];
        $day->setCutOffHour(21);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHolidayFrequencyChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $holidays = $method1->getHolidays();
        $holiday = $holidays[0];
        $holiday->setFrequency('yearly');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHolidayFromDateChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $holidays = $method1->getHolidays();
        $holiday = $holidays[0];
        $holiday->setFromDate('2021-12-24');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHolidayToDateChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $holidays = $method1->getHolidays();
        $holiday = $holidays[0];
        $holiday->setToDate('2021-12-24');

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHolidayRecurringChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $holidays = $method1->getHolidays();
        $holiday = $holidays[0];
        $holiday->setRecurring(true);

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedHolidayTitleChanged()
    {
        // arrange
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $holidays = $method1->getHolidays();
        $holiday = $holidays[0];
        $holiday->setTitle("Saturnalia");

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    public function testCanBeUpdatedServicePointData()
    {
        // arrange
        $dto = $this->deliveryMethods[3];
        $method1 = DeliveryMethod::fromDTO($dto);
        $method2 = DeliveryMethod::fromDTO($dto);
        $servicePointData = $method1->getServicePointData();
        $servicePointData->setCountry("UK");

        // act
        $result = $method1->canBeUpdated($method2);

        // assert
        $this->assertTrue($result);
    }

    /**
     * @throws \Exception
     */
    public function testIsAvailableSameDayDelivery()
    {
        $dto = $this->deliveryMethodsWithShippingRates[1];

        $method = DeliveryMethod::fromDTO($dto);

        $time = time();
        $deliveryDayName = date("l", $time);

        $deliveryDate = new DateTime('@' . $time);
        $timezone = new DateTimeZone($method->getTimeZoneName());
        $deliveryDate->setTimezone($timezone);

        $parcelHandoverDay = $method->getProcessingDays()[strtolower($deliveryDayName)];
        $parcelHandoverDay->setEnabled(true);
        $parcelHandoverDay->setCutOffHour((int)$deliveryDate->format('H') + 1);

        $holidays = $method->getHolidays();
        foreach ($holidays as $holiday) {
            $holiday->setFromDate(date("Y-m-d", strtotime("+1 month", $time)));
            $holiday->setToDate(date("Y-m-d", strtotime("+2 month", $time)));
        }
        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));

        $this->assertTrue($method->isAvailable($order));
    }

    public function testNotAvailableSameDayDelivery()
    {
        $dto = $this->deliveryMethodsWithShippingRates[1];

        $method = DeliveryMethod::fromDTO($dto);

        $time = time();
        $deliveryDayName = date("l", $time);

        $deliveryDate = new DateTime('@' . $time);
        $timezone = new DateTimeZone($method->getTimeZoneName());
        $deliveryDate->setTimezone($timezone);

        $parcelHandoverDay = $method->getProcessingDays()[strtolower($deliveryDayName)];
        $parcelHandoverDay->setEnabled(true);
        $parcelHandoverDay->setCutOffHour((int)$deliveryDate->format('H') - 1);

        $holidays = $method->getHolidays();
        foreach ($holidays as $holiday) {
            $holiday->setFromDate(date("Y-m-d", strtotime("+1 month", $time)));
            $holiday->setToDate(date("Y-m-d", strtotime("+2 month", $time)));
        }
        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));
        $this->assertFalse($method->isAvailable($order));
    }

    public function testNotAvailableDuringHolidaysSameDayDelivery()
    {
        $dto = $this->deliveryMethodsWithShippingRates[1];

        $method = DeliveryMethod::fromDTO($dto);

        $time = time();
        $deliveryDayName = date("l", $time);

        $deliveryDate = new DateTime('@' . $time);
        $timezone = new DateTimeZone($method->getTimeZoneName());
        $deliveryDate->setTimezone($timezone);

        $parcelHandoverDay = $method->getProcessingDays()[strtolower($deliveryDayName)];
        $parcelHandoverDay->setEnabled(true);
        $parcelHandoverDay->setCutOffHour((int)$deliveryDate->format('H') + 1);

        $holidays = $method->getHolidays();
        foreach ($holidays as $holiday) {
            $holiday->setFromDate(date("Y-m-d", strtotime("-1 month", $time)));
            $holiday->setToDate(date("Y-m-d", strtotime("+1 month", $time)));
        }
        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));

        $this->assertFalse($method->isAvailable($order));
    }

    /**
     * @throws \Exception
     */
    public function testIsAvailableStandardDelivery()
    {
        $dto = $this->deliveryMethodsWithShippingRates[1];
        $method = DeliveryMethod::fromDTO($dto);

        $time = time();
        $deliveryDayName = date("l", $time);

        $deliveryDate = new DateTime('@' . $time);
        $timezone = new DateTimeZone($method->getTimeZoneName());
        $deliveryDate->setTimezone($timezone);

        $parcelHandoverDay = $method->getProcessingDays()[strtolower($deliveryDayName)];
        $parcelHandoverDay->setEnabled(true);
        $parcelHandoverDay->setCutOffHour((int)$deliveryDate->format('H') + 1);

        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));
        $this->assertTrue($method->isAvailable($order));
    }

    public function testNotAvailableStandardDelivery()
    {
        $dto = $this->deliveryMethodsWithShippingRates[1];
        $method = DeliveryMethod::fromDTO($dto);

        $time = time();
        $deliveryDayName = date("l", $time);

        $deliveryDate = new DateTime('@' . $time);
        $timezone = new DateTimeZone($method->getTimeZoneName());
        $deliveryDate->setTimezone($timezone);

        $parcelHandoverDay = $method->getProcessingDays()[strtolower($deliveryDayName)];
        $parcelHandoverDay->setEnabled(true);
        $parcelHandoverDay->setCutOffHour((int)$deliveryDate->format('H') - 1);
        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));
        $this->assertFalse($method->isAvailable($order));
    }

    public function testAvailableWithEnabledRates()
    {
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method = DeliveryMethod::fromDTO($dto);

        $order = new Order('1', array(new OrderItem('1', new Weight(2, WeightUnits::KILOGRAM), 1)));
        $this->assertTrue($method->isAvailable($order));
    }

    public function testNotAvailableOverweight()
    {
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method = DeliveryMethod::fromDTO($dto);

        $order = new Order('1', array(new OrderItem('1', new Weight(9, WeightUnits::KILOGRAM), 1)));
        $this->assertFalse($method->isAvailable($order));
    }

    public function testNotAvailableRateNotEnabledForWeight()
    {
        $dto = $this->deliveryMethodsWithShippingRates[0];
        $method = DeliveryMethod::fromDTO($dto);

        $order = new Order('1', array(new OrderItem('1', new Weight(3, WeightUnits::KILOGRAM), 1)));
        $this->assertFalse($method->isAvailable($order));
    }

    public function testIsAvailableServicePointDelivery(){
        // arrange
        $dto = $this->deliveryMethods[3];

        // act
        $method = DeliveryMethod::fromDTO($dto);
        $order = new Order('1', array(new OrderItem('1', new Weight(22.3, WeightUnits::KILOGRAM), 2)));
        $result = $method->isAvailable($order);

        $this->assertTrue($result);
    }
}
