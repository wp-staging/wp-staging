<?php
namespace WPStaging\Backend\Modules;

use WPStaging\DI\InjectionAware;

/**
 * Class Optimizer
 * @package WPStaging\Backend\Modules;
 */
class Optimizer extends InjectionAware
{

    /**
     * @var array
     */
    private $wpFilter;

    private $settings;

    /**
     * Optimizer constructor.
     */
    public function initialize()
    {
        $this->wpFilter = $this->getDI()->get("wpFilter");
    }

    /**
     * Remove TGM Plugin Activation "force_activation" from admin_init action hook if it exists
     * @desc Stop excluded plugins being deactivated after a migration when a theme uses TGMPA
     * to require a plugin to be always active
     * @return bool
     */
    public function compatibility()
    {
        if (!$this->shouldRemove() || !$this->wpFilter)
        {
            return false;
        }

        foreach ($this->wpFilter["admin_init"] as $priority => $functions)
        {
            foreach ($functions as $key => $function)
            {
                if (false !== strpos($key, "force_activation"))
                {
                    unset($this->wpFilter["admin_init"][$priority][$key]);
                    break;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function shouldRemove()
    {
        return (
            (isset($_GET["page"]) && "wpstg_clone" === $_GET["page"]) ||
            $this->isCompatibilityModeRequest()
        );
    }

    /**
     * Checks if the current request should be processed by compatibility mode
     * @return bool
     */
    public function isCompatibilityModeRequest()
    {
        return (
            defined("DOING_AJAX") &&
            DOING_AJAX &&
            isset($_POST["action"]) &&
            false !== strpos($_POST["action"], "wpstg")
        );
    }

    /**
     * Returns an array of plugin slugs to be blacklisted.
     * @return array
     */
    public function blackListedPlugins()
    {
        $blackListedPlugins = $this->getDI()->get("settings")->getBlackListedPlugins();

        return (empty($blackListedPlugins)) ? $blackListedPlugins : array_flip($blackListedPlugins);
    }

    /**
     * @param array $plugins
     * @return array|bool
     */
    public function getBlackListedPluginsForExcludes($plugins = array())
    {
        if (!is_array($plugins) || empty($plugins) || !$this->isCompatibilityModeRequest())
        {
            return false;
        }

        $blackListedPlugins = $this->blackListedPlugins();

        if (empty($blackListedPlugins))
        {
            return false;
        }

        return $blackListedPlugins;
    }

    /**
     * Remove blacklisted plugins
     * @param array $plugins
     * @return array
     */
    public function excludedPlugins($plugins = array())
    {
        $blackListedPlugins = $this->getBlackListedPluginsForExcludes($plugins);

        if (false === $blackListedPlugins)
        {
            return $plugins;
        }

        foreach ($plugins as $key => $plugin)
        {
            if (false === strpos($plugin, "wp-staging") || isset($blackListedPlugins[$plugin]))
            {
                unset($plugins[$key]);
            }
        }

        return $plugins;
    }
}