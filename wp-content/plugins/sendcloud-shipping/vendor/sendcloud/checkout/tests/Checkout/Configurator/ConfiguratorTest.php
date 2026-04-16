<?php

namespace SendCloud\Tests\Checkout\Configurator;

use SendCloud\Checkout\Configurator;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\HTTP\Request;
use SendCloud\Checkout\Exceptions\Domain\FailedToDeleteCheckoutConfigurationException;
use SendCloud\Checkout\Exceptions\Domain\FailedToUpdateCheckoutConfigurationException;
use SendCloud\Checkout\Exceptions\DTO\DTOValidationException;
use SendCloud\Checkout\Exceptions\ValidationException;
use SendCloud\Tests\Checkout\Configurator\Mock\MockCheckoutService;
use SendCloud\Tests\Checkout\Configurator\Mock\MockValidator;
use SendCloud\Tests\Common\BaseTest;

class ConfiguratorTest extends BaseTest
{
    /**
     * @var MockValidator
     */
    public $updateValidator;
    /**
     * @var MockCheckoutService
     */
    public $service;
    /**
     * @var Configurator
     */
    public $configurator;
    /**
     * @var MockValidator
     */
    public $deleteValidator;

    protected function setUp()
    {
        $this->updateValidator = new MockValidator();
        $this->deleteValidator = new MockValidator();
        $this->service = new MockCheckoutService();
        $this->configurator = new Configurator($this->updateValidator, $this->deleteValidator, $this->service);
    }

    /**
     * @throws DTOValidationException
     * @throws FailedToUpdateCheckoutConfigurationException
     * @throws ValidationException
     */
    public function testValidatorCalled()
    {
        // arrange
        $request = new Request(json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true), array());

        // act
        $this->configurator->update($request);

        // assert
        $this->assertEquals(array(array('name' => 'validate', 'arguments' => array($request))), $this->updateValidator->callHistory->getCallHistory());
    }

    /**
     * @throws DTOValidationException
     * @throws FailedToUpdateCheckoutConfigurationException
     * @throws ValidationException
     */
    public function testServiceCalled()
    {
        // arrange
        $request = new Request(json_decode(file_get_contents(__DIR__ . '/../../Common/APIDataExamples/checkout.json'), true), array());
        $body = $request->getBody();

        // act
        $this->configurator->update($request);

        // assert
        $this->assertEquals(array(array('name' => 'update', 'arguments' => array(Checkout::fromArray($body['checkout_configuration'])))), $this->service->callHistory->getCallHistory());
    }

    /**
     * @throws ValidationException
     * @throws FailedToDeleteCheckoutConfigurationException
     */
    public function testDeleteValidatorCalled()
    {
        // arrange
        $request = new Request(array(), array());
        $expected = array($request);

        // act
        $this->configurator->deleteAll($request);

        // assert
        $history = $this->deleteValidator->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'validate', 'arguments' => $expected), $history[0]);
    }

    /**
     * @throws FailedToDeleteCheckoutConfigurationException
     * @throws ValidationException
     */
    public function testDeleteServiceCalled()
    {
        // arrange
        $request = new Request(array(), array());
        $expected = array();

        // act
        $this->configurator->deleteAll($request);

        // assert
        $history = $this->service->callHistory->getCallHistory();
        $this->assertEquals(array('name' => 'delete', 'arguments' => $expected), $history[0]);
    }
}