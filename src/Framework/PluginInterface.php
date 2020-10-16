<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Container\Container;

interface PluginInterface
{
    public function setContainer(Container $container);

    public function init();

    /**
     * @param string $id
     * @param array $options
     */
    public function addComponent($id, array $options = []);

    public function removeComponent($id);

    /**
     * @return string|null
     */
    public function getSlug();

    /**
     * @return Container
     */
    public function getContainer();
}
