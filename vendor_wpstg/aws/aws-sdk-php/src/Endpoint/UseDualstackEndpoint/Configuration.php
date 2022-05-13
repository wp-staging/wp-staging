<?php

namespace WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint;

use WPStaging\Vendor\Aws;
use WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Exception\ConfigurationException;
class Configuration implements \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationInterface
{
    private $useDualstackEndpoint;
    public function __construct($useDualstackEndpoint, $region)
    {
        $this->useDualstackEndpoint = \WPStaging\Vendor\Aws\boolean_value($useDualstackEndpoint);
        if (\is_null($this->useDualstackEndpoint)) {
            throw new \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Exception\ConfigurationException("'use_dual_stack_endpoint' config option" . " must be a boolean value.");
        }
        if ($this->useDualstackEndpoint == \true && (\strpos($region, "iso-") !== \false || \strpos($region, "-iso") !== \false)) {
            throw new \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Exception\ConfigurationException("Dual-stack is not supported in ISO regions");
        }
    }
    /**
     * {@inheritdoc}
     */
    public function isUseDualstackEndpoint()
    {
        return $this->useDualstackEndpoint;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return ['use_dual_stack_endpoint' => $this->isUseDualstackEndpoint()];
    }
}
