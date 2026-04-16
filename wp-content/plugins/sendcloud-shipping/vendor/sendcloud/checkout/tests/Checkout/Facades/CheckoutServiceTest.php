<?php

namespace SendCloud\Tests\Checkout\Facades;

use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Domain\Delivery\DeliveryZone;
use SendCloud\Checkout\Domain\Search\Query;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\Domain\FailedToDeleteCheckoutConfigurationException;
use SendCloud\Checkout\Exceptions\Domain\FailedToUpdateCheckoutConfigurationException;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Checkout\Exceptions\HTTP\HttpException;
use SendCloud\Checkout\CheckoutService;
use SendCloud\Tests\Checkout\Facades\Mock\MockDeliveryMethodService;
use SendCloud\Tests\Checkout\Facades\Mock\MockDeliveryMethodSetupService;
use SendCloud\Tests\Checkout\Facades\Mock\MockDeliveryZoneService;
use SendCloud\Tests\Checkout\Facades\Mock\MockDeliveryZoneSetupService;
use SendCloud\Tests\Checkout\Facades\Mock\MockProxy;
use SendCloud\Tests\Common\BaseTest;
use SendCloud\Tests\Common\CallHistory;

/**
 * Class CheckoutServiceTest
 *
 * @package SendCloud\Tests\Checkout\Facades
 */
class CheckoutServiceTest extends BaseTest
{
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Zone\DeliveryZone[]
     */
    public $deliveryZones;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Zone\DeliveryZone
     */
    public $zoneDto;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethod[]
     */
    public $deliveryMethods;
    /**
     * @var \SendCloud\Checkout\API\Checkout\Delivery\Method\DeliveryMethod
     */
    public $methodDto;
    /**
     * @var MockDeliveryMethodService
     */
    public $methodService;
    /**
     * @var MockDeliveryMethodSetupService
     */
    public $methodSetupService;
    /**
     * @var MockDeliveryZoneService
     */
    public $zoneService;
    /**
     * @var MockDeliveryZoneSetupService
     */
    public $zoneSetupService;
    /**
     * @var CheckoutService
     */
    public $service;
    /**
     * @var Checkout
     */
    public $checkout;
    /**
     * @var MockProxy
     */
    public $proxy;
    /**
     * @var Query
     */
    public $query;

    /**
     * @throws DTOValidationException
     */
    public function setUp()
    {
        // Instantiate mock services.
        $this->methodService = new MockDeliveryMethodService(new CallHistory());
        $this->methodSetupService = new MockDeliveryMethodSetupService(new CallHistory());
        $this->zoneService = new MockDeliveryZoneService(new CallHistory());
        $this->zoneSetupService = new MockDeliveryZoneSetupService(new CallHistory());
        $this->proxy = new MockProxy(new CallHistory());

        // Setup mock data.
        $payload = json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true);
        $this->checkout = Checkout::fromArray($payload['checkout_configuration']);

        $this->deliveryZones = $this->checkout->getDeliveryZones();
        $this->zoneDto = $this->deliveryZones[0];

        $this->deliveryMethods = $this->zoneDto->getDeliveryMethods();
        $this->methodDto = $this->deliveryMethods[0];
        $this->query = new Query();

        // Instantiate test subject.
        $this->service = new CheckoutService($this->zoneService, $this->zoneSetupService, $this->methodService, $this->methodSetupService, $this->proxy);
    }

    public function testDeleteObsoleteMethodsCalled()
    {
        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteObsoleteConfigs', 'arguments' => array()), $callHistory[0]);
    }

    public function testDeleteObsoleteZonesCalled()
    {
        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteObsoleteConfigs', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodFindDiffCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'findDiff', 'arguments' => array($methods)), $callHistory[1]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneFindDiffCall()
    {
        // arrange
        $expected = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'findDiff', 'arguments' => array($expected)), $callHistory[1]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodSetupDeleteSpecificCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->deletedMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteSpecific', 'arguments' => array($this->methodService->deletedMethods)), $callHistory[0]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodDeleteSpecificCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->deletedMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteSpecific', 'arguments' => array($this->methodService->deletedMethods)), $callHistory[2]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneSetupDeleteSpecificCall()
    {
        // arrange
        $this->zoneService->deletedZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteSpecific', 'arguments' => array($this->zoneService->deletedZones)), $callHistory[0]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneDeleteSpecificCall()
    {
        // arrange
        $this->zoneService->deletedZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteSpecific', 'arguments' => array($this->zoneService->deletedZones)), $callHistory[2]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodSetupUpdateCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->updatedMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'update', 'arguments' => array($this->methodService->updatedMethods)), $callHistory[1]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodUpdateCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->updatedMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'update', 'arguments' => array($this->methodService->updatedMethods)), $callHistory[3]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneSetupUpdateCall()
    {
        // arrange
        $this->zoneService->updatedZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'update', 'arguments' => array($this->zoneService->updatedZones)), $callHistory[1]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneUpdateCall()
    {
        // arrange
        $this->zoneService->updatedZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'update', 'arguments' => array($this->zoneService->updatedZones)), $callHistory[3]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneSetupCreateCall()
    {
        // arrange
        $this->zoneService->newZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'create', 'arguments' => array($this->zoneService->newZones)), $callHistory[2]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryZoneCreateCall()
    {
        // arrange
        $this->zoneService->newZones = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'create', 'arguments' => array($this->zoneService->newZones)), $callHistory[4]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodSetupCreateCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->newMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'create', 'arguments' => array($this->methodService->newMethods)), $callHistory[2]);
    }

    /**
     * @throws FailedToUpdateCheckoutConfigurationException
     */
    public function testDeliveryMethodCreateCall()
    {
        // arrange
        $methods = array();
        foreach($this->deliveryMethods as $deliveryMethod){
            $method = DeliveryMethod::fromDTO($deliveryMethod);
            $method->setDeliveryZoneId($this->zoneDto->getId());
            $methods[] = $method;
        }
        $this->methodService->newMethods = $methods;

        // act
        $this->service->update($this->checkout);

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'create', 'arguments' => array($this->methodService->newMethods)), $callHistory[4]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function testDeleteDeliveryZoneSetupServiceCalled()
    {
        // act
        $this->service->delete();

        // assert
        $callHistory = $this->zoneSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function testDeleteDeliveryMethodSetupServiceCalled()
    {
        // act
        $this->service->delete();

        // assert
        $callHistory = $this->methodSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function testDeleteDeliveryZoneServiceCalled()
    {
        // act
        $this->service->delete();

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function testDeleteDeliveryMethodServiceCalled()
    {
        // act
        $this->service->delete();

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallDeliveryZoneSetupServiceCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->zoneSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallDeliveryMethodSetupServiceCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->methodSetupService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallDeliveryZoneServiceCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallDeliveryMethodServiceCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAll', 'arguments' => array()), $callHistory[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallDeliveryMethodServiceDeleteAllDataCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->methodService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'deleteAllData', 'arguments' => array()), $callHistory[1]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws HttpException
     */
    public function testUninstallProxyDeleteCalled()
    {
        // act
        $this->service->uninstall();

        // assert
        $callHistory = $this->proxy->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'delete', 'arguments' => array()), $callHistory[0]);
    }

    public function testSearchNoMethodsFound()
    {
        // act
        $result = $this->service->search($this->query);

        // assert
        $this->assertEmpty($result);
    }

    public function testSearchZoneSearchCalled()
    {
        // arrange
        $method = DeliveryMethod::fromDTO($this->methodDto);
        $method->setDeliveryZoneId('1231');
        $this->methodService->methodSearchResult = array($method);

        // act
        $this->service->search($this->query);

        // assert
        $callHistory = $this->zoneService->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'search', 'arguments' => array($this->query)), $callHistory[0]);
    }

    public function testSearchNoZonesFound()
    {
        // arrange
        $this->methodService->methodSearchResult = array();

        // act
        $result = $this->service->search($this->query);

        // assert
        $this->assertEmpty($result);
    }

    public function testSearchMethodInZones()
    {
        // arrange
        $method = DeliveryMethod::fromDTO($this->methodDto);
        $method->setDeliveryZoneId('1');
        $this->methodService->methodSearchResult = array($method);
        $this->zoneService->zoneSearchResult = array(DeliveryZone::fromDTO($this->zoneDto));

        // act
        $result = $this->service->search($this->query);

        // assert
        $this->assertEquals(array($method), $result);
    }
}