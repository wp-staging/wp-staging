<?php

namespace WPStaging\Backend\Optimizer;

// No Direct Access
use WPStaging\Framework\Filesystem\Filesystem;

if (!defined("WPINC")) {
    die;
}

/**
 * Optimizer
 */

class Optimizer
{

    private $mudir;
    private $source;
    private $dest;

    /**
     * Optimizer constructor.
     *
     * If changes are made to this, also check uninstall.php!
     */
    public function __construct()
    {
        $this->mudir = ( defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') ) ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';

        $this->source = trailingslashit(WPSTG_PLUGIN_DIR) . 'Backend/Optimizer/wp-staging-optimizer.php';
        $this->dest = trailingslashit($this->mudir) . 'wp-staging-optimizer.php';
    }

    public function installOptimizer()
    {
        if (file_exists($this->dest) && $this->mustUpdateOptimizer() === false) {
            return false;
        }

        if ((new Filesystem())->mkdir($this->mudir)) {
            $this->copy();
        }
        return false;
    }

    private function copy()
    {
        if (!@copy($this->source, $this->dest)) {
            return false;
        }
    }

   /**
    * Check if the Optimizer must use plugin must be updated
    * @return boolean
    */
    private function mustUpdateOptimizer()
    {
        $isVersionNumber = defined('WPSTG_OPTIMIZER_VERSION') ? WPSTG_OPTIMIZER_VERSION : false;

        $update = false;

        if ($isVersionNumber === false) {
            return true;
        }

        $mustVersionNumber = defined('WPSTG_OPTIMIZER_MUVERSION') ? WPSTG_OPTIMIZER_MUVERSION : false;

        if ($mustVersionNumber) {
            $update = version_compare($isVersionNumber, $mustVersionNumber, '!=');
        }

        return $update;
    }
}
