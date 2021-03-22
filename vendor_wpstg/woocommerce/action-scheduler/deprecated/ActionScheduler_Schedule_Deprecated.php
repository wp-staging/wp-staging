<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_Abstract_Schedule
 */
abstract class ActionScheduler_Schedule_Deprecated implements \WPStaging\Vendor\ActionScheduler_Schedule
{
    /**
     * Get the date & time this schedule was created to run, or calculate when it should be run
     * after a given date & time.
     *
     * @param DateTime $after
     *
     * @return DateTime|null
     */
    public function next(\DateTime $after = \WPStaging\Vendor\NULL)
    {
        if (empty($after)) {
            $return_value = $this->get_date();
            $replacement_method = 'get_date()';
        } else {
            $return_value = $this->get_next($after);
            $replacement_method = 'get_next( $after )';
        }
        \WPStaging\Vendor\_deprecated_function(__METHOD__, '3.0.0', __CLASS__ . '::' . $replacement_method);
        return $return_value;
    }
}
