<?php

namespace WPStaging\Vendor\Aws\Exception;

use WPStaging\Vendor\Aws\HasMonitoringEventsTrait;
use WPStaging\Vendor\Aws\MonitoringEventsInterface;
class InvalidJsonException extends \RuntimeException implements \WPStaging\Vendor\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
