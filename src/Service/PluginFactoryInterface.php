<?php

namespace WPStaging\Service;

interface PluginFactoryInterface
{
    /**
     * @param string $pluginClass
     *
     * @return AbstractPlugin
     */
    public static function make($pluginClass);
}
