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
    const HOURLY          = 'wpstg_hourly';
    const SIX_HOURS       = 'wpstg_six_hours';
    const TWELVE_HOURS    = 'wpstg_twelve_hours';
    const DAILY           = 'wpstg_daily';
    const EVERY_TWO_DAYS  = 'wpstg_every_two_days';
    const WEEKLY          = 'wpstg_weekly';
    const EVERY_TWO_WEEKS = 'wpstg_every_two_weeks';
    const MONTHLY         = 'wpstg_montly';

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

        $schedules[static::HOURLY] = [
            'interval' => HOUR_IN_SECONDS,
            'display' => __('WP Staging events that happens hourly'),
        ];

        $schedules[static::SIX_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 6,
            'display' => __('WP Staging events that happens every six hours'),
        ];

        $schedules[static::TWELVE_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 12,
            'display' => __('WP Staging events that happens every twelve hours'),
        ];

        $schedules[static::DAILY] = [
            'interval' => DAY_IN_SECONDS,
            'display' => __('WP Staging events that happens daily'),
        ];

        $schedules[static::EVERY_TWO_DAYS] = [
            'interval' => DAY_IN_SECONDS * 2,
            'display' => __('WP Staging events that happens every 2 days'),
        ];

        $schedules[static::WEEKLY] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('WP Staging events that happens weekly'),
        ];

        $schedules[static::EVERY_TWO_WEEKS] = [
            'interval' => WEEK_IN_SECONDS * 2,
            'display' => __('WP Staging events that happens every two weeks'),
        ];

        $schedules[static::MONTHLY] = [
            'interval' => MONTH_IN_SECONDS,
            'display' => __('WP Staging events that happens every month'),
        ];

        return $schedules;
    }

    public static function getCronDisplayName($cronInterval)
    {
        switch ($cronInterval) {
            case static::HOURLY:
                return __('Hourly', 'wp-staging');
            case static::SIX_HOURS:
                return __('Every 6 Hours', 'wp-staging');
            case static::TWELVE_HOURS:
                return __('Every 12 Hours', 'wp-staging');
            case static::DAILY:
                return __('Daily', 'wp-staging');
            case static::EVERY_TWO_DAYS:
                return __('Every 2 Days', 'wp-staging');
            case static::WEEKLY:
                return __('Weekly', 'wp-staging');
            case static::EVERY_TWO_WEEKS:
                return __('Every 2 weeks', 'wp-staging');
            case static::MONTHLY:
                return __('Monthly', 'wp-staging');
        }

        return $cronInterval;
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
