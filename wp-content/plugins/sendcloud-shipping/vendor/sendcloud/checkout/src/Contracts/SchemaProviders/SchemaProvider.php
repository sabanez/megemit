<?php

namespace SendCloud\Checkout\Contracts\SchemaProviders;

interface SchemaProvider
{
    /**
     * Gets payload schema.
     *
     * @return mixed
     */
    public static function getSchema();
}