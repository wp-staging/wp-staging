<?php

/**
 * PublicKeyLoader
 *
 * Returns a PublicKey or PrivateKey object.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2009 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace WPStaging\Vendor\phpseclib3\Crypt;

use WPStaging\Vendor\phpseclib3\Crypt\Common\AsymmetricKey;
use WPStaging\Vendor\phpseclib3\Crypt\Common\PrivateKey;
use WPStaging\Vendor\phpseclib3\Crypt\Common\PublicKey;
use WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException;
use WPStaging\Vendor\phpseclib3\File\X509;
/**
 * PublicKeyLoader
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PublicKeyLoader
{
    /**
     * Loads a public or private key
     *
     * @return AsymmetricKey
     * @param string|array $key
     * @param string $password optional
     */
    public static function load($key, $password = \false)
    {
        try {
            return \WPStaging\Vendor\phpseclib3\Crypt\EC::load($key, $password);
        } catch (\WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException $e) {
        }
        try {
            return \WPStaging\Vendor\phpseclib3\Crypt\RSA::load($key, $password);
        } catch (\WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException $e) {
        }
        try {
            return \WPStaging\Vendor\phpseclib3\Crypt\DSA::load($key, $password);
        } catch (\WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException $e) {
        }
        try {
            $x509 = new \WPStaging\Vendor\phpseclib3\File\X509();
            $x509->loadX509($key);
            $key = $x509->getPublicKey();
            if ($key) {
                return $key;
            }
        } catch (\Exception $e) {
        }
        throw new \WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException('Unable to read key');
    }
    /**
     * Loads a private key
     *
     * @return PrivateKey
     * @param string|array $key
     * @param string $password optional
     */
    public static function loadPrivateKey($key, $password = \false)
    {
        $key = self::load($key, $password);
        if (!$key instanceof \WPStaging\Vendor\phpseclib3\Crypt\Common\PrivateKey) {
            throw new \WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException('The key that was loaded was not a private key');
        }
        return $key;
    }
    /**
     * Loads a public key
     *
     * @return PublicKey
     * @param string|array $key
     */
    public static function loadPublicKey($key)
    {
        $key = self::load($key);
        if (!$key instanceof \WPStaging\Vendor\phpseclib3\Crypt\Common\PublicKey) {
            throw new \WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException('The key that was loaded was not a public key');
        }
        return $key;
    }
    /**
     * Loads parameters
     *
     * @return AsymmetricKey
     * @param string|array $key
     */
    public static function loadParameters($key)
    {
        $key = self::load($key);
        if (!$key instanceof \WPStaging\Vendor\phpseclib3\Crypt\Common\PrivateKey && !$key instanceof \WPStaging\Vendor\phpseclib3\Crypt\Common\PublicKey) {
            throw new \WPStaging\Vendor\phpseclib3\Exception\NoKeyLoadedException('The key that was loaded was not a parameter');
        }
        return $key;
    }
}
