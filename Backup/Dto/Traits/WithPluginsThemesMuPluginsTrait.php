<?php

namespace WPStaging\Backup\Dto\Traits;

trait WithPluginsThemesMuPluginsTrait
{
 
    private $plugins = [];

 
    private $themes = [];

 
    private $muPlugins = [];




    public function getPlugins()
    {
        return $this->plugins;
    }




    public function setPlugins(array $plugins)
    {
        $this->plugins = $plugins;
    }




    public function getThemes()
    {
        return $this->themes;
    }




    public function setThemes(array $themes)
    {
        $this->themes = $themes;
    }




    public function getMuPlugins()
    {
        return $this->muPlugins;
    }




    public function setMuPlugins(array $muPlugins)
    {
        $this->muPlugins = $muPlugins;
    }
}
