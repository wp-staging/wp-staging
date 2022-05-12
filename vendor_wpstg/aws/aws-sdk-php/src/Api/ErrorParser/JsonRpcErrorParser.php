<?php

namespace WPStaging\Vendor\Aws\Api\ErrorParser;

use WPStaging\Vendor\Aws\Api\Parser\JsonParser;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
/**
 * Parsers JSON-RPC errors.
 */
class JsonRpcErrorParser extends \WPStaging\Vendor\Aws\Api\ErrorParser\AbstractErrorParser
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
        // Make the casing consistent across services.
        if ($data['parsed']) {
            $data['parsed'] = \array_change_key_case($data['parsed']);
        }
        if (isset($data['parsed']['__type'])) {
            $parts = \explode('#', $data['parsed']['__type']);
            $data['code'] = isset($parts[1]) ? $parts[1] : $parts[0];
            $data['message'] = isset($data['parsed']['message']) ? $data['parsed']['message'] : null;
        }
        $this->populateShape($data, $response, $command);
        return $data;
    }
}
