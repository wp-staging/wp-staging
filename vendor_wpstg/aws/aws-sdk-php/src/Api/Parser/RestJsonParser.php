<?php

namespace WPStaging\Vendor\Aws\Api\Parser;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements REST-JSON parsing (e.g., Glacier, Elastic Transcoder)
 */
class RestJsonParser extends \WPStaging\Vendor\Aws\Api\Parser\AbstractRestParser
{
    use PayloadParserTrait;
    /**
     * @param Service    $api    Service description
     * @param JsonParser $parser JSON body builder
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api, \WPStaging\Vendor\Aws\Api\Parser\JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new \WPStaging\Vendor\Aws\Api\Parser\JsonParser();
    }
    protected function payload(\WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response, \WPStaging\Vendor\Aws\Api\StructureShape $member, array &$result)
    {
        $jsonBody = $this->parseJson($response->getBody(), $response);
        if ($jsonBody) {
            $result += $this->parser->parse($member, $jsonBody);
        }
    }
    public function parseMemberFromStream(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream, \WPStaging\Vendor\Aws\Api\StructureShape $member, $response)
    {
        $jsonBody = $this->parseJson($stream, $response);
        if ($jsonBody) {
            return $this->parser->parse($member, $jsonBody);
        }
        return [];
    }
}
