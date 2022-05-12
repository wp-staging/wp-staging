<?php

namespace WPStaging\Vendor\Aws\Arn\S3;

use WPStaging\Vendor\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface BucketArnInterface extends \WPStaging\Vendor\Aws\Arn\ArnInterface
{
    public function getBucketName();
}
