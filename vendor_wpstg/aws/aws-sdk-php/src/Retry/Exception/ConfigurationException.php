<?php

namespace WPStaging\Vendor\Aws\Retry\Exception;

use WPStaging\Vendor\Aws\HasMonitoringEventsTrait;
use WPStaging\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with retry configuration
 */
class ConfigurationException extends \RuntimeException implements \WPStaging\Vendor\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
