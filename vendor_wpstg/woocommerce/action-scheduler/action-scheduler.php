<?php

namespace WPStaging\Vendor;

/*
 * Plugin Name: Action Scheduler
 * Plugin URI: https://actionscheduler.org
 * Description: A robust scheduling library for use in WordPress plugins.
 * Author: Automattic
 * Author URI: https://automattic.com/
 * Version: 3.1.6
 * License: GPLv3
 *
 * Copyright 2019 Automattic, Inc.  (https://automattic.com/contact/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
if (!\function_exists('WPStaging\\Vendor\\action_scheduler_register_3_dot_1_dot_6')) {
    if (!\class_exists('WPStaging\\Vendor\\ActionScheduler_Versions')) {
        require_once 'classes/ActionScheduler_Versions.php';
        \WPStaging\Vendor\add_action('plugins_loaded', array('ActionScheduler_Versions', 'initialize_latest_version'), 1, 0);
    }
    \WPStaging\Vendor\add_action('plugins_loaded', 'action_scheduler_register_3_dot_1_dot_6', 0, 0);
    function action_scheduler_register_3_dot_1_dot_6()
    {
        $versions = \WPStaging\Vendor\ActionScheduler_Versions::instance();
        $versions->register('3.1.6', 'action_scheduler_initialize_3_dot_1_dot_6');
    }
    function action_scheduler_initialize_3_dot_1_dot_6()
    {
        require_once 'classes/abstracts/ActionScheduler.php';
        \WPStaging\Vendor\ActionScheduler::init(__FILE__);
    }
    // Support usage in themes - load this version if no plugin has loaded a version yet.
    if (\WPStaging\Vendor\did_action('plugins_loaded') && !\class_exists('WPStaging\\Vendor\\ActionScheduler')) {
        \WPStaging\Vendor\action_scheduler_initialize_3_dot_1_dot_6();
        \WPStaging\Vendor\do_action('action_scheduler_pre_theme_init');
        \WPStaging\Vendor\ActionScheduler_Versions::initialize_latest_version();
    }
}
