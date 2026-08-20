<?php







namespace WPStaging\Framework\Utils;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;






class Times
{












    public function getSiteTimezoneString()
    {
 
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








    public function getSiteTimezoneObject()
    {
        return new DateTimeZone($this->getSiteTimezoneString());
    }



















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







    public function getHumanReadableDuration($duration)
    {
        if ((empty($duration) || !is_string($duration))) {
            return false;
        }

        $duration = trim($duration);

 
        if ('-' === substr($duration, 0, 1)) {
            $duration = substr($duration, 1);
        }

 
        $duration_parts = array_reverse(explode(':', $duration));
        $duration_count = count($duration_parts);

        $hour = null;
        $minute = null;
        $second = null;

        if (3 === $duration_count) {
 
            if (!((bool)preg_match('/^([0-9]+):([0-5]?[0-9]):([0-5]?[0-9])$/', $duration))) {
                return false;
            }

 
            list($second, $minute, $hour) = $duration_parts;
        } elseif (2 === $duration_count) {
 
            if (!((bool)preg_match('/^([0-5]?[0-9]):([0-5]?[0-9])$/', $duration))) {
                return false;
            }

 
            list($second, $minute) = $duration_parts;
        } else {
            return false;
        }

        $human_readable_duration = [];

 
        if (is_numeric($hour)) {
            /* translators: %s: Time duration in hour or hours. */
            $human_readable_duration[] = sprintf(_n('%s hour', '%s hours', (int)$hour, 'wp-staging'), (int)$hour);
        }

 
        if (is_numeric($minute)) {
            /* translators: %s: Time duration in minute or minutes. */
            $human_readable_duration[] = sprintf(_n('%s minute', '%s minutes', (int)$minute, 'wp-staging'), (int)$minute);
        }

 
        if (is_numeric($second)) {
            /* translators: %s: Time duration in second or seconds. */
            $human_readable_duration[] = sprintf(_n('%s second', '%s seconds', (int)$second, 'wp-staging'), (int)$second);
        }

        return implode(', ', $human_readable_duration);
    }
















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
            $since = sprintf(_n('%s second', '%s seconds', $secs, 'wp-staging'), $secs);
        } elseif ($diff < HOUR_IN_SECONDS) {
            $mins = round($diff / MINUTE_IN_SECONDS);
            if ($mins <= 1) {
                $mins = 1;
            }

            /* translators: Time difference between two dates, in minutes (min=minute). %s: Number of minutes. */
            $since = sprintf(_n('%s min', '%s mins', $mins, 'wp-staging'), $mins);
        } elseif ($diff < DAY_IN_SECONDS) {
            $hours = round($diff / HOUR_IN_SECONDS);
            if ($hours <= 1) {
                $hours = 1;
            }

            /* translators: Time difference between two dates, in hours. %s: Number of hours. */
            $since = sprintf(_n('%s hour', '%s hours', $hours, 'wp-staging'), $hours);
        } elseif ($diff < WEEK_IN_SECONDS) {
            $days = round($diff / DAY_IN_SECONDS);
            if ($days <= 1) {
                $days = 1;
            }

            /* translators: Time difference between two dates, in days. %s: Number of days. */
            $since = sprintf(_n('%s day', '%s days', $days, 'wp-staging'), $days);
        } elseif ($diff < MONTH_IN_SECONDS) {
            $weeks = round($diff / WEEK_IN_SECONDS);
            if ($weeks <= 1) {
                $weeks = 1;
            }

            /* translators: Time difference between two dates, in weeks. %s: Number of weeks. */
            $since = sprintf(_n('%s week', '%s weeks', $weeks, 'wp-staging'), $weeks);
        } elseif ($diff < YEAR_IN_SECONDS) {
            $months = round($diff / MONTH_IN_SECONDS);
            if ($months <= 1) {
                $months = 1;
            }

            /* translators: Time difference between two dates, in months. %s: Number of months. */
            $since = sprintf(_n('%s month', '%s months', $months, 'wp-staging'), $months);
        } elseif ($diff >= YEAR_IN_SECONDS) {
            $years = round($diff / YEAR_IN_SECONDS);
            if ($years <= 1) {
                $years = 1;
            }

            /* translators: Time difference between two dates, in years. %s: Number of years. */
            $since = sprintf(_n('%s year', '%s years', $years, 'wp-staging'), $years);
        }

        return $since;
    }





    public function getCurrentTime()
    {
        $timeFormatOption = get_option('time_format');
        return (new DateTime('now', $this->getSiteTimezoneObject()))->format($timeFormatOption);
    }





    public function getCurrentTimestamp(): int
    {
        return (new DateTime('now', $this->getSiteTimezoneObject()))->getTimestamp();
    }







    public static function formatQueryTime(float $seconds): string
    {
        if ($seconds < 1) {
            return round($seconds * 1000, 1) . ' ms';
        }

        return round($seconds, 2) . ' s';
    }
}
