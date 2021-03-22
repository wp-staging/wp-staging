<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\Interfaces\TransientInterface;
use WPStaging\Framework\Traits\BooleanTransientTrait;

/**
 * Class LoginNotice
 *
 * This class is used to show notice on staging login form one time or with a maximum of 300sec
 *
 * @package WPStaging\Frontend
 * @todo can be DRY using new Transient interface and trait
 */
class LoginNotice implements TransientInterface
{
    use BooleanTransientTrait;

    /**
     * The transient option_name that is used for showing notice on login form on staging site.
     */
    const NOTICE_TRANSIENT_NAME = 'wpstg_show_login_notice';

    /**
     * After this time transient will be expired
     */
    const TIME_IN_SEC = 300;

    /**
     * @return string
     */
    public function getTransientName()
    {
        return self::NOTICE_TRANSIENT_NAME;
    }

    /**
     * @return int expiry time in seconds
     */
    public function getExpiryTime()
    {
        return self::TIME_IN_SEC;
    }

    /**
     * Check if transient is expired or not and return its value.
     * @return bool
     */
    public function isLoginNoticeActive()
    {
        $expiredOrNot = $this->getTransient();
        $this->deleteTransient();

        return $expiredOrNot;
    }
}
