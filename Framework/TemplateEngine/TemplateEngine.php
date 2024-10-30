<?php

namespace WPStaging\Framework\TemplateEngine;

use DateTime;
use WPStaging\Framework\Adapter\DateTimeAdapter;

class TemplateEngine implements TemplateEngineInterface
{
    /**
     * Hook that is used to inject pro templates in UI. So they can be used by our JS
     * @var string
     */
    const HOOK_RENDER_PRO_TEMPLATES = 'wpstg.template.render_pro_templates';

    /** @var string Absolute path to the views directory.  */
    protected $views;

    /**
     * @param string $path
     * @param array  $params
     *
     * @return string
     */
    public function render(string $path, array $params = []): string
    {
        if (!isset($this->views)) {
            $this->views = WPSTG_VIEWS_DIR;
        }

        $fullPath = WPSTG_VIEWS_DIR . $path;
        if (!file_exists($fullPath)) {
            throw new TemplateEngineException('Template not found: ' . $fullPath);
        }

        extract($params, EXTR_SKIP);
        ob_start();

        /** @noinspection PhpIncludeInspection */
        require $fullPath;
        $result = ob_get_clean();

        return (string)$result;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function getDateTimeFormat(): string
    {
        return (new DateTimeAdapter())->getDateTimeFormat();
    }

    /**
     * @param DateTime|null $dateTime
     *
     * @return string
     */
    protected function transformToWpFormat($dateTime = null): string
    {
        if (!$dateTime) {
            return '';
        }

        return (new DateTimeAdapter())->transformToWpFormat($dateTime);
    }
}
