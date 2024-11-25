<?php

/**
 * RSAPrivateKey
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
 * RSAPrivateKey
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class RSAPrivateKey
{
    // version must be multi if otherPrimeInfos present
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => [
        'version' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER, 'mapping' => ['two-prime', 'multi']],
        'modulus' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // n
        'publicExponent' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // e
        'privateExponent' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // d
        'prime1' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // p
        'prime2' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // q
        'exponent1' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // d mod (p-1)
        'exponent2' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // d mod (q-1)
        'coefficient' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_INTEGER],
        // (inverse of q) mod p
        'otherPrimeInfos' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\OtherPrimeInfos::MAP + ['optional' => \true],
    ]];
}
