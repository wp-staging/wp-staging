<?php

namespace WPStaging\Framework\CloningProcess;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Staging\CloneOptions;




class ExcludedPlugins
{



    const EXCLUDED_PLUGINS_KEY  = 'excluded_plugins';




    private $excludedPlugins;

 
    private $pluginsPath;

 
    private $absPath;







    public function __construct($dirAdapter = null)
    {
        if ($dirAdapter === null) {
            $dirAdapter        = WPStaging::make(Directory::class);
        }

        $this->pluginsPath     = $dirAdapter->getPluginsDirectory();
        $this->absPath         = $dirAdapter->getAbsPath();
        $this->excludedPlugins = [
            'wps-hide-login',
            'wp-super-cache',
            'peters-login-redirect',
            'wp-spamshield',
        ];
    }













    public function getPluginsToExclude()
    {
        return $this->excludedPlugins;
    }






    public function getPluginsToExcludeWithIdentifier()
    {
        return array_map(function ($plugin) {
            return PathIdentifier::IDENTIFIER_PLUGINS . $plugin;
        }, $this->excludedPlugins);
    }






    public function getPluginsToExcludeWithRelativePath()
    {
        $relativePath = str_replace($this->absPath, '/', $this->pluginsPath);
        $relativePath = trailingslashit($relativePath);
        return array_map(function ($plugin) use ($relativePath) {
            return $relativePath . $plugin;
        }, $this->excludedPlugins);
    }






    public function getPluginsToExcludeFullPath()
    {
        $pluginsPath = $this->pluginsPath;
        return array_map(function ($plugin) use ($pluginsPath) {
            return $pluginsPath . $plugin;
        }, $this->excludedPlugins);
    }








    public function getFilteredPluginsToExclude($installedPlugins = [])
    {
 
        if (is_multisite()) {
            $filteredExcludedPlugins = apply_filters(Directory::FILTER_CLONE_MU_EXCLUDED_FOLDERS, $this->getPluginsToExcludeWithRelativePath());
        } else {
            $filteredExcludedPlugins = apply_filters(Directory::FILTER_CLONE_EXCLUDED_FOLDERS, $this->getPluginsToExcludeWithRelativePath());
        }

        if ($installedPlugins === []) {
            $installedPlugins = get_plugins();
            $installedPlugins = array_keys($installedPlugins);
        }

        $relativePath = str_replace($this->absPath, '/', $this->pluginsPath);
        $relativePath = trailingslashit($relativePath);
 
        $filteredExcludedPlugins = array_filter($filteredExcludedPlugins, function ($path) use ($installedPlugins, $relativePath) {
            foreach ($installedPlugins as $plugin) {
                $plugin = $relativePath . explode('/', $plugin)[0];
                if (strpos($path, $plugin) === 0) {
                    return true;
                }
            }

            return false;
        });

 
        $filteredExcludedPlugins = array_values($filteredExcludedPlugins);







        return array_map(function ($path) use ($relativePath) {
            $plugin = str_replace($relativePath, '', $path);
            return explode('/', $plugin)[0];
        }, $filteredExcludedPlugins);
    }






    public function getExcludedPlugins()
    {
        return (new CloneOptions())->get(self::EXCLUDED_PLUGINS_KEY);
    }
}
