<?php

namespace WPStaging\Vendor\Aws\Signature;

use WPStaging\Vendor\Aws\Credentials\CredentialsInterface;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * Interface used to provide interchangeable strategies for signing requests
 * using the various AWS signature protocols.
 */
interface SignatureInterface
{
    /**
     * Signs the specified request with an AWS signing protocol by using the
     * provided AWS account credentials and adding the required headers to the
     * request.
     *
     * @param RequestInterface     $request     Request to sign
     * @param CredentialsInterface $credentials Signing credentials
     *
     * @return RequestInterface Returns the modified request.
     */
    public function signRequest(\WPStaging\Vendor\Psr\Http\Message\RequestInterface $request, \WPStaging\Vendor\Aws\Credentials\CredentialsInterface $credentials);
    /**
     * Create a pre-signed request.
     *
     * @param RequestInterface              $request     Request to sign
     * @param CredentialsInterface          $credentials Credentials used to sign
     * @param int|string|\DateTimeInterface $expires The time at which the URL should
     *     expire. This can be a Unix timestamp, a PHP DateTime object, or a
     *     string that can be evaluated by strtotime.
     *
     * @return RequestInterface
     */
    public function presign(\WPStaging\Vendor\Psr\Http\Message\RequestInterface $request, \WPStaging\Vendor\Aws\Credentials\CredentialsInterface $credentials, $expires, array $options = []);
}
