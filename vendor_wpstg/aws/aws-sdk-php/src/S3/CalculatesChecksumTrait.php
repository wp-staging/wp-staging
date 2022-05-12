<?php

namespace WPStaging\Vendor\Aws\S3;

use WPStaging\Vendor\AWS\CRT\CRT;
use WPStaging\Vendor\Aws\Exception\CommonRuntimeException;
use WPStaging\Vendor\GuzzleHttp\Psr7;
use InvalidArgumentException;
trait CalculatesChecksumTrait
{
    /**
     * @param string $requestedAlgorithm  the algorithm to encode with
     * @param string $value               the value to be encoded
     * @return string
     */
    public static function getEncodedValue($requestedAlgorithm, $value)
    {
        $requestedAlgorithm = \strtolower($requestedAlgorithm);
        $useCrt = \extension_loaded('awscrt');
        if ($useCrt) {
            switch ($requestedAlgorithm) {
                case 'crc32c':
                    return \WPStaging\Vendor\AWS\CRT\CRT::crc32c($value);
                case 'crc32':
                    return \WPStaging\Vendor\AWS\CRT\CRT::crc32($value);
                case 'sha256':
                case 'sha1':
                    return \base64_encode(\WPStaging\Vendor\GuzzleHttp\Psr7\Utils::hash($value, $requestedAlgorithm, \true));
                default:
                    break;
                    throw new \InvalidArgumentException("Invalid checksum requested: {$requestedAlgorithm}." . "  Valid algorithms are CRC32C, CRC32, SHA256, and SHA1.");
            }
        } else {
            if ($requestedAlgorithm == 'crc32c') {
                throw new \WPStaging\Vendor\Aws\Exception\CommonRuntimeException("crc32c is not supported for checksums " . "without use of the common runtime for php.  Please enable the CRT or choose " . "a different algorithm.");
            }
            if ($requestedAlgorithm == "crc32") {
                $requestedAlgorithm = "crc32b";
            }
            return \base64_encode(\WPStaging\Vendor\GuzzleHttp\Psr7\Utils::hash($value, $requestedAlgorithm, \true));
        }
    }
}
