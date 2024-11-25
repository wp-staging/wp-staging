<?php

/**
 * GeneralName
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
 * GeneralName
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class GeneralName
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_CHOICE, 'children' => ['otherName' => ['constant' => 0, 'optional' => \true, 'implicit' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\AnotherName::MAP, 'rfc822Name' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_IA5_STRING, 'constant' => 1, 'optional' => \true, 'implicit' => \true], 'dNSName' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_IA5_STRING, 'constant' => 2, 'optional' => \true, 'implicit' => \true], 'x400Address' => ['constant' => 3, 'optional' => \true, 'implicit' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\ORAddress::MAP, 'directoryName' => ['constant' => 4, 'optional' => \true, 'explicit' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\Name::MAP, 'ediPartyName' => ['constant' => 5, 'optional' => \true, 'implicit' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\EDIPartyName::MAP, 'uniformResourceIdentifier' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_IA5_STRING, 'constant' => 6, 'optional' => \true, 'implicit' => \true], 'iPAddress' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_OCTET_STRING, 'constant' => 7, 'optional' => \true, 'implicit' => \true], 'registeredID' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_OBJECT_IDENTIFIER, 'constant' => 8, 'optional' => \true, 'implicit' => \true]]];
}
