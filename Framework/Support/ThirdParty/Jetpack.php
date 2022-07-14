<?php

namespace WPStaging\Framework\Support\ThirdParty;

use WPStaging\Framework\Adapter\WpAdapter;

/**
 * Class Jetpack
 *
 * Provide special treatments for cloning and pushing when a site is using jetpack
 *
 * @package WPStaging\Framework\Support\ThirdParty
 */
class Jetpack
{
    /**
     * Const used for checking staging mode by Jetpack Plugin
     */
    const STAGING_MODE_CONST = 'JETPACK_STAGING_MODE';

    /** @var WpAdapter */
    protected $wpAdapter;

    public function __construct(WpAdapter $wpAdapter)
    {
        $this->wpAdapter = $wpAdapter;
    }

    /**
     * Check if jetpack plugin installed and active
     *
     * @return bool
     */
    public function isJetpackActive()
    {
        return $this->wpAdapter->isPluginActive('jetpack/jetpack.php');
    }
}
