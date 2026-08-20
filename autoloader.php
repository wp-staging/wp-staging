<?php

 
 
 
 
 
 
 
 
 
 
 
 
$wpstgSrcMapFile    = __DIR__ . '/vendor_wpstg/autoload/src.php';
$wpstgVendorMapFile = __DIR__ . '/vendor_wpstg/autoload/vendor.php';
$wpstgFilesMapFile  = __DIR__ . '/vendor_wpstg/autoload/files.php';

if (
    !is_readable($wpstgSrcMapFile)
    || !is_readable($wpstgVendorMapFile)
    || !is_readable($wpstgFilesMapFile)
) {
    return;
}

 
 
 
 
 
 
$wpstgSrcMap         = include_once $wpstgSrcMapFile;
$wpstgVendorMap      = include_once $wpstgVendorMapFile;
$wpstgFilesToInclude = include_once $wpstgFilesMapFile;

if (
    !is_array($wpstgSrcMap)
    || !is_array($wpstgVendorMap)
    || !is_array($wpstgFilesToInclude)
) {
    return;
}

$class_map = array_merge($wpstgSrcMap, $wpstgVendorMap);

spl_autoload_register(
    // @phpstan-ignore-next-line - Autoloader return value preserved for compatibility
    function (string $class) use ($class_map) {
        if (isset($class_map[$class]) && file_exists($class_map[$class])) {
            include_once $class_map[$class];

            return true;
        }
    },
    true,
    true
);

foreach ($wpstgFilesToInclude as $file) {
    if (is_readable($file)) {
        require $file;
    }
}
