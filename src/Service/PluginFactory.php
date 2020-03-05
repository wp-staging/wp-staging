<?php

namespace WPStaging\Service;

use WPStaging\Service\Container\Container;

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
        /** @noinspection PhpIncludeInspection */
        $config = require plugin_dir_path(__FILE__) . '../config.php';
        $container = new Container($config?: []);

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
}
