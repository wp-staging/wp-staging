<?php

/**
 * DisplayText
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
 * DisplayText
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class DisplayText
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_CHOICE, 'children' => ['ia5String' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_IA5_STRING], 'visibleString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_VISIBLE_STRING], 'bmpString' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_BMP_STRING], 'utf8String' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_UTF8_STRING]]];
}
