<?php

/**
 * CertificateList
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
 * CertificateList
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class CertificateList
{
    const MAP = ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_SEQUENCE, 'children' => ['tbsCertList' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\TBSCertList::MAP, 'signatureAlgorithm' => \WPStaging\Vendor\phpseclib3\File\ASN1\Maps\AlgorithmIdentifier::MAP, 'signature' => ['type' => \WPStaging\Vendor\phpseclib3\File\ASN1::TYPE_BIT_STRING]]];
}
