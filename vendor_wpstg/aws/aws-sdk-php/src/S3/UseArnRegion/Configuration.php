<?php

namespace WPStaging\Vendor\Aws\S3\UseArnRegion;

use WPStaging\Vendor\Aws;
use WPStaging\Vendor\Aws\S3\UseArnRegion\Exception\ConfigurationException;
class Configuration implements \WPStaging\Vendor\Aws\S3\UseArnRegion\ConfigurationInterface
{
    private $useArnRegion;
    public function __construct($useArnRegion)
    {
        $this->useArnRegion = \WPStaging\Vendor\Aws\boolean_value($useArnRegion);
        if (\is_null($this->useArnRegion)) {
            throw new \WPStaging\Vendor\Aws\S3\UseArnRegion\Exception\ConfigurationException("'use_arn_region' config option" . " must be a boolean value.");
        }
    }
    /**
     * {@inheritdoc}
     */
    public function isUseArnRegion()
    {
        return $this->useArnRegion;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return ['use_arn_region' => $this->isUseArnRegion()];
    }
}
