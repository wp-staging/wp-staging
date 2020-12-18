<?php
/**
 * @var $this \WPStaging\Bootstrap\V1\WpstgBootstrap The context where this file is included.
 */

// Register the autoloader for the plugin source code, and for the prefixed vendors.
$class_map = array_merge(
    require_once $this->rootPath . '/vendor_wpstg/autoload/src.php',
    require_once $this->rootPath . '/vendor_wpstg/autoload/vendor.php'
);

spl_autoload_register(
    function ($class) use ($class_map) {
        if (isset($class_map[$class])) {
            require_once $class_map[$class];

            return true;
        }

        return null;
    },
    true,
    true
);
