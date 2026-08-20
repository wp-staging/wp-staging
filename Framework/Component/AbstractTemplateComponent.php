<?php

 
 

namespace WPStaging\Framework\Component;

use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

abstract class AbstractTemplateComponent
{
 
    protected $templateEngine;

    private $accessToken;
    private $nonce;
    private $wpAdapter;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;

 
        $this->accessToken = new AccessToken();
        $this->nonce       = new Nonce();
        $this->wpAdapter   = new WpAdapter();
    }







    public function renderTemplate($path, array $params = [])
    {
        return $this->templateEngine->render($path, $params);
    }




    protected function canRenderAjax()
    {
        $isAjax          = $this->wpAdapter->doingAjax();
        $hasToken        = $this->accessToken->requestHasValidToken();
        $isAuthenticated = current_user_can((new Capabilities())->manageWPSTG()) && $this->nonce->requestHasValidNonce(Nonce::WPSTG_NONCE);

        return $isAjax && ($hasToken || $isAuthenticated);
    }
}
