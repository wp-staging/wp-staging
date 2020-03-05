<?php

//TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Adapter;

use DateTime as CoreDateTime;

class DateTimeAdapter
{
    public function getDateTimeFormat()
    {
        $dateFormat = get_option('date_format');
        $timeFormat = 'H:i:s';

        if (!$dateFormat) {
            $dateFormat = 'Y/m/d';
        }

        $dateFormat = str_replace('F', 'M', $dateFormat);

        return $dateFormat . ' ' . $timeFormat;
    }

    // TODO PHP7.0; public function transformWpDateTimeFormat(DateTime $dateTime): string
    /**
     * @param CoreDateTime $dateTime
     * @return string
     */
    public function transformToWpFormat(CoreDateTime $dateTime)
    {
        return $dateTime->format($this->getDateTimeFormat());
    }
}
