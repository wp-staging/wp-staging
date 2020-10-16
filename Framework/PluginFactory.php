<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Container\Container;

class PluginFactory implements PluginFactoryInterface
{
    /**
     * @param string $pluginClass
     *
     * @return PluginInterface
     * @throws InvalidPluginException
     */
    public static function make($pluginClass)
    {
        $config = self::getConfig();
        $container = new Container($config);

        /** @var PluginInterface $plugin */
        $plugin = new $pluginClass($container);

        if (!$plugin instanceof PluginInterface) {
            throw new InvalidPluginException($pluginClass);
        }

        if (!isset($config['components'])) {
            return $plugin;
        }

        foreach ($config['components'] as $id => $options) {
            if (is_string($id) && is_array($options)) {
                $plugin->addComponent($id, $options);
                continue;
            }

            $plugin->addComponent($options);
        }

        return $plugin;
    }

    /**
     * @return array
     */
    private static function getConfig()
    {
        $pluginDir = plugin_dir_path(__FILE__);
        /** @noinspection PhpIncludeInspection */
        $config = require $pluginDir . '../config.php';
        $dirPro = $pluginDir . '../Pro/config.php';
        if (!is_file($dirPro)) {
            return $config?: [];
        }

        /** @noinspection PhpIncludeInspection */
        $configPro = require $dirPro;

        if (!$configPro) {
            return $config?: [];
        }

        $mergedConfig = [];
        foreach ($config as $key => $value) {
            if (isset($configPro[$key]) && is_array($configPro[$key])) {
                $mergedConfig[$key] = array_merge($config[$key], $configPro[$key]);
                continue;
            }
            $mergedConfig[$key] = $value;
        }

        return $mergedConfig;
    }
}
