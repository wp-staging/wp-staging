<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints && return types

namespace WPStaging\Service\Component;

use WPStaging\Service\Adapter\Hooks;
use WPStaging\Service\TemplateEngine\TemplateEngine;

abstract class AbstractTemplateComponent extends AbstractComponent implements RenderableComponentInterface
{
    use AjaxTrait;

    /** @var TemplateEngine */
    protected $templateEngine;

    public function __construct(Hooks $hooks, TemplateEngine $templateEngine)
    {
        parent::__construct($hooks);
        $this->templateEngine = $templateEngine;
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
