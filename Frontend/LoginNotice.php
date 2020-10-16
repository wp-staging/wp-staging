<?php

namespace WPStaging\Frontend;

/**
 * Class LoginNotice
 *
 * This class is used to show notice on staging login form one time or with a maximum of 300sec
 *
 * @package WPStaging\Frontend
 */
class LoginNotice
{
    /**
     * The transient option_name that is used for showing notice on login form on staging site.
     */
    const NOTICE_TRANSIENT_NAME = 'wpstg_show_login_notice';

    /**
     * After this time transient will be expired
     */
    const TIME_IN_SEC = 300;

    /**
     * Set the initial transient to show notice
     */
    public function setTransient()
    {
        set_transient(static::NOTICE_TRANSIENT_NAME, true, static::TIME_IN_SEC);
    }

    /** @return bool */
    public function getTransient()
    {
        return get_transient(static::NOTICE_TRANSIENT_NAME);
    }

    public function deleteTransient()
    {
        delete_transient(static::NOTICE_TRANSIENT_NAME);
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
