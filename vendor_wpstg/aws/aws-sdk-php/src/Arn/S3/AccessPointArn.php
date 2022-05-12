<?php

namespace WPStaging\Vendor\Aws\Arn\S3;

use WPStaging\Vendor\Aws\Arn\AccessPointArn as BaseAccessPointArn;
use WPStaging\Vendor\Aws\Arn\AccessPointArnInterface;
use WPStaging\Vendor\Aws\Arn\ArnInterface;
use WPStaging\Vendor\Aws\Arn\Exception\InvalidArnException;
/**
 * @internal
 */
class AccessPointArn extends \WPStaging\Vendor\Aws\Arn\AccessPointArn implements \WPStaging\Vendor\Aws\Arn\AccessPointArnInterface
{
    /**
     * Validation specific to AccessPointArn
     *
     * @param array $data
     */
    public static function validate(array $data)
    {
        parent::validate($data);
        if ($data['service'] !== 's3') {
            throw new \WPStaging\Vendor\Aws\Arn\Exception\InvalidArnException("The 3rd component of an S3 access" . " point ARN represents the region and must be 's3'.");
        }
    }
}
