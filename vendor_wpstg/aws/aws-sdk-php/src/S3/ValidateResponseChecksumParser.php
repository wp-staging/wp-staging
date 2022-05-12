<?php

namespace WPStaging\Vendor\Aws\S3;

use WPStaging\Vendor\Aws\Api\Parser\AbstractParser;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\CommandInterface;
use WPStaging\Vendor\Aws\S3\Exception\S3Exception;
use WPStaging\Vendor\Psr\Http\Message\ResponseInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * @internal Decorates a parser for the S3 service to validate the response checksum.
 */
class ValidateResponseChecksumParser extends \WPStaging\Vendor\Aws\Api\Parser\AbstractParser
{
    use CalculatesChecksumTrait;
    /**
     * @param callable $parser Parser to wrap.
     */
    public function __construct(callable $parser, \WPStaging\Vendor\Aws\Api\Service $api)
    {
        $this->api = $api;
        $this->parser = $parser;
    }
    public function __invoke(\WPStaging\Vendor\Aws\CommandInterface $command, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        $fn = $this->parser;
        $result = $fn($command, $response);
        //Skip this middleware if the operation doesn't have an httpChecksum
        $op = $this->api->getOperation($command->getName());
        $checksumInfo = isset($op['httpChecksum']) ? $op['httpChecksum'] : [];
        if (empty($checksumInfo)) {
            return $result;
        }
        //Skip this middleware if the operation doesn't send back a checksum, or the user doesn't opt in
        $checksumModeEnabledMember = isset($checksumInfo['requestValidationModeMember']) ? $checksumInfo['requestValidationModeMember'] : "";
        $checksumModeEnabled = isset($command[$checksumModeEnabledMember]) ? $command[$checksumModeEnabledMember] : "";
        $responseAlgorithms = isset($checksumInfo['responseAlgorithms']) ? $checksumInfo['responseAlgorithms'] : [];
        if (empty($responseAlgorithms) || \strtolower($checksumModeEnabled) !== "enabled") {
            return $result;
        }
        if (\extension_loaded('awscrt')) {
            $checksumPriority = ['CRC32C', 'CRC32', 'SHA1', 'SHA256'];
        } else {
            $checksumPriority = ['CRC32', 'SHA1', 'SHA256'];
        }
        $checksumsToCheck = \array_intersect($responseAlgorithms, $checksumPriority);
        $checksumValidationInfo = $this->validateChecksum($checksumsToCheck, $response);
        if ($checksumValidationInfo['status'] == "SUCCEEDED") {
            $result['ChecksumValidated'] = $checksumValidationInfo['checksum'];
        } else {
            if ($checksumValidationInfo['status'] == "FAILED") {
                //Ignore failed validations on GetObject if it's a multipart get which returned a full multipart object
                if ($command->getName() == "GetObject" && !empty($checksumValidationInfo['checksumHeaderValue'])) {
                    $headerValue = $checksumValidationInfo['checksumHeaderValue'];
                    $lastDashPos = \strrpos($headerValue, '-');
                    $endOfChecksum = \substr($headerValue, $lastDashPos + 1);
                    if (\is_numeric($endOfChecksum) && \intval($endOfChecksum) > 1 && \intval($endOfChecksum) < 10000) {
                        return $result;
                    }
                }
                throw new \WPStaging\Vendor\Aws\S3\Exception\S3Exception("Calculated response checksum did not match the expected value", $command);
            }
        }
        return $result;
    }
    public function parseMemberFromStream(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream, \WPStaging\Vendor\Aws\Api\StructureShape $member, $response)
    {
        return $this->parser->parseMemberFromStream($stream, $member, $response);
    }
    /**
     * @param $checksumPriority
     * @param ResponseInterface $response
     */
    public function validateChecksum($checksumPriority, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        $checksumToValidate = $this->chooseChecksumHeaderToValidate($checksumPriority, $response);
        $validationStatus = "SKIPPED";
        $checksumHeaderValue = null;
        if (!empty($checksumToValidate)) {
            $checksumHeaderValue = $response->getHeader('x-amz-checksum-' . $checksumToValidate);
            if (isset($checksumHeaderValue)) {
                $checksumHeaderValue = $checksumHeaderValue[0];
                $calculatedChecksumValue = $this->getEncodedValue($checksumToValidate, $response->getBody());
                $validationStatus = $checksumHeaderValue == $calculatedChecksumValue ? "SUCCEEDED" : "FAILED";
            }
        }
        return ["status" => $validationStatus, "checksum" => $checksumToValidate, "checksumHeaderValue" => $checksumHeaderValue];
    }
    /**
     * @param $checksumPriority
     * @param ResponseInterface $response
     */
    public function chooseChecksumHeaderToValidate($checksumPriority, \WPStaging\Vendor\Psr\Http\Message\ResponseInterface $response)
    {
        foreach ($checksumPriority as $checksum) {
            $checksumHeader = 'x-amz-checksum-' . $checksum;
            if ($response->hasHeader($checksumHeader)) {
                return $checksum;
            }
        }
        return null;
    }
}
