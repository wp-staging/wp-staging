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
    const MONTHLY         = 'wpstg_montly'; // @todo: fix the typo in "montly"

    /** @var string */
    const BASIC_DAILY     = 'wpstg_basic_daily';

    /** @var string */
    const ACTION_DAILY_EVENT = 'wpstg_daily_event';

    /** @var string */
    const ACTION_WEEKLY_EVENT = 'wpstg_weekly_event';

    /** @var string */
    const ACTION_CREATE_CRON_BACKUP = 'wpstg_create_cron_backup';

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
            'interval' => WEEK_IN_SECONDS,
            'display'  => __('Once Weekly', 'wp-staging'),
        ];

        $schedules['monthly'] = [
            'interval' => MONTH_IN_SECONDS,
            'display'  => __('Once a month', 'wp-staging'),
        ];

        $schedules[static::HOURLY] = [
            'interval' => HOUR_IN_SECONDS,
            'display'  => __('WP Staging events that happens hourly', 'wp-staging'),
        ];

        $schedules[static::SIX_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 6,
            'display'  => __('WP Staging events that happens every six hours', 'wp-staging'),
        ];

        $schedules[static::TWELVE_HOURS] = [
            'interval' => HOUR_IN_SECONDS * 12,
            'display'  => __('WP Staging events that happens every twelve hours', 'wp-staging'),
        ];

        $schedules[static::DAILY] = [
            'interval' => DAY_IN_SECONDS,
            'display'  => __('WP Staging events that happens daily', 'wp-staging'),
        ];

        $schedules[static::EVERY_TWO_DAYS] = [
            'interval' => DAY_IN_SECONDS * 2,
            'display'  => __('WP Staging events that happens every 2 days', 'wp-staging'),
        ];

        $schedules[static::WEEKLY] = [
            'interval' => WEEK_IN_SECONDS,
            'display'  => __('WP Staging events that happens weekly', 'wp-staging'),
        ];

        // Weekly schedules for specific days (1-7, Monday-Sunday, ISO 8601 standard)
        // Day 1 = Monday, Day 2 = Tuesday, ..., Day 7 = Sunday
        for ($day = 1; $day <= 7; $day++) {
            $dayName = $this->getDayName($day);
            $schedules[static::WEEKLY . '_' . $day] = [
                'interval' => WEEK_IN_SECONDS,
                'display'  => sprintf(
                    __('WP Staging events that happen weekly - %s', 'wp-staging'),
                    $dayName
                ),
            ];
        }

        $schedules[static::EVERY_TWO_WEEKS] = [
            'interval' => WEEK_IN_SECONDS * 2,
            'display'  => __('WP Staging events that happens every two weeks', 'wp-staging'),
        ];

        $schedules[static::MONTHLY] = [
            'interval' => MONTH_IN_SECONDS,
            'display'  => __('WP Staging events that happens every month', 'wp-staging'),
        ];

        return $schedules;
    }

    public function addBasicIntervals($schedules)
    {
        $schedules[static::BASIC_DAILY] = [
            'interval' => DAY_IN_SECONDS,
            'display'  => __('WP Staging events that happens daily', 'wp-staging'),
        ];

        return $schedules;
    }

    /**
     * Get day name from day number (1-7, Monday-Sunday, ISO 8601)
     *
     * @param int $dayNumber Day number (1=Monday, 7=Sunday)
     * @return string Day name
     */
    public static function getDayName(int $dayNumber): string
    {
        $days = [
            1 => __('Monday', 'wp-staging'),
            2 => __('Tuesday', 'wp-staging'),
            3 => __('Wednesday', 'wp-staging'),
            4 => __('Thursday', 'wp-staging'),
            5 => __('Friday', 'wp-staging'),
            6 => __('Saturday', 'wp-staging'),
            7 => __('Sunday', 'wp-staging'),
        ];

        return isset($days[$dayNumber]) ? $days[$dayNumber] : '';
    }

    /**
     * Extract day number from schedule name
     *
     * @param string $cronInterval Schedule name (e.g., 'wpstg_weekly_3')
     * @return int|null Day number (1-7) or null if not a day-specific schedule
     */
    public static function extractDayFromSchedule(string $cronInterval)
    {
        // Check for day-specific schedules (format: schedule_type_day)
        if (preg_match('/_(\d+)$/', $cronInterval, $matches)) {
            $day = (int)$matches[1];
            // Validate it's in the range 1-7
            if ($day >= 1 && $day <= 7) {
                return $day;
            }
        }

        return null;
    }

    /**
     * @param string $cronInterval
     * @return string
     */
    public static function getCronDisplayName($cronInterval)
    {
        // Check for day-specific schedules (e.g., wpstg_weekly_1, wpstg_weekly_2)
        $day = self::extractDayFromSchedule($cronInterval);
        if ($day !== null) {
            // Extract base schedule type
            $baseSchedule = preg_replace('/_\d+$/', '', $cronInterval);

            if ($baseSchedule === static::WEEKLY) {
                return sprintf(
                    __('Weekly - %s', 'wp-staging'),
                    self::getDayName($day)
                );
            }
        }

        // Handle regular schedules
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
                return __('Weekly', 'wp-staging'); // Backward compatibility: plain wpstg_weekly without day
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
            self::MONTHLY,
        ];
    }

    /**
     * @return bool
     */
    public function scheduleEvent()
    {
        if (!wp_next_scheduled(self::ACTION_WEEKLY_EVENT)) {
            wp_schedule_event(time(), 'weekly', self::ACTION_WEEKLY_EVENT);
        }

        if (!wp_next_scheduled(self::ACTION_DAILY_EVENT)) {
            wp_schedule_event(time(), 'daily', self::ACTION_DAILY_EVENT);
        }

        return true;
    }
}
