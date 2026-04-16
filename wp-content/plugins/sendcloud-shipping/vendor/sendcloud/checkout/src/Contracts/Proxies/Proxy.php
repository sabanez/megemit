<?php

namespace SendCloud\Checkout\Contracts\Proxies;

use SendCloud\Checkout\Exceptions\HTTP\HttpException;

/**
 * Interface Proxy
 *
 * @package SendCloud\Checkout\Contracts\Proxies
 */
interface Proxy
{
    /**
     * Deletes configuration on middleware.
     *
     * @return void
     *
     * @throws HttpException
     */
    public function delete();
}