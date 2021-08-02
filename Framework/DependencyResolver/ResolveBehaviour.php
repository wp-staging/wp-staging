<?php

namespace WPStaging\Framework\DependencyResolver;

/**
 * Class ResolveBehaviour
 *
 * This is a port of https://github.com/anthonykgross/dependency-resolver, adapted to run on our PHP requirement version.
 *
 * @package WPStaging\Framework\DependencyResolver
 */
class ResolveBehaviour
{
    private $throwOnCircularReference = true;

    private $throwOnMissingReference = false;

    public static function create()
    {
        return new self();
    }

    /**
     * @return bool
     */
    public function isThrowOnCircularReference()
    {
        return $this->throwOnCircularReference;
    }

    /**
     * @param $throwOnCircularReference
     *
     * @return $this
     */
    public function setThrowOnCircularReference($throwOnCircularReference)
    {
        $this->throwOnCircularReference = $throwOnCircularReference;

        return $this;
    }

    /**
     * @return bool
     */
    public function isThrowOnMissingReference()
    {
        return $this->throwOnMissingReference;
    }

    /**
     * @param $throwOnMissingReference
     *
     * @return $this
     */
    public function setThrowOnMissingReference($throwOnMissingReference)
    {
        $this->throwOnMissingReference = $throwOnMissingReference;

        return $this;
    }
}
