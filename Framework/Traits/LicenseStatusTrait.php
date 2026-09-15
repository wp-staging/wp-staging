<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Pro\License\Licensing;





trait LicenseStatusTrait
{



    protected function getLicenseStatus()
    {
        if (!class_exists(Licensing::class)) {
            return get_option('wpstg_license_status');
        }

        return WPStaging::make(Licensing::class)->getLicenseStatus();
    }
}
