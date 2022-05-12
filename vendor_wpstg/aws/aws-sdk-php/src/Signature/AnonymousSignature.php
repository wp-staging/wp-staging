<?php

namespace WPStaging\Vendor\Aws\Signature;

use WPStaging\Vendor\Aws\Credentials\CredentialsInterface;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * Provides anonymous client access (does not sign requests).
 */
class AnonymousSignature implements \WPStaging\Vendor\Aws\Signature\SignatureInterface
{
    /**
     * /** {@inheritdoc}
     */
    public function signRequest(\WPStaging\Vendor\Psr\Http\Message\RequestInterface $request, \WPStaging\Vendor\Aws\Credentials\CredentialsInterface $credentials)
    {
        return $request;
    }
    /**
     * /** {@inheritdoc}
     */
    public function presign(\WPStaging\Vendor\Psr\Http\Message\RequestInterface $request, \WPStaging\Vendor\Aws\Credentials\CredentialsInterface $credentials, $expires, array $options = [])
    {
        return $request;
    }
}
