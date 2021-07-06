<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints & return types;

namespace WPStaging\Framework\TemplateEngine;

use DateTime;
use WPStaging\Framework\Adapter\DateTimeAdapter;

class TemplateEngine implements TemplateEngineInterface
{
    /** @var string Absolute path to the views directory.  */
    protected $views;

    /**
     * @param string $path
     * @param array  $params
     *
     * @return string
     */
    public function render($path, array $params = [])
    {
        if (!isset($this->views)) {
            $this->views = WPSTG_PLUGIN_DIR . 'Backend/views/';
        }

        $fullPath = WPSTG_PLUGIN_DIR . $path;
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
    protected function getDateTimeFormat()
    {
        return (new DateTimeAdapter())->getDateTimeFormat();
    }

    /**
     * @param DateTime|null $dateTime
     *
     * @return string
     */
    protected function transformToWpFormat(DateTime $dateTime = null)
    {
        if (!$dateTime) {
            return '';
        }

        return (new DateTimeAdapter())->transformToWpFormat($dateTime);
    }
}
