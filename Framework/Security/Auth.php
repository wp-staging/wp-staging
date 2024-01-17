<?php

namespace WPStaging\Framework\Security;

/**
 * Class Auth
 *
 * This class provide helping methods for authentication different kinds of request
 * like ajax, non-ajax etc
 *
 * @package WPStaging\Framework\Security
 */
class Auth
{
    /**
     * @var Capabilities
     */
    protected $capabilities;

    /**
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * @var Nonce
     */
    protected $nonce;

    /**
     * @param Capabilities $capabilities
     * @param AccessToken $accessToken
     * @param Nonce $nonce
     */
    public function __construct(Capabilities $capabilities, AccessToken $accessToken, Nonce $nonce)
    {
        $this->capabilities = $capabilities;
        $this->accessToken = $accessToken;
        $this->nonce = $nonce;
    }

    /**
     * Validate (ajax) request with wpstg nonce or access token.
     * Criteria to be a valid request should satisfy any point below:
     *
     * A. User must be logged in and must have capability to manage WP Staging.
     *    WP Staging Nonce must be valid
     * or
     * B. WP Staging Access Token must be valid
     *
     *
     * @param string $nonce
     * @param string $capability
     * @todo In case we need it later, we can consider updating $capability to be an array of capabilities
     *
     * @return bool
     */
    public function isAuthenticatedRequest(string $nonce = '', string $capability = ''): bool
    {
        if (empty($nonce)) {
            $nonce = Nonce::WPSTG_NONCE;
        }

        if (empty($capability)) {
            $capability = $this->capabilities->manageWPSTG();
        }

        if (
            $this->nonce->requestHasValidNonce($nonce) &&
            current_user_can($capability)
        ) {
            return true;
        }

        return $this->accessToken->requestHasValidToken();
    }
}
