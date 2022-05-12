<?php

namespace WPStaging\Vendor\Aws\DefaultsMode\Exception;

use WPStaging\Vendor\Aws\HasMonitoringEventsTrait;
use WPStaging\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration mode
 */
class ConfigurationException extends \RuntimeException implements \WPStaging\Vendor\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
