<?php

/**
 * DigestInfo
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace WPStaging\Vendor\phpseclib3\File\ASN1\Maps;

use WPStaging\Vendor\phpseclib3\File\ASN1;
/**
 * DigestInfo
 *
 * from https://tools.ietf.org/html/rfc2898#appendix-A.3
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class DigestInfo
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => ['digestAlgorithm' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\AlgorithmIdentifier::MAP, 'digest' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_OCTET_STRING]]];
}
