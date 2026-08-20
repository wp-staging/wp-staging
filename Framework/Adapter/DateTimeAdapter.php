<?php

 
 

namespace WPStaging\Framework\Adapter;

class DateTimeAdapter
{
    const DEFAULT_TIME_FORMAT = 'H:i:s';

 
    private $dateFormat;

 
    private $timeFormat;

 
    private $genericDateFormats = [
 
        'F j, Y',
        'Y-m-d',
        'm/d/Y',
        'd/m/Y',
 
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





    public function transformToWpFormat(\DateTime $dateTime)
    {
        return get_date_from_gmt($dateTime->format('Y-m-d H:i:s'), $this->getDateTimeFormat());
    }





    public function getDateTime($value)
    {
        $date = null;
        foreach ($this->generateDefaultDateFormats() as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                break;
            }
        }

        return $date ?: null;
    }

 
    private function generateDefaultDateFormats()
    {
        $formats = [
            'U', 
            $this->getDateTimeFormat(),
            $this->getWPDateTimeFormat(),
        ];

        foreach ($this->genericDateFormats as $format) {
            $formats[] = $format . ' ' . self::DEFAULT_TIME_FORMAT;
        }

        return $formats;
    }
}
