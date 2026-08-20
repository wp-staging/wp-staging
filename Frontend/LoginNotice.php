<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\Interfaces\TransientInterface;
use WPStaging\Framework\Traits\BooleanTransientTrait;









class LoginNotice implements TransientInterface
{
    use BooleanTransientTrait;




    const NOTICE_TRANSIENT_NAME = 'wpstg_show_login_notice';




    const TIME_IN_SEC = 300;




    public function getTransientName()
    {
        return self::NOTICE_TRANSIENT_NAME;
    }




    public function getExpiryTime()
    {
        return self::TIME_IN_SEC;
    }





    public function isLoginNoticeActive()
    {
        $expiredOrNot = $this->getTransient();
        $this->deleteTransient();

        return $expiredOrNot;
    }
}
