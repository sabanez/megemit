<?php


namespace SendCloud\Checkout\Contracts\Validators;

use SendCloud\Checkout\HTTP\Request;
use SendCloud\Checkout\Exceptions\ValidationException;

/**
 * Interface RequestValidator
 *
 * @package SendCloud\Checkout\Contracts
 */
interface RequestValidator
{
    /**
     * Validates array.
     *
     * @param Request $request
     *
     * @return void
     *
     * @throws ValidationException
     */
    public function validate(Request $request);
}