<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\TemplateEngine\TemplateEngine;

class HookedTemplate
{

    /** @var Hooks */
    private $hooks;

    /** @var TemplateEngine */
    private $templateEngine;

    public function __construct(Hooks $hooks, TemplateEngine $templateEngine)
    {
        $this->hooks = $hooks;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @return Hooks
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * @return TemplateEngine
     */
    public function getTemplateEngine()
    {
        return $this->templateEngine;
    }
}
