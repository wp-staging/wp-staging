<?php

namespace WPStaging\Framework\DependencyResolver;








class ResolveBehaviour
{
    private $throwOnCircularReference = true;

    private $throwOnMissingReference = false;

    public static function create()
    {
        return new self();
    }




    public function isThrowOnCircularReference()
    {
        return $this->throwOnCircularReference;
    }






    public function setThrowOnCircularReference($throwOnCircularReference)
    {
        $this->throwOnCircularReference = $throwOnCircularReference;

        return $this;
    }




    public function isThrowOnMissingReference()
    {
        return $this->throwOnMissingReference;
    }






    public function setThrowOnMissingReference($throwOnMissingReference)
    {
        $this->throwOnMissingReference = $throwOnMissingReference;

        return $this;
    }
}
