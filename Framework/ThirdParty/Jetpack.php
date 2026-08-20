<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\WpAdapter;








class Jetpack
{



    const STAGING_MODE_CONST = 'JETPACK_STAGING_MODE';

 
    protected $wpAdapter;

    public function __construct(WpAdapter $wpAdapter)
    {
        $this->wpAdapter = $wpAdapter;
    }






    public function isJetpackActive()
    {
        return $this->wpAdapter->isPluginActive('jetpack/jetpack.php');
    }
}
