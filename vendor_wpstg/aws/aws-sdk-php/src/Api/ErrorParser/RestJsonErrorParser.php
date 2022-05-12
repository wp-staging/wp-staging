<?php

namespace WPStaging\Vendor\Aws\Api\ErrorParser;

use WPStaging\Vendor\Aws\Api\Parser\JsonParser;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
/**
 * Parses JSON-REST errors.
 */
class RestJsonErrorParser extends \WPStaging\Vendor\Aws\Api\ErrorParser\AbstractErrorParser
{
    use JsonParserTrait;
    private $parser;
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api = null, \WPStaging\Vendor\Aws\Api\Parser\JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new \WPStaging\Vendor\Aws\Api\Parser\JsonParser();
    }
    public function __invoke(\WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response, \WPStaging\Vendor\Aws\CommandInterface $command = null)
    {
        $data = $this->genericHandler($response);
        // Merge in error data from the JSON body
        if ($json = $data['parsed']) {
            $data = \array_replace($data, $json);
        }
        // Correct error type from services like Amazon Glacier
        if (!empty($data['type'])) {
            $data['type'] = \strtolower($data['type']);
        }
        // Retrieve the error code from services like Amazon Elastic Transcoder
        if ($code = $response->getHeaderLine('x-amzn-errortype')) {
            $colon = \strpos($code, ':');
            $data['code'] = $colon ? \substr($code, 0, $colon) : $code;
        }
        // Retrieve error message directly
        $data['message'] = isset($data['parsed']['message']) ? $data['parsed']['message'] : (isset($data['parsed']['Message']) ? $data['parsed']['Message'] : null);
        $this->populateShape($data, $response, $command);
        return $data;
    }
}
