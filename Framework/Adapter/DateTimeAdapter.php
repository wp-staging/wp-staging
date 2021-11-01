<?php

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x; type hinting & return types

namespace WPStaging\Framework\Adapter;

use DateTime;

class DateTimeAdapter
{
    const DEFAULT_TIME_FORMAT = 'H:i:s';

    /** @var string */
    private $dateFormat;

    /** @var string */
    private $timeFormat;

    // TODO PHP5.6 constant
    private $genericDateFormats = [
        // WP Suggested formats
        'F j, Y',
        'Y-m-d',
        'm/d/Y',
        'd/m/Y',
        // Commonly used formats
        'd-m-Y',
        'm-d-Y',
        'Y-m-d',
        'Y/m/d',
    ];

    public function __construct()
    {
        $this->dateFormat = get_option('date_format');
        $this->timeFormat = get_option('time_format');
    }

    public function getWPDateTimeFormat()
    {
        return $this->dateFormat . ' ' . $this->timeFormat;
    }

    public function getDateTimeFormat()
    {
        $dateFormat = $this->dateFormat;
        $timeFormat = self::DEFAULT_TIME_FORMAT;

        if (!$dateFormat) {
            $dateFormat = 'Y/m/d';
        }

        $dateFormat = str_replace('F', 'M', $dateFormat);

        return $dateFormat . ' ' . $timeFormat;
    }

    /**
     * @param DateTime $dateTime
     * @return string
     */
    public function transformToWpFormat(DateTime $dateTime)
    {
        return get_date_from_gmt($dateTime->format('Y-m-d H:i:s'), $this->getDateTimeFormat());
    }

    /**
     * @param string $value
     * @return DateTime|null
     */
    public function getDateTime($value)
    {
        $date = null;
        foreach ($this->generateDefaultDateFormats() as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date) {
                break;
            }
        }

        return $date ?: null;
    }

    // TODO
    private function generateDefaultDateFormats()
    {
        $formats = [
            'U', // Timestamp
            $this->getDateTimeFormat(),
            $this->getWPDateTimeFormat(),
        ];

        foreach ($this->genericDateFormats as $format) {
            $formats[] = $format . ' ' . self::DEFAULT_TIME_FORMAT;
        }

        return $formats;
    }
}
