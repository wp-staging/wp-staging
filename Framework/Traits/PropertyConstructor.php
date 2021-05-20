<?php

/**
 * Provides methods to build a value object from a map of values that will be mapped
 * to the object properties.
 *
 * @package WPStaging\Framework\Traits
 */

namespace WPStaging\Framework\Traits;

/**
 * Trait PropertyConstructor
 *
 * @package WPStaging\Framework\Traits
 */
trait PropertyConstructor
{

    /**
     * JobArguments constructor.
     *
     * @param array<string,mixed> $props A map from the property names to their values.
     */
    public function __construct(array $props = [])
    {
        foreach ($props as $prop => $value) {
            if (property_exists($this, $prop)) {
                $this->{$prop} = $value;
            }
        }
    }
}
