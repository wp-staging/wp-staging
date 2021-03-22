<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Adapter\Maintenance;

trait MaintenanceTrait
{
    public function enableMaintenance($isMaintenance)
    {
        (new Maintenance())->enableMaintenance($isMaintenance);
    }

    public function skipMaintenanceMode()
    {
        apply_filters('enable_maintenance_mode', false);
    }
}
