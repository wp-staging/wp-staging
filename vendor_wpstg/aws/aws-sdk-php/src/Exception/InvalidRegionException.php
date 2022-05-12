<?php

namespace WPStaging\Vendor\Aws\Exception;

use WPStaging\Vendor\Aws\HasMonitoringEventsTrait;
use WPStaging\Vendor\Aws\MonitoringEventsInterface;
class InvalidRegionException extends \RuntimeException implements \WPStaging\Vendor\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
