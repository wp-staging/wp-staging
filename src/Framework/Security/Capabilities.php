<?php

namespace WPStaging\Framework\Security;

/**
 * Class Capabilities
 *
 * This class should return capabilities to be used in all
 * placed we do a capability check on the plugin.
 *
 * @package WPStaging\Framework\Security
 */
class Capabilities
{
    /**
     * @return string The required capability to manage WPSTAGING.
     */
    public function manageWPSTG()
    {
        return 'manage_options';
    }
}
