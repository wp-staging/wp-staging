<?php

namespace WPStaging\Backend\Modules\Views\Tabs;





class Tabs
{




    private static $tabs;





    public function __construct($tabs)
    {
        if (is_array($tabs)) {
            self::$tabs = $tabs;
        }
    }






    public function add($id, $value)
    {
        self::$tabs[$id] = $value;
    }





    public function get()
    {
        return self::$tabs;
    }
}
