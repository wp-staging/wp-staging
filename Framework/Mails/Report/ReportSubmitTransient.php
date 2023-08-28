<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Framework\Interfaces\TransientInterface;
use WPStaging\Framework\Traits\BooleanTransientTrait;

class ReportSubmitTransient implements TransientInterface
{
    use BooleanTransientTrait;

    /**
     * The transient name on which we store/fetch/delete issue report submitted.
     */
    const TRANSIENT_NAME = 'wpstg_issue_report_submitted';

    /**
     * Set expiry time to 300 seconds = 5 mins
     */
    const EXPIRY_TIME_IN_SEC = 300;

    /**
     * @return string
     */
    public function getTransientName()
    {
        return self::TRANSIENT_NAME;
    }

    /**
     * @return int expiry time in seconds
     */
    public function getExpiryTime()
    {
        return self::EXPIRY_TIME_IN_SEC;
    }
}
