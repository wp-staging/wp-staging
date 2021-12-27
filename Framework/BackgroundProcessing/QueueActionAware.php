<?php

/**
 * Provides methods to make an object aware of its involvement in the Queue processing.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

/**
 * Trait QueueActionAware.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
trait QueueActionAware
{
    /**
     * A reference to the Action object that is currently being processed in the context
     * of the Queue processing.
     *
     * @var Action|null
     */
    private $queueCurrentAction;

    /**
     * Sets, or unsets, the reference to the Action object that is currently being processed in the
     * context of the Queue processing.
     *
     * @param Action|null $action The reference to the Action object that is currently being processed
     *                            in the context of the Queue processing.
     */
    public function setCurrentAction(Action $action = null)
    {
        $this->queueCurrentAction = $action;
    }

    /**
     * Returns the reference to the current Action that is being processed.
     *
     * @return Action|null Either a reference to the Action that is currently being processed, or `null`
     *                     if no Action is being processed, or the reference was not set.
     *
     * @see QueueActionAware::setCurrentAction() for the method that will set and unset the reference.
     */
    public function getCurrentAction()
    {
        return $this->queueCurrentAction;
    }
}
