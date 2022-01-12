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
     * @see wp_timezone_string()
     *
     * @return mixed|string|void PHP timezone string or a ±HH:MM offset.
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
}
