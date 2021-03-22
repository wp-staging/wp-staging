<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_NullLogEntry
 */
class ActionScheduler_NullLogEntry extends \WPStaging\Vendor\ActionScheduler_LogEntry
{
    public function __construct($action_id = '', $message = '')
    {
        // nothing to see here
    }
}
