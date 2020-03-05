<?php

namespace WPStaging\Service\Component;

use WPStaging\Service\Container\Container;

abstract class AbstractContainerAware
{
    /** @var Container  */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     *
     * @return null|object
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @return string|null
     */
    protected function getSlug()
    {
        return $this->container->getParameter('slug');
    }
}
