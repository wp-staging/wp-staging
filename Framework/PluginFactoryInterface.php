<?php

namespace WPStaging\Framework;

interface PluginFactoryInterface
{
    /**
     * @param string $pluginClass
     *
     * @return AbstractPlugin
     */
    public static function make($pluginClass);
}
