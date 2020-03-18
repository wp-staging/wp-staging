<?php
// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints & return types;

namespace WPStaging\Service\TemplateEngine;

use DateTime;
use WPStaging\Service\Adapter\DateTimeAdapter;
use WPStaging\Service\Adapter\Directory;

class TemplateEngine implements TemplateEngineInterface
{

    /** @var string */
    private $slug;

    /** @var string */
    private $path;

    /** @var string */
    private $url;

    /** @var string */
    private $domain;

    /**
     * TemplateEngine constructor.
     *
     * @param Directory $directory
     * @param string $slug
     * @param string $domain
     */
    public function __construct(Directory $directory, $slug, $domain)
    {
        $this->slug = $slug;
        $this->domain = $domain;
        $this->path = $directory->getPluginDirectory() . 'template/';
        $this->url = plugin_dir_url($directory->getPluginDirectory() . $slug . '.php') . 'public/';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @noinspection PhpUnused
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @noinspection PhpUnused
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param string $path
     * @param array $params
     *
     * @return string
     */
    public function render($path, array $params = [])
    {
        $fullPath = $this->path .  $path;
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
        return (new DateTimeAdapter)->getDateTimeFormat();
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
        return (new DateTimeAdapter)->transformToWpFormat($dateTime);
    }
}
