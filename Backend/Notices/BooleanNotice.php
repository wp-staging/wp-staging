<?php

namespace WPStaging\Backend\Notices;

/**
 * Class BooleanNotice
 *
 * This class is used to reduce the boilerplate code for boolean notices
 *
 * @package WPStaging\Backend\Notices;
 */
abstract class BooleanNotice
{
    /**
     * The name of option on which the visibility of this notice is stored in db
     *
     * @return string
     */
    abstract function getOptionName();

    /**
     * Enable the option in database to show this notice
     */
    public function enable()
    {
        return add_option($this->getOptionName(), true);
    }

    /**
     * Check whether to show this notice or not
     *
     * @return bool
     */
    public function isEnabled()
    {
        return get_option($this->getOptionName(), false);
    }

    /**
     * Delete the option in database to disable showing the notice
     *
     * @return bool
     */
    public function disable()
    {
        return delete_option($this->getOptionName());
    }
}
