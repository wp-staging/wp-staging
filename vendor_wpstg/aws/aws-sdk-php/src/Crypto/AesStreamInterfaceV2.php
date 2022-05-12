<?php

namespace WPStaging\Vendor\Aws\Crypto;

use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
interface AesStreamInterfaceV2 extends \WPStaging\Vendor\Psr\Http\Message\StreamInterface
{
    /**
     * Returns an AES recognizable name, such as 'AES/GCM/NoPadding'. V2
     * interface is accessible from a static context.
     *
     * @return string
     */
    public static function getStaticAesName();
    /**
     * Returns an identifier recognizable by `openssl_*` functions, such as
     * `aes-256-cbc` or `aes-128-ctr`.
     *
     * @return string
     */
    public function getOpenSslName();
    /**
     * Returns the IV that should be used to initialize the next block in
     * encrypt or decrypt.
     *
     * @return string
     */
    public function getCurrentIv();
}
