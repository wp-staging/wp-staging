<?php

namespace WPStaging;

use WPStaging\Service\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    /**
     * TODO; remove the demonstration
     * @noinspection PhpUnused
     */
    public function onActivation()
    {
    }

    /**
     * TODO; remove the demonstration
     * @noinspection PhpUnused
     */
    public function onDeactivate()
    {
    }

    /**
     * TODO; remove the demonstration
     * @noinspection PhpUnused
     * This needs to be static due to how register_uninstall_hook() works
     */
    public static function onUninstall()
    {
    }
}
