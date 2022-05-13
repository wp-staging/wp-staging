<?php

namespace WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint;

use WPStaging\Vendor\Aws;
use WPStaging\Vendor\Aws\ClientResolver;
use WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\Exception\ConfigurationException;
class Configuration implements \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationInterface
{
    private $useFipsEndpoint;
    public function __construct($useFipsEndpoint)
    {
        $this->useFipsEndpoint = \WPStaging\Vendor\Aws\boolean_value($useFipsEndpoint);
        if (\is_null($this->useFipsEndpoint)) {
            throw new \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\Exception\ConfigurationException("'use_fips_endpoint' config option" . " must be a boolean value.");
        }
    }
    /**
     * {@inheritdoc}
     */
    public function isUseFipsEndpoint()
    {
        return $this->useFipsEndpoint;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return ['use_fips_endpoint' => $this->isUseFipsEndpoint()];
    }
}
