<?php

/**
 * ORAddress
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
 * ORAddress
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class ORAddress
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => ['built-in-standard-attributes' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\BuiltInStandardAttributes::MAP, 'built-in-domain-defined-attributes' => ['optional' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\BuiltInDomainDefinedAttributes::MAP, 'extension-attributes' => ['optional' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\ExtensionAttributes::MAP]];
}
