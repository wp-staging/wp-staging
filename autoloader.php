<?php

// Register the autoloader for the plugin source code, and for the prefixed vendors.
//
// Defensive guards against missing/corrupted vendor_wpstg files (issue #5074):
// without these, a missing autoload map would make `include_once` return false,
// `array_merge(array, false)` would throw a TypeError on PHP 8+, and the entire
// WordPress site would crash. Bail early instead so bootstrap.php can detect the
// failed autoloader via class_exists() and show an admin notice.
//
// is_readable() (rather than file_exists()) covers the "file present but
// unreadable" case — security scanners chmod'ing vendor files to 000 is one
// of the real-world triggers — and avoids include_once emitting an E_WARNING
// that would leak to the page output when WP_DEBUG_DISPLAY is on.
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

// Validate ALL three maps (including files.php) before registering the
// autoloader. If files.php is corrupted, we must bail before
// spl_autoload_register so bootstrap.php's class_exists() guard sees an
// unregistered autoloader and surfaces the admin notice — otherwise the
// plugin would continue booting in a half-broken state without composer's
// file-based autoloads.
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
