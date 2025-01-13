<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide helper methods related to Url
 * Useful in standalone tool
 * Trait UrlTrait
 * @package WPStaging\Framework\Traits
 */
trait UrlTrait
{
    /**
     * Return url without scheme
     * @param string $string
     * @return string
     */
    public function getUrlWithoutScheme(string $string): string
    {
        return (string)preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    /**
     * Decodes data encoded with MIME base64
     * @param string $input
     * @return string
     */
    public function base64Decode(string $input): string
    {
        $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        $i      = 0;
        $output = "";
        $input  = preg_replace("[^A-Za-z0-9\+\/\=]", "", $input);
        do {
            $enc1 = strpos($keyStr, substr($input, $i++, 1));
            $enc2 = strpos($keyStr, substr($input, $i++, 1));
            $enc3 = strpos($keyStr, substr($input, $i++, 1));
            $enc4 = strpos($keyStr, substr($input, $i++, 1));
            $chr1 = ($enc1 << 2) | ($enc2 >> 4);
            $chr2 = (($enc2 & 15) << 4) | ($enc3 >> 2);
            $chr3 = (($enc3 & 3) << 6) | $enc4;

            $output = $output . chr((int)$chr1);
            if ($enc3 != 64) {
                $output = $output . chr((int)$chr2);
            }

            if ($enc4 != 64) {
                $output = $output . chr((int)$chr3);
            }
        } while ($i < strlen($input));
        return urldecode($output);
    }
}
