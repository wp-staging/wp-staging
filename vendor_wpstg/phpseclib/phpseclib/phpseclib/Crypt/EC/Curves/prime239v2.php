<?php

/**
 * prime239v2
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */
namespace WPStaging\Vendor\phpseclib3\Crypt\EC\Curves;

use WPStaging\Vendor\phpseclib3\Crypt\EC\BaseCurves\Prime;
use WPStaging\Vendor\phpseclib3\Math\BigInteger;
class prime239v2 extends \WPStaging\Vendor\phpseclib3\Crypt\EC\BaseCurves\Prime
{
    public function __construct()
    {
        $this->setModulo(new \WPStaging\Vendor\phpseclib3\Math\BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF7FFFFFFFFFFF8000000000007FFFFFFFFFFF', 16));
        $this->setCoefficients(new \WPStaging\Vendor\phpseclib3\Math\BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF7FFFFFFFFFFF8000000000007FFFFFFFFFFC', 16), new \WPStaging\Vendor\phpseclib3\Math\BigInteger('617FAB6832576CBBFED50D99F0249C3FEE58B94BA0038C7AE84C8C832F2C', 16));
        $this->setBasePoint(new \WPStaging\Vendor\phpseclib3\Math\BigInteger('38AF09D98727705120C921BB5E9E26296A3CDCF2F35757A0EAFD87B830E7', 16), new \WPStaging\Vendor\phpseclib3\Math\BigInteger('5B0125E4DBEA0EC7206DA0FC01D9B081329FB555DE6EF460237DFF8BE4BA', 16));
        $this->setOrder(new \WPStaging\Vendor\phpseclib3\Math\BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF800000CFA7E8594377D414C03821BC582063', 16));
    }
}
