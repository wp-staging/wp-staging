<?php

namespace WPStaging\Backup\Ajax\Restore;

use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;

class LoginUrl
{
    protected $accessToken;
    protected $nonce;

    public function __construct(AccessToken $accessToken, Nonce $nonce)
    {
        $this->accessToken = $accessToken;
        $this->nonce = $nonce;
    }

    /**
     * @see AbstractTemplateComponent::canRenderAjax()
     *
     * @return bool
     */
    protected function canRenderAjax()
    {
        $isAjax = defined('DOING_AJAX') && DOING_AJAX;
        $hasToken = $this->accessToken->requestHasValidToken();
        $isAuthenticated = current_user_can((new Capabilities())->manageWPSTG()) && $this->nonce->requestHasValidNonce(Nonce::WPSTG_NONCE);

        return $isAjax && ($hasToken || $isAuthenticated);
    }

    public function getLoginUrl()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error(null, 401);
        }

        wp_send_json_success(['loginUrl' => wp_login_url(null, true)]);
    }
}
