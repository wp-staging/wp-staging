<?php

namespace WPStaging\Framework\Adapter\Dto;

class HookDto
{
    const DEFAULT_PRIORITY = 10;

    /** @var string */
    private $hook;

    /** @var object */
    private $component;

    /** @var string */
    private $callback;

    /** @var int */
    private $priority = 10;

    /** @var int */
    private $acceptedArgs = 0;

    /**
     * @return string|null
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * @param string $hook
     */
    public function setHook($hook)
    {
        $this->hook = $hook;
    }

    /**
     * @return object|null
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * @param object $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * @return string|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param string $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority?: self::DEFAULT_PRIORITY;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getAcceptedArgs()
    {
        return (int)$this->acceptedArgs;
    }

    /**
     * @param $acceptedArgs
     */
    public function setAcceptedArgs($acceptedArgs)
    {
        $this->acceptedArgs = $acceptedArgs;
    }
}
