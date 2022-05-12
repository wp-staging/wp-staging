<?php

namespace WPStaging\Vendor\Aws\Api\Parser;

use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\Result;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * @internal Parses query (XML) responses (e.g., EC2, SQS, and many others)
 */
class QueryParser extends \WPStaging\Vendor\Aws\Api\Parser\AbstractParser
{
    use PayloadParserTrait;
    /** @var bool */
    private $honorResultWrapper;
    /**
     * @param Service   $api                Service description
     * @param XmlParser $xmlParser          Optional XML parser
     * @param bool      $honorResultWrapper Set to false to disable the peeling
     *                                      back of result wrappers from the
     *                                      output structure.
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api, \WPStaging\Vendor\Aws\Api\Parser\XmlParser $xmlParser = null, $honorResultWrapper = \true)
    {
        parent::__construct($api);
        $this->parser = $xmlParser ?: new \WPStaging\Vendor\Aws\Api\Parser\XmlParser();
        $this->honorResultWrapper = $honorResultWrapper;
    }
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $command, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        $output = $this->api->getOperation($command->getName())->getOutput();
        $xml = $this->parseXml($response->getBody(), $response);
        if ($this->honorResultWrapper && $output['resultWrapper']) {
            $xml = $xml->{$output['resultWrapper']};
        }
        return new \WPStaging\Vendor\Aws\Result($this->parser->parse($output, $xml));
    }
    public function parseMemberFromStream(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream, \WPStaging\Vendor\Aws\Api\StructureShape $member, $response)
    {
        $xml = $this->parseXml($stream, $response);
        return $this->parser->parse($member, $xml);
    }
}
