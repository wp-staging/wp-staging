<?php

 
 

namespace WPStaging\Framework\Component;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\TemplateEngine\TemplateEngine;




abstract class AbstractTemplateComponent
{
 
    protected $templateEngine;

    private $auth;
    private $wpAdapter;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;

 
        $this->auth      = WPStaging::make(Auth::class);
        $this->wpAdapter = new WpAdapter();
    }







    public function renderTemplate($path, array $params = [])
    {
        return $this->templateEngine->render($path, $params);
    }




    protected function canRenderAjax()
    {
        return $this->wpAdapter->doingAjax() && $this->auth->isAuthenticatedRequest();
    }
}
