<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Framework\Interfaces\TransientInterface;
use WPStaging\Framework\Traits\BooleanTransientTrait;

class ReportSubmitTransient implements TransientInterface
{
    use BooleanTransientTrait;




    const TRANSIENT_NAME = 'wpstg_issue_report_submitted';




    const EXPIRY_TIME_IN_SEC = 300;




    public function getTransientName()
    {
        return self::TRANSIENT_NAME;
    }




    public function getExpiryTime()
    {
        return self::EXPIRY_TIME_IN_SEC;
    }
}
