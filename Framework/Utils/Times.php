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

/**
 * Class Times
 *
 * @package WPStaging\Framework\Utils
 */
class Times
{
    public function findNextHour()
    {
        $dateTime = new \DateTime('now', wp_timezone());
        $dateTime->add(new \DateInterval('PT1H'))
                 ->setTime($dateTime->format('H'), '00');

        return $dateTime;
    }

    /**
     * Produces a set of date objects modeling a time range.
     *
     * This method is similar, in concept, to the PHP Core `range` method
     * where the entity is changed from numeric values to Dates.
     *
     * @param DateTime|DateTimeImmutable|string $start        Either a Date object or a valid date definition to start
     *                                                        the range from.
     * @param DateTime|DateTimeImmutable|string $end          Either a Date object or a valid date definition to end
     *                                                        the range at, inclusively.
     * @param DateInterval|string               $step         The step definition, as either an Interval object, or as
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
                : new DateTimeImmutable($start, wp_timezone());
        }
        if ($end instanceof DateTimeImmutable) {
            $endDateObject = $end;
        } else {
            $endDateObject = $end instanceof DateTime ?
                DateTimeImmutable::createFromMutable($end)
                : new DateTimeImmutable($end, wp_timezone());
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
