<?php

/**
 * SubjectPublicKeyInfo
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
 * SubjectPublicKeyInfo
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class SubjectPublicKeyInfo
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => ['algorithm' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\AlgorithmIdentifier::MAP, 'subjectPublicKey' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_BIT_STRING]]];
}
