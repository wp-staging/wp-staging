<?php

namespace WPStaging\Vendor;

/**
 * Registers the library aliases redirecting calls to the `tad_DI52_`, non-namespaced, class format to the namespaced
 * classes.
 */
$aliases = [['WPStaging\\Vendor\\lucatume\\DI52\\Container', 'tad_DI52_Container'], ['WPStaging\\Vendor\\lucatume\\DI52\\ServiceProvider', 'tad_DI52_ServiceProvider']];
foreach ($aliases as list($class, $alias)) {
    if (!\class_exists($alias)) {
        \class_alias($class, $alias);
    }
}
