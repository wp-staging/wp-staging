<?php

namespace WPStaging\Vendor\Aws\Arn\S3;

use WPStaging\Vendor\Aws\Arn\Arn;
use WPStaging\Vendor\Aws\Arn\ResourceTypeAndIdTrait;
/**
 * This class represents an S3 multi-region bucket ARN, which is in the
 * following format:
 *
 * @internal
 */
class MultiRegionAccessPointArn extends \WPStaging\Vendor\Aws\Arn\S3\AccessPointArn
{
    use ResourceTypeAndIdTrait;
    /**
     * Parses a string into an associative array of components that represent
     * a MultiRegionArn
     *
     * @param $string
     * @return array
     */
    public static function parse($string)
    {
        return parent::parse($string);
    }
    /**
     *
     * @param array $data
     */
    public static function validate(array $data)
    {
        \WPStaging\Vendor\Aws\Arn\Arn::validate($data);
    }
}
