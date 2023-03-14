<?php

/**
 * Handles and manipulates times.
 *
 * @package WPStaging\Framework\Utils
 */

namespace WPStaging\Framework\Utils;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Class Times
 *
 * @package WPStaging\Framework\Utils
 */
class Times
{

    /**
     * Ports wp core wp_timezone_string() function for compatibility with WordPress < 5.3
     * Retrieves the timezone from site settings as a string.
     *
     * Uses the `timezone_string` option to get a proper timezone if available,
     * otherwise falls back to an offset.
     *
     * @return mixed|string|void PHP timezone string or a ±HH:MM offset.
     * @see wp_timezone_string()
     *
     */
    public function getSiteTimezoneString()
    {
        // Early bail: Let's use WordPress core function if it is available.
        if (function_exists('wp_timezone_string')) {
            return wp_timezone_string();
        }

        $timezone_string = get_option('timezone_string');

        if ($timezone_string) {
            return $timezone_string;
        }

        $offset = (float)get_option('gmt_offset');
        $hours = (int)$offset;
        $minutes = ($offset - $hours);

        $sign = ($offset < 0) ? '-' : '+';
        $abs_hour = abs($hours);
        $abs_mins = abs($minutes * 60);
        $tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

        return $tz_offset;
    }

    /**
     * Retrieves the timezone from site settings as a `DateTimeZone` object.
     * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
     * This is copied from wordpress core wp_timezone() which exists since WordPress 5.3.0 for backward compatibility
     *
     * @return DateTimeZone Timezone object.
     */
    public function getSiteTimezoneObject()
    {
        return new DateTimeZone($this->getSiteTimezoneString());
    }

    /**
     * Produces a set of date objects modeling a time range.
     *
     * This method is similar, in concept, to the PHP Core `range` method
     * where the entity is changed from numeric values to Dates.
     *
     * @param DateTime|DateTimeImmutable|string $start Either a Date object or a valid date definition to start
     *                                                        the range from.
     * @param DateTime|DateTimeImmutable|string $end Either a Date object or a valid date definition to end
     *                                                        the range at, inclusively.
     * @param DateInterval|string $step The step definition, as either an Interval object, or as
     *                                                        a valid DateInterval definition.
     *
     * @return array<DateTimeImmutable> A list of generated Dates between the start and end.
     *
     * @throws \Exception If there's any issue building the start or end date objects from the definitions or building
     *                    the interval object from the definition.
     */
    public function range($start, $end, $step = 'PT1H')
    {
        if ($start instanceof DateTimeImmutable) {
            $startDateObject = $start;
        } else {
            $startDateObject = $start instanceof DateTime ?
                DateTimeImmutable::createFromMutable($start)
                : new DateTimeImmutable($start, $this->getSiteTimezoneObject());
        }
        if ($end instanceof DateTimeImmutable) {
            $endDateObject = $end;
        } else {
            $endDateObject = $end instanceof DateTime ?
                DateTimeImmutable::createFromMutable($end)
                : new DateTimeImmutable($end, $this->getSiteTimezoneObject());
        }
        $stepInterval = $step instanceof DateInterval ?
            $step
            : new DateInterval($step);

        $values = [];
        $current = $startDateObject;
        do {
            $values[] = $current;
            $current = $current->add($stepInterval);
        } while ($current <= $endDateObject);

        return $values;
    }

    /**
     * Alternative to human_readable_duration() as it is not available for WP < 5.1
     * @param string $duration Duration will be in string format (HH:ii:ss) OR (ii:ss),
     *                         with a possible prepended negative sign (-).
     * @return string|false    A human readable duration string, false on failure.
     */
    public function getHumanReadableDuration($duration)
    {
        if ((empty($duration) || !is_string($duration))) {
            return false;
        }

        $duration = trim($duration);

        // Remove prepended negative sign.
        if ('-' === substr($duration, 0, 1)) {
            $duration = substr($duration, 1);
        }

        // Extract duration parts.
        $duration_parts = array_reverse(explode(':', $duration));
        $duration_count = count($duration_parts);

        $hour = null;
        $minute = null;
        $second = null;

        if (3 === $duration_count) {
            // Validate HH:ii:ss duration format.
            if (!((bool)preg_match('/^([0-9]+):([0-5]?[0-9]):([0-5]?[0-9])$/', $duration))) {
                return false;
            }
            // Three parts: hours, minutes & seconds.
            list($second, $minute, $hour) = $duration_parts;
        } elseif (2 === $duration_count) {
            // Validate ii:ss duration format.
            if (!((bool)preg_match('/^([0-5]?[0-9]):([0-5]?[0-9])$/', $duration))) {
                return false;
            }
            // Two parts: minutes & seconds.
            list($second, $minute) = $duration_parts;
        } else {
            return false;
        }

        $human_readable_duration = [];

        // Add the hour part to the string.
        if (is_numeric($hour)) {
            /* translators: %s: Time duration in hour or hours. */
            $human_readable_duration[] = sprintf(_n('%s hour', '%s hours', $hour), (int)$hour);
        }

        // Add the minute part to the string.
        if (is_numeric($minute)) {
            /* translators: %s: Time duration in minute or minutes. */
            $human_readable_duration[] = sprintf(_n('%s minute', '%s minutes', $minute), (int)$minute);
        }

        // Add the second part to the string.
        if (is_numeric($second)) {
            /* translators: %s: Time duration in second or seconds. */
            $human_readable_duration[] = sprintf(_n('%s second', '%s seconds', $second), (int)$second);
        }

        return implode(', ', $human_readable_duration);
    }

    /**
     *
     * Alternative to human_time_diff() as it has been changed in WP 5.3
     * Determines the difference between two timestamps.
     *
     * The difference is returned in a human readable format such as "1 hour",
     * "5 mins", "2 days".
     *
     * @param int $from Unix timestamp from which the difference begins.
     * @param int $to Optional. Unix timestamp to end the time difference. Default becomes time() if not set.
     * @return string   Human readable time difference.
     * @since 5.3.0 Added support for showing a difference in seconds.
     *
     * @since 1.5.0
     */
    public function getHumanTimeDiff($from, $to = 0)
    {
        if (empty($to)) {
            $to = time();
        }

        $diff = (int)abs($to - $from);

        if ($diff < MINUTE_IN_SECONDS) {
            $secs = $diff;
            if ($secs <= 1) {
                $secs = 1;
            }
            /* translators: Time difference between two dates, in seconds. %s: Number of seconds. */
            $since = sprintf(_n('%s second', '%s seconds', $secs), $secs);
        } elseif ($diff < HOUR_IN_SECONDS) {
            $mins = round($diff / MINUTE_IN_SECONDS);
            if ($mins <= 1) {
                $mins = 1;
            }
            /* translators: Time difference between two dates, in minutes (min=minute). %s: Number of minutes. */
            $since = sprintf(_n('%s min', '%s mins', $mins), $mins);
        } elseif ($diff < DAY_IN_SECONDS) {
            $hours = round($diff / HOUR_IN_SECONDS);
            if ($hours <= 1) {
                $hours = 1;
            }
            /* translators: Time difference between two dates, in hours. %s: Number of hours. */
            $since = sprintf(_n('%s hour', '%s hours', $hours), $hours);
        } elseif ($diff < WEEK_IN_SECONDS) {
            $days = round($diff / DAY_IN_SECONDS);
            if ($days <= 1) {
                $days = 1;
            }
            /* translators: Time difference between two dates, in days. %s: Number of days. */
            $since = sprintf(_n('%s day', '%s days', $days), $days);
        } elseif ($diff < MONTH_IN_SECONDS) {
            $weeks = round($diff / WEEK_IN_SECONDS);
            if ($weeks <= 1) {
                $weeks = 1;
            }
            /* translators: Time difference between two dates, in weeks. %s: Number of weeks. */
            $since = sprintf(_n('%s week', '%s weeks', $weeks), $weeks);
        } elseif ($diff < YEAR_IN_SECONDS) {
            $months = round($diff / MONTH_IN_SECONDS);
            if ($months <= 1) {
                $months = 1;
            }
            /* translators: Time difference between two dates, in months. %s: Number of months. */
            $since = sprintf(_n('%s month', '%s months', $months), $months);
        } elseif ($diff >= YEAR_IN_SECONDS) {
            $years = round($diff / YEAR_IN_SECONDS);
            if ($years <= 1) {
                $years = 1;
            }
            /* translators: Time difference between two dates, in years. %s: Number of years. */
            $since = sprintf(_n('%s year', '%s years', $years), $years);
        }

        /**
         * Filters the human readable difference between two timestamps.
         *
         * @param string $since The difference in human readable text.
         * @param int $diff The difference in seconds.
         * @param int $from Unix timestamp from which the difference begins.
         * @param int $to Unix timestamp to end the time difference.
         * @since 4.0.0
         *
         */
        return apply_filters('human_time_diff', $since, $diff, $from, $to);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCurrentTime()
    {
        $timeFormatOption = get_option('time_format');
        return (new DateTime('now', $this->getSiteTimezoneObject()))->format($timeFormatOption);
    }
}
