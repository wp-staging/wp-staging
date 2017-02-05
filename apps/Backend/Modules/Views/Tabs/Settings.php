<?php
namespace WPStaging\Backend\Modules\Views\Tabs;

/**
 * Class Settings
 * @package WPStaging\Backend\Modules\Views\Tabs
 */
class Settings
{

    /**
     * @var array
     */
    private static $tabs;

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        if (!self::$tabs)
        {
            self::$tabs = array(
                "general" => __("General", 'wpstg')
            );
        }
    }

    /**
     * Add tab
     * @param string $id
     * @param string $value
     */
    public function add($id, $value)
    {
        self::$tabs[$id] = $value;
    }

    /**
     * Get tabs
     * @return array
     */
    public function get()
    {
        return self::$tabs;
    }
}