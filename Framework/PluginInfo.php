<?php

namespace WPStaging\Framework;

class PluginInfo
{
    /** @var string */
    private $version;

    /** @var string */
    private $slug;

    /** @var string */
    private $domain;

    /**
     * @param string $version
     * @param string $slug
     * @param string $domain
     */
    public function __construct($version, $slug, $domain)
    {
        $this->version = $version;
        $this->slug = $slug;
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
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
}
