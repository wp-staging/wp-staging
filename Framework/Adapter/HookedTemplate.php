<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\TemplateEngine\TemplateEngine;

/**
 * Class HookedTemplate
 *
 * @todo Remove this since Hooks is no more.
 *
 * @package WPStaging\Framework\Adapter
 */
class HookedTemplate
{

    /** @var TemplateEngine */
    private $templateEngine;

    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @return TemplateEngine
     */
    public function getTemplateEngine()
    {
        return $this->templateEngine;
    }
}
