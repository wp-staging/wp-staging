<?php
namespace WPStaging\DI;

use WPStaging\WPStaging;

/**
 * Class InjectionAware
 * @package WPStaging\DI
 */
abstract class InjectionAware
{

    /**
     * @var WPStaging
     */
    protected $di;

    /**
     * InjectionAware constructor.
     * @param $di
     */
    public function __construct($di)
    {
        $this->di = $di;

        if (method_exists($this, "initialize"))
        {
            $this->initialize();
        }
    }

    /**
     * @return WPStaging
     */
    public function getDI()
    {
        return $this->di;
    }
}