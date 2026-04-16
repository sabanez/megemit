<?php

namespace SendCloud\Checkout\Domain\Interfaces;

interface Identifiable
{
    /**
     * Provides entity id.
     *
     * @return string | int
     */
    public function getId();
}