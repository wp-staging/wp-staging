<?php
namespace WPStaging\Utils;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class Loader
 * @package WPStaging\Utils
 */
final class Loader
{
    private $actions = array();

    private $filters = array();

    public function addAction($hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    public function addFilter($hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    private function add($hooks, $hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $hooks[] = array(
            "hook"          => $hook,
            "component"     => $component,
            "callback"      => $callback,
            "priority"      => $priority,
            "acceptedArgs"  => $acceptedArgs
        );

        return $hooks;
    }

    public function run($emptyAfterRun = true)
    {
        // Filters
        foreach ($this->filters as $key => $hook)
        {
            add_filter(
                $hook["hook"], array($hook["component"], $hook["callback"]), $hook["priority"], $hook["acceptedArgs"]
            );

            if (true === $emptyAfterRun)
            {
                unset($this->filters[$key]);
            }
        }

        // Actions
        foreach ($this->actions as $key => $hook)
        {
            add_action(
                $hook["hook"], array($hook["component"], $hook["callback"]), $hook["priority"], $hook["acceptedArgs"]
            );

            if (true === $emptyAfterRun)
            {
                unset($this->actions[$key]);
            }
        }
    }
}