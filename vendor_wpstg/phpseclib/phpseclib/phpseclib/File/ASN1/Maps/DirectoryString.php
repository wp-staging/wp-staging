<?php

/**
 * DirectoryString
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
 * DirectoryString
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class DirectoryString
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_CHOICE, 'children' => ['teletexString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_TELETEX_STRING], 'printableString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_PRINTABLE_STRING], 'universalString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_UNIVERSAL_STRING], 'utf8String' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_UTF8_STRING], 'bmpString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_BMP_STRING]]];
}
