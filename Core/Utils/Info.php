<?php

namespace WPStaging\Core\Utils;

class Info
{



    private static $OS = null;




    private static $canUse = [];




    public function __construct()
    {
        $this->getOS();
    }




    public function getOS()
    {
        if (self::$OS === null) {
            self::$OS = strtoupper(substr(PHP_OS, 0, 3)); 
        }

        return self::$OS;
    }





    public function canUse($functionName)
    {
 
        if (isset(self::$canUse[$functionName])) {
            return self::$canUse[$functionName];
        }

 
        if (!function_exists($functionName)) {
            return self::$canUse[$functionName] = false;
        }

 
        $disabledFunctions = array_map('trim', explode(',', ini_get("disable_functions")));

        return self::$canUse[$functionName] = (!in_array($functionName, $disabledFunctions));
    }
}
