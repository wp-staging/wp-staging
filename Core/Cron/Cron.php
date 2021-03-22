<?php

/**
 * Cron relevant stuff
 */

namespace WPStaging\Core\Cron;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

class Cron
{

    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'addIntervals']);
    }

    /**
     * Add new intervals for wp cron jobs
     * @param array $schedules
     * @return array
     */
    public function addIntervals($schedules)
    {
        // add weekly and monthly intervals
        $schedules['weekly'] = [
            'interval' => 604800,
            'display' => __('Once Weekly')
        ];

        $schedules['monthly'] = [
            'interval' => 2635200,
            'display' => __('Once a month')
        ];

        return $schedules;
    }

    /**
     * @return bool
     */
    public function scheduleEvent()
    {
        if (!wp_next_scheduled('wpstg_weekly_event')) {
            wp_schedule_event(time(), 'weekly', 'wpstg_weekly_event');
        }
        if (!wp_next_scheduled('wpstg_daily_event')) {
            wp_schedule_event(time(), 'daily', 'wpstg_daily_event');
        }

        return true;
    }
}
