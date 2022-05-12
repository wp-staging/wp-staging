<?php

namespace WPStaging\Vendor\Aws\Api;

/**
 * Base class representing a modeled shape.
 */
class Shape extends \WPStaging\Vendor\Aws\Api\AbstractModel
{
    /**
     * Get a concrete shape for the given definition.
     *
     * @param array    $definition
     * @param ShapeMap $shapeMap
     *
     * @return mixed
     * @throws \RuntimeException if the type is invalid
     */
    public static function create(array $definition, \WPStaging\Vendor\Aws\Api\ShapeMap $shapeMap)
    {
        static $map = ['structure' => 'WPStaging\\Vendor\\Aws\\Api\\StructureShape', 'map' => 'WPStaging\\Vendor\\Aws\\Api\\MapShape', 'list' => 'WPStaging\\Vendor\\Aws\\Api\\ListShape', 'timestamp' => 'WPStaging\\Vendor\\Aws\\Api\\TimestampShape', 'integer' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'double' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'float' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'long' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'string' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'byte' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'character' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'blob' => 'WPStaging\\Vendor\\Aws\\Api\\Shape', 'boolean' => 'WPStaging\\Vendor\\Aws\\Api\\Shape'];
        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }
        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException('Invalid type: ' . \print_r($definition, \true));
        }
        $type = $map[$definition['type']];
        return new $type($definition, $shapeMap);
    }
    /**
     * Get the type of the shape
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }
    /**
     * Get the name of the shape
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition['name'];
    }
}
