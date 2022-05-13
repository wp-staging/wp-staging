<?php

namespace WPStaging\Vendor\Aws\Arn;

/**
 * @internal
 */
interface AccessPointArnInterface extends \WPStaging\Vendor\Aws\Arn\ArnInterface
{
    public function getAccesspointName();
}
