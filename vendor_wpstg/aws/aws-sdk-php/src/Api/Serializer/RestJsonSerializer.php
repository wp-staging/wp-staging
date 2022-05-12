<?php

namespace WPStaging\Vendor\Aws\Api\Serializer;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
/**
 * Serializes requests for the REST-JSON protocol.
 * @internal
 */
class RestJsonSerializer extends \WPStaging\Vendor\Aws\Api\Serializer\RestSerializer
{
    /** @var JsonBody */
    private $jsonFormatter;
    /** @var string */
    private $contentType;
    /**
     * @param Service  $api           Service API description
     * @param string   $endpoint      Endpoint to connect to
     * @param JsonBody $jsonFormatter Optional JSON formatter to use
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api, $endpoint, \WPStaging\Vendor\Aws\Api\Serializer\JsonBody $jsonFormatter = null)
    {
        parent::__construct($api, $endpoint);
        $this->contentType = 'application/json';
        $this->jsonFormatter = $jsonFormatter ?: new \WPStaging\Vendor\Aws\Api\Serializer\JsonBody($api);
    }
    protected function payload(\WPStaging\Vendor\Aws\Api\StructureShape $member, array $value, array &$opts)
    {
        $body = isset($value) ? (string) $this->jsonFormatter->build($member, $value) : "{}";
        $opts['headers']['Content-Type'] = $this->contentType;
        $opts['body'] = $body;
    }
}
