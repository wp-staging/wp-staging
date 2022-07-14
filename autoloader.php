<?php

// Register the autoloader for the plugin source code, and for the prefixed vendors.
$class_map = array_merge(
    include_once __DIR__ . '/vendor_wpstg/autoload/src.php',
    include_once __DIR__ . '/vendor_wpstg/autoload/vendor.php'
);

spl_autoload_register(
    function ($class) use ($class_map) {
        if (isset($class_map[$class]) && file_exists($class_map[$class])) {
            include_once $class_map[$class];

            return true;
        }

        return null;
    },
    true,
    true
);

$filesToInclude = include_once __DIR__ . '/vendor_wpstg/autoload/files.php';

foreach ($filesToInclude as $file) {
    if (file_exists($file)) {
        require $file;
    }
}
