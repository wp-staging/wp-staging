<?php

namespace WPStaging\Framework\Security;









class Auth
{



    protected $capabilities;




    protected $accessToken;




    protected $nonce;






    public function __construct(Capabilities $capabilities, AccessToken $accessToken, Nonce $nonce)
    {
        $this->capabilities = $capabilities;
        $this->accessToken = $accessToken;
        $this->nonce = $nonce;
    }

















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
