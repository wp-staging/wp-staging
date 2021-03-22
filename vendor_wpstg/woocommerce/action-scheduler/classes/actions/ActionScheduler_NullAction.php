<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_NullAction
 */
class ActionScheduler_NullAction extends \WPStaging\Vendor\ActionScheduler_Action
{
    public function __construct($hook = '', array $args = array(), \WPStaging\Vendor\ActionScheduler_Schedule $schedule = \WPStaging\Vendor\NULL)
    {
        $this->set_schedule(new \WPStaging\Vendor\ActionScheduler_NullSchedule());
    }
    public function execute()
    {
        // don't execute
    }
}
