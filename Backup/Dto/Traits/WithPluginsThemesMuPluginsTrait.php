<?php

namespace WPStaging\Backup\Dto\Traits;

trait WithPluginsThemesMuPluginsTrait
{
    /** @var array Which plugins this backup contains */
    private $plugins = [];

    /** @var array Which themes this backup contains */
    private $themes = [];

    /** @var array Which mu-plugins this backup contains */
    private $muPlugins = [];

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param array $plugins
     */
    public function setPlugins(array $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @return array
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * @param array $themes
     */
    public function setThemes(array $themes)
    {
        $this->themes = $themes;
    }

    /**
     * @return array
     */
    public function getMuPlugins()
    {
        return $this->muPlugins;
    }

    /**
     * @param array $mu_plugins
     */
    public function setMuPlugins(array $muPlugins)
    {
        $this->muPlugins = $muPlugins;
    }
}
