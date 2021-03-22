<?php

namespace WPStaging\Core\Utils;

class Info
{
    /**
     * @var null|string
     */
    private static $OS = null;

    /**
     * @var array
     */
    private static $canUse = [];

    /**
     * Info constructor.
     */
    public function __construct()
    {
        $this->getOS();
    }

    /**
     * @return string
     */
    public function getOS()
    {
        if (self::$OS === null) {
            self::$OS = strtoupper(substr(PHP_OS, 0, 3)); // WIN, LIN..
        }

        return self::$OS;
    }

    /**
     * @param string $functionName
     * @return bool
     */
    public function canUse($functionName)
    {
        // Set
        if (isset(self::$canUse[$functionName])) {
            return self::$canUse[$functionName];
        }

        // Function doesn't exist
        if (!function_exists($functionName)) {
            return self::$canUse[$functionName] = false;
        }

        // Check if it is disabled from INI
        $disabledFunctions = explode(',', ini_get("disable_functions"));

        return self::$canUse[$functionName] = (!in_array($functionName, $disabledFunctions));
    }
}
