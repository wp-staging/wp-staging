<?php

namespace WPStaging\Service\Adapter;

class Directory
{
    /** @var string */
    private $pluginSlug;

    /**
     * @param string $pluginSlug
     */
    public function __construct($pluginSlug)
    {
        $this->pluginSlug = $pluginSlug;
    }

    /**
     * @return string
     */
    public function getPluginDirectory()
    {
        return sprintf('%s/%s/', WP_PLUGIN_DIR, $this->pluginSlug);
    }
}
