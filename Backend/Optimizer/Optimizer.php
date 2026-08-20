<?php

namespace WPStaging\Backend\Optimizer;

use WPStaging\Framework\Filesystem\Filesystem;

 
if (!defined("WPINC")) {
    die;
}




class Optimizer
{



    private $mudir;




    private $source;




    private $dest;






    public function __construct()
    {
        $this->mudir  = ( defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') ) ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';

        $this->source = trailingslashit(WPSTG_PLUGIN_DIR) . 'Backend/Optimizer/wp-staging-optimizer.php';
        $this->dest   = trailingslashit($this->mudir) . 'wp-staging-optimizer.php';
    }






    public function installOptimizer(): bool
    {
        if (file_exists($this->dest) && $this->mustUpdateOptimizer() === false) {
            return false;
        }

        if (file_exists($this->dest) && !is_writable($this->dest)) {
            return false;
        }

        if (!(new Filesystem())->mkdir($this->mudir)) {
            return false;
        }

 
        if (!is_writable($this->mudir)) {
            return false;
        }

        return @copy($this->source, $this->dest);
    }






    private function mustUpdateOptimizer(): bool
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
