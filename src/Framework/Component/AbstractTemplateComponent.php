<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints && return types

namespace WPStaging\Framework\Component;

use WPStaging\Framework\Adapter\HookedTemplate;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

abstract class AbstractTemplateComponent extends AbstractComponent implements RenderableComponentInterface
{
    use AjaxTrait;

    /** @var TemplateEngine */
    protected $templateEngine;

    public function __construct(HookedTemplate $hookedTemplate)
    {
        parent::__construct($hookedTemplate->getHooks());
        $this->templateEngine = $hookedTemplate->getTemplateEngine();
    }

    /**
     * @param string $path
     * @param array $params
     *
     * @return string
     */
    public function renderTemplate($path, array $params = [])
    {
        return $this->templateEngine->render($path, $params);
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->templateEngine->getSlug();
    }
}
