<?php

/**
 * RevokedCertificate
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
 * RevokedCertificate
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class RevokedCertificate
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => ['userCertificate' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\CertificateSerialNumber::MAP, 'revocationDate' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\Time::MAP, 'crlEntryExtensions' => ['optional' => \true] + \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\Extensions::MAP]];
}