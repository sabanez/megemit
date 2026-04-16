<?php

namespace SendCloud\Checkout\Validators;

use SendCloud\Checkout\Contracts\Validators\RequestValidator;
use SendCloud\Checkout\HTTP\Request;

class NullRequestValidator implements RequestValidator
{
    /**
     * Omits validation if validation for particular request type is not necessary.
     *
     * @param Request $request
     */
    public function validate(Request $request)
    {
        // Intentionally left empty.
    }
}