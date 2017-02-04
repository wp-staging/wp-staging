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
    private $actions = [];

    private $filters = [];

    public function addAction($hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    public function addFilter($hook, $component, $callback)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback);
    }

    private function add($hooks, $hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $hooks[] = [
            "hook"          => $hook,
            "component"     => $component,
            "callback"      => $callback,
            "priority"      => $priority,
            "acceptedArgs"  => $acceptedArgs
        ];

        return $hooks;
    }

    public function run($emptyAfterRun = true)
    {
        // Filters
        foreach ($this->filters as $key => $hook)
        {
            add_filter(
                $hook["hook"], [$hook["component"]], $hook["callback"], $hook["priority"], $hook["acceptedArgs"]
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
                $hook["hook"], [$hook["component"], $hook["callback"]], $hook["priority"], $hook["acceptedArgs"]
            );

            if (true === $emptyAfterRun)
            {
                unset($this->actions[$key]);
            }
        }
    }
}