<?php

namespace SendCloud\Checkout\Utility;

use SendCloud\Checkout\Domain\Interfaces\Identifiable;

/**
 * Class ArrayToHashMap
 *
 * @package SendCloud\Checkout\Utility
 */
class ArrayToHashMap
{
    /**
     * Converts array to hashmap.
     *
     * @param Identifiable[] $collection
     * @return array
     */
    public static function convert(array $collection)
    {
        $result = array();
        foreach ($collection as $item) {
            $result[$item->getId()] = $item;
        }

        return $result;
    }
}