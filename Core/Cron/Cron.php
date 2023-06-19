<?php

/**
 * Cron relevant stuff
 */

namespace WPStaging\Core\Cron;

use WPStaging\Core\WPStaging;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

class Cron
{
    /** @var string */
    const HOURLY          = 'wpstg_hourly';

    /** @var string */
    const SIX_HOURS       = 'wpstg_six_hours';

    /** @var string */
    const TWELVE_HOURS    = 'wpstg_twelve_hours';

    /** @var string */
    const DAILY           = 'wpstg_daily';

    /** @var string */
    const EVERY_TWO_DAYS  = 'wpstg_every_two_days';

    /** @var string */
    const WEEKLY          = 'wpstg_weekly';

    /** @var string */
    const EVERY_TWO_WEEKS = 'wpstg_every_two_weeks';

    /** @var string */
    const MONTHLY         = 'wpstg_montly';

    /** @var string */
    const BASIC_DAILY     = 'wpstg_basic_daily';

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
        if (!WPStaging::isPro()) {
            return $this->addBasicIntervals($schedules);
        }

        // add weekly and monthly intervals
        $schedules['weekly'] = [
            'interval' => 604800,
            'display' => __('Once Weekly', 'wp-staging')
        ];

        $schedules['monthly'] = [
            'interval' => 2635200,
            'display' => __('Once a month', 'wp-staging')
        ];

        $schedules[static::HOURLY] = [
            'interval' => HOUR_IN_SECONDS,
            'display' => __('WP Staging events that happens hourly', 'wp-staging'),
        ];

        $schedules[static::SIX_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 6,
            'display' => __('WP Staging events that happens every six hours', 'wp-staging'),
        ];

        $schedules[static::TWELVE_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 12,
            'display' => __('WP Staging events that happens every twelve hours', 'wp-staging'),
        ];

        $schedules[static::DAILY] = [
            'interval' => DAY_IN_SECONDS,
            'display' => __('WP Staging events that happens daily', 'wp-staging'),
        ];

        $schedules[static::EVERY_TWO_DAYS] = [
            'interval' => DAY_IN_SECONDS * 2,
            'display' => __('WP Staging events that happens every 2 days', 'wp-staging'),
        ];

        $schedules[static::WEEKLY] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('WP Staging events that happens weekly', 'wp-staging'),
        ];

        $schedules[static::EVERY_TWO_WEEKS] = [
            'interval' => WEEK_IN_SECONDS * 2,
            'display' => __('WP Staging events that happens every two weeks', 'wp-staging'),
        ];

        $schedules[static::MONTHLY] = [
            'interval' => MONTH_IN_SECONDS,
            'display' => __('WP Staging events that happens every month', 'wp-staging'),
        ];

        return $schedules;
    }

    public function addBasicIntervals($schedules)
    {
        $schedules[static::BASIC_DAILY] = [
            'interval' => DAY_IN_SECONDS,
            'display' => __('WP Staging events that happens daily', 'wp-staging'),
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
            case static::BASIC_DAILY:
                return __('Daily', 'wp-staging');
        }

        return $cronInterval;
    }

    /**
     * @return array
     */
    public function getProEvents()
    {
        return [
            self::HOURLY,
            self::SIX_HOURS,
            self::TWELVE_HOURS,
            self::DAILY,
            self::EVERY_TWO_DAYS,
            self::WEEKLY,
            self::EVERY_TWO_WEEKS,
            self::MONTHLY
        ];
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
