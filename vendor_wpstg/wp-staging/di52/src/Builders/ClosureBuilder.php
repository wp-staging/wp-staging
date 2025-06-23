<?php

/**
 * Closure-based builder.
 *
 * @package lucatume\DI52
 */
namespace WPStaging\Vendor\lucatume\DI52\Builders;

use Closure;
use WPStaging\Vendor\lucatume\DI52\Container;
/**
 * Class ClosureBuilder
 *
 * @package lucatume\DI52\Builders
 */
class ClosureBuilder implements \WPStaging\Vendor\lucatume\DI52\Builders\BuilderInterface
{
    /**
     * A reference to the resolver currently using the builder.
     *
     * @var Container
     */
    protected $container;
    /**
     * A reference to the closure the builder should run to build.
     *
     * @var Closure
     */
    protected $closure;
    /**
     * ClosureBuilder constructor.
     *
     * @param Container $container A reference to the current DI container instance.
     * @param Closure   $closure   A reference to the closure that should be used to build the implementation.
     */
    public function __construct(\WPStaging\Vendor\lucatume\DI52\Container $container, \Closure $closure)
    {
        $this->container = $container;
        $this->closure = $closure;
    }
    /**
     * Calls the Closure handled by the builder to return the built implementation.
     *
     * @return mixed The built implementation.
     */
    public function build()
    {
        $closure = $this->closure;
        return $closure($this->container);
    }
}
