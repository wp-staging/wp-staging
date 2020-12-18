<?php

namespace WPStaging\Framework\DI;

class Container extends \WPStaging\Vendor\tad_DI52_Container
{
    /**
     * @var string The PSR-4 namespace prefix we use to isolate third-party dependencies.
     */
    protected $prefix = 'WPStaging\\Vendor\\';

    /**
     * @deprecated Currently, all usages of _get in the codebase
     *              are Service Locators, not Dependency Injection.
     *              They need to be refactored in the future.
     *
     * @param $offset
     *
     * @return mixed|null
     */
    public function _get($offset)
    {
        try {
            return $this->offsetGet($offset);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log($e->getMessage());
            }

            return null;
        }
    }


    /**
     * Overloads bind definition binding the prefix as well so that the DI container works locally.
     *
     * @param string     $classOrInterface
     * @param null       $implementation
     * @param array|null $afterBuildMethods
     */
    public function bind($classOrInterface, $implementation = null, array $afterBuildMethods = null)
    {
        if (defined('WPSTG_DEV') && WPSTG_DEV) {
            parent::bind(str_replace($this->prefix, '', $classOrInterface), $implementation, $afterBuildMethods);
        }
        parent::bind($classOrInterface, $implementation, $afterBuildMethods);
    }

    /**
     * Overloads singleton definition binding the prefix as well so that the DI container works locally.
     *
     * @param string     $classOrInterface
     * @param null       $implementation
     * @param array|null $afterBuildMethods
     */
    public function singleton($classOrInterface, $implementation = null, array $afterBuildMethods = null)
    {
        if (defined('WPSTG_DEV') && WPSTG_DEV) {
            parent::singleton(str_replace($this->prefix, '', $classOrInterface), $implementation, $afterBuildMethods);
        }
        parent::singleton($classOrInterface, $implementation, $afterBuildMethods);
    }
}
