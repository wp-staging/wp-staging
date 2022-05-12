<?php

namespace WPStaging\Vendor\Aws\Api\Serializer;

use WPStaging\Vendor\Aws\Api\Shape;
use WPStaging\Vendor\Aws\Api\ListShape;
/**
 * @internal
 */
class Ec2ParamBuilder extends \WPStaging\Vendor\Aws\Api\Serializer\QueryParamBuilder
{
    protected function queryName(\WPStaging\Vendor\Aws\Api\Shape $shape, $default = null)
    {
        return ($shape['queryName'] ?: \ucfirst(@$shape['locationName'] ?: "")) ?: $default;
    }
    protected function isFlat(\WPStaging\Vendor\Aws\Api\Shape $shape)
    {
        return \false;
    }
    protected function format_list(\WPStaging\Vendor\Aws\Api\ListShape $shape, array $value, $prefix, &$query)
    {
        // Handle empty list serialization
        if (!$value) {
            $query[$prefix] = \false;
        } else {
            $items = $shape->getMember();
            foreach ($value as $k => $v) {
                $this->format($items, $v, $prefix . '.' . ($k + 1), $query);
            }
        }
    }
}
