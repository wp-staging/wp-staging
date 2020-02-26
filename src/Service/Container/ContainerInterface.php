<?php

namespace WPStaging\Service\Container;

interface ContainerInterface
{
    /**
     * @param string $id
     *
     * @return object|null
     */
    public function get($id);

    /**
     * @param string $id
     * @param null|string|array|object|int|float|bool $value
     *
     * @return void
     */
    public function set($id, $value = null);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id);
}
