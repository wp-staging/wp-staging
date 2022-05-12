<?php

namespace WPStaging\Vendor\Aws\S3;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Arn\AccessPointArnInterface;
use WPStaging\Vendor\Aws\Arn\ArnParser;
use WPStaging\Vendor\Aws\Arn\ObjectLambdaAccessPointArn;
use WPStaging\Vendor\Aws\Arn\Exception\InvalidArnException;
use WPStaging\Vendor\Aws\Arn\AccessPointArn as BaseAccessPointArn;
use WPStaging\Vendor\Aws\Arn\S3\OutpostsAccessPointArn;
use WPStaging\Vendor\Aws\Arn\S3\MultiRegionAccessPointArn;
use WPStaging\Vendor\Aws\Arn\S3\OutpostsArnInterface;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider;
use WPStaging\Vendor\Aws\Exception\InvalidRegionException;
use WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException;
use WPStaging\Vendor\Aws\S3\Exception\S3Exception;
use InvalidArgumentException;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * Checks for access point ARN in members targeting BucketName, modifying
 * endpoint as appropriate
 *
 * @internal
 */
class BucketEndpointArnMiddleware
{
    use EndpointRegionHelperTrait;
    /** @var callable */
    private $nextHandler;
    /** @var array */
    private $nonArnableCommands = ['CreateBucket'];
    /**
     * Create a middleware wrapper function.
     *
     * @param Service $service
     * @param $region
     * @param array $config
     * @return callable
     */
    public static function wrap(\WPStaging\Vendor\Aws\Api\Service $service, $region, array $config)
    {
        return function (callable $handler) use($service, $region, $config) {
            return new self($handler, $service, $region, $config);
        };
    }
    public function __construct(callable $nextHandler, \WPStaging\Vendor\Aws\Api\Service $service, $region, array $config = [])
    {
        $this->partitionProvider = \WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider::defaultProvider();
        $this->region = $region;
        $this->service = $service;
        $this->config = $config;
        $this->nextHandler = $nextHandler;
    }
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $cmd, \WPStaging\Vendor\Psr\Http\Message\RequestInterface $req)
    {
        $nextHandler = $this->nextHandler;
        $op = $this->service->getOperation($cmd->getName())->toArray();
        if (!empty($op['input']['shape'])) {
            $service = $this->service->toArray();
            if (!empty($input = $service['shapes'][$op['input']['shape']])) {
                foreach ($input['members'] as $key => $member) {
                    if ($member['shape'] === 'BucketName') {
                        $arnableKey = $key;
                        break;
                    }
                }
                if (!empty($arnableKey) && \WPStaging\Vendor\Aws\Arn\ArnParser::isArn($cmd[$arnableKey])) {
                    try {
                        // Throw for commands that do not support ARN inputs
                        if (\in_array($cmd->getName(), $this->nonArnableCommands)) {
                            throw new \WPStaging\Vendor\Aws\S3\Exception\S3Exception('ARN values cannot be used in the bucket field for' . ' the ' . $cmd->getName() . ' operation.', $cmd);
                        }
                        $arn = \WPStaging\Vendor\Aws\Arn\ArnParser::parse($cmd[$arnableKey]);
                        $partition = $this->validateArn($arn);
                        $host = $this->generateAccessPointHost($arn, $req);
                        // Remove encoded bucket string from path
                        $path = $req->getUri()->getPath();
                        $encoded = \rawurlencode($cmd[$arnableKey]);
                        $len = \strlen($encoded) + 1;
                        if (\trim(\substr($path, 0, $len), '/') === "{$encoded}") {
                            $path = \substr($path, $len);
                            if (\substr($path, 0, 1) !== "/") {
                                $path = '/' . $path;
                            }
                        }
                        if (empty($path)) {
                            $path = '';
                        }
                        // Set modified request
                        $req = $req->withUri($req->getUri()->withPath($path)->withHost($host));
                        // Update signing region based on ARN data if configured to do so
                        if ($this->config['use_arn_region']->isUseArnRegion() && !$this->config['use_fips_endpoint']->isUseFipsEndpoint()) {
                            $region = $arn->getRegion();
                        } else {
                            $region = $this->region;
                        }
                        $endpointData = $partition(['region' => $region, 'service' => $arn->getService()]);
                        $cmd['@context']['signing_region'] = $endpointData['signingRegion'];
                        // Update signing service for Outposts and Lambda ARNs
                        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\OutpostsArnInterface || $arn instanceof \WPStaging\Vendor\Aws\Arn\ObjectLambdaAccessPointArn) {
                            $cmd['@context']['signing_service'] = $arn->getService();
                        }
                    } catch (\WPStaging\Vendor\Aws\Arn\Exception\InvalidArnException $e) {
                        // Add context to ARN exception
                        throw new \WPStaging\Vendor\Aws\S3\Exception\S3Exception('Bucket parameter parsed as ARN and failed with: ' . $e->getMessage(), $cmd, [], $e);
                    }
                }
            }
        }
        return $nextHandler($cmd, $req);
    }
    private function generateAccessPointHost(\WPStaging\Vendor\Aws\Arn\AccessPointArn $arn, \WPStaging\Vendor\Psr\Http\Message\RequestInterface $req)
    {
        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\OutpostsAccessPointArn) {
            $accesspointName = $arn->getAccesspointName();
        } else {
            $accesspointName = $arn->getResourceId();
        }
        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\MultiRegionAccessPointArn) {
            $partition = $this->partitionProvider->getPartitionByName($arn->getPartition(), 's3');
            $dnsSuffix = $partition->getDnsSuffix();
            return "{$accesspointName}.accesspoint.s3-global.{$dnsSuffix}";
        }
        $host = "{$accesspointName}-" . $arn->getAccountId();
        $useFips = $this->config['use_fips_endpoint']->isUseFipsEndpoint();
        $fipsString = $useFips ? "-fips" : "";
        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\OutpostsAccessPointArn) {
            $host .= '.' . $arn->getOutpostId() . '.s3-outposts';
        } else {
            if ($arn instanceof \WPStaging\Vendor\Aws\Arn\ObjectLambdaAccessPointArn) {
                if (!empty($this->config['endpoint'])) {
                    return $host . '.' . $this->config['endpoint'];
                } else {
                    $host .= ".s3-object-lambda{$fipsString}";
                }
            } else {
                $host .= ".s3-accesspoint{$fipsString}";
                if (!empty($this->config['dual_stack'])) {
                    $host .= '.dualstack';
                }
            }
        }
        if (!empty($this->config['use_arn_region']->isUseArnRegion())) {
            $region = $arn->getRegion();
        } else {
            $region = $this->region;
        }
        $region = \WPStaging\Vendor\Aws\strip_fips_pseudo_regions($region);
        $host .= '.' . $region . '.' . $this->getPartitionSuffix($arn, $this->partitionProvider);
        return $host;
    }
    /**
     * Validates an ARN, returning a partition object corresponding to the ARN
     * if successful
     *
     * @param $arn
     * @return \Aws\Endpoint\Partition
     */
    private function validateArn($arn)
    {
        if ($arn instanceof \WPStaging\Vendor\Aws\Arn\AccessPointArnInterface) {
            // Dualstack is not supported with Outposts access points
            if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\OutpostsAccessPointArn && !empty($this->config['dual_stack'])) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Dualstack is currently not supported with S3 Outposts access' . ' points. Please disable dualstack or do not supply an' . ' access point ARN.');
            }
            if ($arn instanceof \WPStaging\Vendor\Aws\Arn\S3\MultiRegionAccessPointArn) {
                if (!empty($this->config['disable_multiregion_access_points'])) {
                    throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Multi-Region Access Point ARNs are disabled, but one was provided.  Please' . ' enable them or provide a different ARN.');
                }
                if (!empty($this->config['dual_stack'])) {
                    throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Multi-Region Access Point ARNs do not currently support dual stack. Please' . ' disable dual stack or provide a different ARN.');
                }
            }
            // Accelerate is not supported with access points
            if (!empty($this->config['accelerate'])) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Accelerate is currently not supported with access points.' . ' Please disable accelerate or do not supply an access' . ' point ARN.');
            }
            // Path-style is not supported with access points
            if (!empty($this->config['path_style'])) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Path-style addressing is currently not supported with' . ' access points. Please disable path-style or do not' . ' supply an access point ARN.');
            }
            // Custom endpoint is not supported with access points
            if (!\is_null($this->config['endpoint']) && !$arn instanceof \WPStaging\Vendor\Aws\Arn\ObjectLambdaAccessPointArn) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('A custom endpoint has been supplied along with an access' . ' point ARN, and these are not compatible with each other.' . ' Please only use one or the other.');
            }
            // Dualstack is not supported with object lambda access points
            if ($arn instanceof \WPStaging\Vendor\Aws\Arn\ObjectLambdaAccessPointArn && !empty($this->config['dual_stack'])) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Dualstack is currently not supported with Object Lambda access' . ' points. Please disable dualstack or do not supply an' . ' access point ARN.');
            }
            // Global endpoints do not support cross-region requests
            if ($this->isGlobal($this->region) && $this->config['use_arn_region']->isUseArnRegion() == \false && $arn->getRegion() != $this->region && !$arn instanceof \WPStaging\Vendor\Aws\Arn\S3\MultiRegionAccessPointArn) {
                throw new \WPStaging\Vendor\Aws\Exception\UnresolvedEndpointException('Global endpoints do not support cross region requests.' . ' Please enable use_arn_region or do not supply a global region' . ' with a different region in the ARN.');
            }
            // Get partitions for ARN and client region
            $arnPart = $this->partitionProvider->getPartition($arn->getRegion(), 's3');
            $clientPart = $this->partitionProvider->getPartition($this->region, 's3');
            // If client partition not found, try removing pseudo-region qualifiers
            if (!$clientPart->isRegionMatch($this->region, 's3')) {
                $clientPart = $this->partitionProvider->getPartition(\WPStaging\Vendor\Aws\strip_fips_pseudo_regions($this->region), 's3');
            }
            if (!$arn instanceof \WPStaging\Vendor\Aws\Arn\S3\MultiRegionAccessPointArn) {
                // Verify that the partition matches for supplied partition and region
                if ($arn->getPartition() !== $clientPart->getName()) {
                    throw new \WPStaging\Vendor\Aws\Exception\InvalidRegionException('The supplied ARN partition' . " does not match the client's partition.");
                }
                if ($clientPart->getName() !== $arnPart->getName()) {
                    throw new \WPStaging\Vendor\Aws\Exception\InvalidRegionException('The corresponding partition' . ' for the supplied ARN region does not match the' . " client's partition.");
                }
                // Ensure ARN region matches client region unless
                // configured for using ARN region over client region
                $this->validateMatchingRegion($arn);
                // Ensure it is not resolved to fips pseudo-region for S3 Outposts
                $this->validateFipsConfigurations($arn);
            }
            return $arnPart;
        }
        throw new \WPStaging\Vendor\Aws\Arn\Exception\InvalidArnException('Provided ARN was not a valid S3 access' . ' point ARN or S3 Outposts access point ARN.');
    }
    /**
     * Checks if a region is global
     *
     * @param $region
     * @return bool
     */
    private function isGlobal($region)
    {
        return $region == 's3-external-1' || $region == 'aws-global';
    }
}
