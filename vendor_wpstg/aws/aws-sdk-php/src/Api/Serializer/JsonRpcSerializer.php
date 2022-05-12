<?php

namespace WPStaging\Vendor\Aws\Api\Serializer;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\GuzzleHttp\Psr7\Request;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * Prepares a JSON-RPC request for transfer.
 * @internal
 */
class JsonRpcSerializer
{
    /** @var JsonBody */
    private $jsonFormatter;
    /** @var string */
    private $endpoint;
    /** @var Service */
    private $api;
    /** @var string */
    private $contentType;
    /**
     * @param Service  $api           Service description
     * @param string   $endpoint      Endpoint to connect to
     * @param JsonBody $jsonFormatter Optional JSON formatter to use
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api, $endpoint, \WPStaging\Vendor\Aws\Api\Serializer\JsonBody $jsonFormatter = null)
    {
        $this->endpoint = $endpoint;
        $this->api = $api;
        $this->jsonFormatter = $jsonFormatter ?: new \WPStaging\Vendor\Aws\Api\Serializer\JsonBody($this->api);
        $this->contentType = \WPStaging\Vendor\Aws\Api\Serializer\JsonBody::getContentType($api);
    }
    /**
     * When invoked with an AWS command, returns a serialization array
     * containing "method", "uri", "headers", and "body" key value pairs.
     *
     * @param CommandInterface $command
     *
     * @return RequestInterface
     */
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $command)
    {
        $name = $command->getName();
        $operation = $this->api->getOperation($name);
        return new \WPStaging\Vendor\GuzzleHttp\Psr7\Request($operation['http']['method'], $this->endpoint, ['X-Amz-Target' => $this->api->getMetadata('targetPrefix') . '.' . $name, 'Content-Type' => $this->contentType], $this->jsonFormatter->build($operation->getInput(), $command->toArray()));
    }
}
