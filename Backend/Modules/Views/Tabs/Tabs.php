<?php

namespace WPStaging\Backend\Modules\Views\Tabs;

/**
 * Class Tabs
 * @package WPStaging\Backend\Modules\Views\Tabs
 */
class Tabs
{

    /**
     * @var array
     */
    private static $tabs;

    /**
     * Settings constructor.
     * @param array $tabs
     */
    public function __construct($tabs)
    {
        if (is_array($tabs)) {
            self::$tabs = $tabs;
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
