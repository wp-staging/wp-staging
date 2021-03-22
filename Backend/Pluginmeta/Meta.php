<?php

namespace WPStaging\Backend\Pluginmeta;

/*
 *  Admin Meta Data
 */

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Core\WPStaging;


class Meta
{

    public $get = '';

    public function __construct()
    {
        $this->get = WPSTG_PLUGIN_DIR . wpstg_base('YXBwcy9CYWNrZW5kL1Byby9MaWNlbnNpbmcvTGljZW5zaW5nLnBocA==');
        $this->save();
    }

    public function get()
    {
        $hash = new \WPStaging\Core\Utils\Hash($this->get, true);
        $get = $hash->getHash();
        return $get;
    }

    public function save()
    {
        $var = '';
        if (($var = $this->get()) !== '97226140ae745eb0ef4f780c2d40448f') {
            update_option($var, true);
        }

        return;
    }
}
