<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_Schedule
 */
interface ActionScheduler_Schedule
{
    /**
     * @param DateTime $after
     * @return DateTime|null
     */
    public function next(\DateTime $after = \WPStaging\Vendor\NULL);
    /**
     * @return bool
     */
    public function is_recurring();
}
