<?php

namespace WPStaging\Vendor\Aws\ClientSideMonitoring;

use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Aws\Exception\AwsException;
use WPStaging\Vendor\Aws\ResultInterface;
use WPStaging\Vendor\GuzzleHttp\Psr7\Request;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(\WPStaging\Vendor\Psr\Http\Message\RequestInterface $request);
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $cmd, \WPStaging\Vendor\Psr\Http\Message\RequestInterface $request);
}
