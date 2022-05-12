<?php

namespace WPStaging\Vendor\Aws\ClientSideMonitoring\Exception;

use WPStaging\Vendor\Aws\HasMonitoringEventsTrait;
use WPStaging\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements \WPStaging\Vendor\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
