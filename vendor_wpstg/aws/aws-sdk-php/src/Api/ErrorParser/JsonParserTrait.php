<?php

namespace WPStaging\Vendor\Aws\Api\ErrorParser;

use WPStaging\Vendor\Aws\Api\Parser\PayloadParserTrait;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
/**
 * Provides basic JSON error parsing functionality.
 */
trait JsonParserTrait
{
    use PayloadParserTrait;
    private function genericHandler(\WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        $code = (string) $response->getStatusCode();
        return ['request_id' => (string) $response->getHeaderLine('x-amzn-requestid'), 'code' => null, 'message' => null, 'type' => $code[0] == '4' ? 'client' : 'server', 'parsed' => $this->parseJson($response->getBody(), $response)];
    }
    protected function payload(\WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response, \WPStaging\Vendor\Aws\Api\StructureShape $member)
    {
        $jsonBody = $this->parseJson($response->getBody(), $response);
        if ($jsonBody) {
            return $this->parser->parse($member, $jsonBody);
        }
    }
}
