<?php

namespace WPStaging\Vendor\Aws\Api\Parser;

use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Result;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements JSON-RPC parsing (e.g., DynamoDB)
 */
class JsonRpcParser extends \WPStaging\Vendor\Aws\Api\Parser\AbstractParser
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
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $command, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        $operation = $this->api->getOperation($command->getName());
        $result = null === $operation['output'] ? null : $this->parseMemberFromStream($response->getBody(), $operation->getOutput(), $response);
        return new \WPStaging\Vendor\Aws\Result($result ?: []);
    }
    public function parseMemberFromStream(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream, \WPStaging\Vendor\Aws\Api\StructureShape $member, $response)
    {
        return $this->parser->parse($member, $this->parseJson($stream, $response));
    }
}
