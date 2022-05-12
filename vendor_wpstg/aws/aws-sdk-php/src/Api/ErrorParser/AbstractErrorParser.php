<?php

namespace WPStaging\Vendor\Aws\Api\ErrorParser;

use WPStaging\Vendor\Aws\Api\Parser\MetadataParserTrait;
use WPStaging\Vendor\Aws\Api\Parser\PayloadParserTrait;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
abstract class AbstractErrorParser
{
    use MetadataParserTrait;
    use PayloadParserTrait;
    /**
     * @var Service
     */
    protected $api;
    /**
     * @param Service $api
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api = null)
    {
        $this->api = $api;
    }
    protected abstract function payload(\WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response, \WPStaging\Vendor\Aws\Api\StructureShape $member);
    protected function extractPayload(\WPStaging\Vendor\Aws\Api\StructureShape $member, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        if ($member instanceof \WPStaging\Vendor\Aws\Api\StructureShape) {
            // Structure members parse top-level data into a specific key.
            return $this->payload($response, $member);
        } else {
            // Streaming data is just the stream from the response body.
            return $response->getBody();
        }
    }
    protected function populateShape(array &$data, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response, \WPStaging\Vendor\Aws\CommandInterface $command = null)
    {
        $data['body'] = [];
        if (!empty($command) && !empty($this->api)) {
            // If modeled error code is indicated, check for known error shape
            if (!empty($data['code'])) {
                $errors = $this->api->getOperation($command->getName())->getErrors();
                foreach ($errors as $key => $error) {
                    // If error code matches a known error shape, populate the body
                    if ($data['code'] == $error['name'] && $error instanceof \WPStaging\Vendor\Aws\Api\StructureShape) {
                        $modeledError = $error;
                        $data['body'] = $this->extractPayload($modeledError, $response);
                        $data['error_shape'] = $modeledError;
                        foreach ($error->getMembers() as $name => $member) {
                            switch ($member['location']) {
                                case 'header':
                                    $this->extractHeader($name, $member, $response, $data['body']);
                                    break;
                                case 'headers':
                                    $this->extractHeaders($name, $member, $response, $data['body']);
                                    break;
                                case 'statusCode':
                                    $this->extractStatus($name, $response, $data['body']);
                                    break;
                            }
                        }
                        break;
                    }
                }
            }
        }
        return $data;
    }
}
