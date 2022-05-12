<?php

namespace WPStaging\Vendor\Aws\S3;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Arn\ArnInterface;
use WPStaging\Vendor\Aws\Arn\S3\OutpostsArnInterface;
use WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider;
use WPStaging\Vendor\Aws\Exception\InvalidRegionException;
/**
 * @internal
 */
trait EndpointRegionHelperTrait
{
    /** @var array */
    private $config;
    /** @var PartitionEndpointProvider */
    private $partitionProvider;
    /** @var string */
    private $region;
    /** @var Service */
    private $service;
    private function getPartitionSuffix(\WPStaging\Vendor\Aws\Arn\ArnInterface $arn, \WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider $provider)
    {
        $partition = $provider->getPartition($arn->getRegion(), $arn->getService());
        return $partition->getDnsSuffix();
    }
    private function getSigningRegion($region, $service, \WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider $provider)
    {
        $partition = $provider->getPartition($region, $service);
        $data = $partition->toArray();
        if (isset($data['services'][$service]['endpoints'][$region]['credentialScope']['region'])) {
            return $data['services'][$service]['endpoints'][$region]['credentialScope']['region'];
        }
        return $region;
    }
    private function isMatchingSigningRegion($arnRegion, $clientRegion, $service, \WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider $provider)
    {
        $arnRegion = \WPStaging\Vendor\Aws\strip_fips_pseudo_regions(\strtolower($arnRegion));
        $clientRegion = \strtolower($clientRegion);
        if ($arnRegion === $clientRegion) {
            return \true;
        }
        if ($this->getSigningRegion($clientRegion, $service, $provider) === $arnRegion) {
            return \true;
        }
        return \false;
    }
    private function validateFipsConfigurations(\WPStaging\Vendor\Aws\Arn\ArnInterface $arn)
    {
        $useFipsEndpoint = !empty($this->config['use_fips_endpoint']);
        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\OutpostsArnInterface) {
            if (empty($this->config['use_arn_region']) || !$this->config['use_arn_region']->isUseArnRegion()) {
                $region = $this->region;
            } else {
                $region = $arn->getRegion();
            }
            if (\WPStaging\Vendor\Aws\is_fips_pseudo_region($region)) {
                throw new \WPStaging\Vendor\Aws\Exception\InvalidRegionException('Fips is currently not supported with S3 Outposts access' . ' points. Please provide a non-fips region or do not supply an' . ' access point ARN.');
            }
        }
    }
    private function validateMatchingRegion(\WPStaging\Vendor\Aws\Arn\ArnInterface $arn)
    {
        if (!$this->isMatchingSigningRegion($arn->getRegion(), $this->region, $this->service->getEndpointPrefix(), $this->partitionProvider)) {
            if (empty($this->config['use_arn_region']) || !$this->config['use_arn_region']->isUseArnRegion()) {
                throw new \WPStaging\Vendor\Aws\Exception\InvalidRegionException('The region' . " specified in the ARN (" . $arn->getRegion() . ") does not match the client region (" . "{$this->region}).");
            }
        }
    }
}
