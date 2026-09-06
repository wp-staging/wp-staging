<?php

namespace WPStaging\Backend\Optimizer;

use WPStaging\Core\DTO\Settings as SettingsDTO;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Network\HttpBasicAuth;
use WPStaging\Framework\Security\Capabilities;

 
if (!defined("WPINC")) {
    die;
}




class Optimizer
{
    use HttpBasicAuth;

 
    const TRANSIENT_CHECK_SECRET = 'wpstg_optimizer_check_secret';

 
    const OPTION_OPTIMIZER_DISABLED_AFTER_FATAL = 'wpstg_optimizer_disabled_after_fatal';

 
    const SAFETY_CHECK_TIMEOUT = 5;




    private $mudir;




    private $source;




    private $dest;






    public function __construct()
    {
        $this->mudir  = ( defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') ) ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';

        $this->source = trailingslashit(WPSTG_PLUGIN_DIR) . 'Backend/Optimizer/wp-staging-optimizer.php';
        $this->dest   = trailingslashit($this->mudir) . 'wp-staging-optimizer.php';
    }









    public function maybeRunSafetyCheck()
    {
        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        if (!$this->isOptimizerSettingEnabled()) {
            return;
        }

        if (get_option(self::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL) === '1') {
            return;
        }

        if (get_transient(self::TRANSIENT_CHECK_SECRET)) {
            return;
        }

        $secret = wp_generate_password(32, false);
        set_transient(self::TRANSIENT_CHECK_SECRET, $secret, MINUTE_IN_SECONDS);

 
        $headers   = $this->getHttpAuthHeaders();
        $sslVerify = empty($headers) ? apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false) : true;

        wp_remote_post(admin_url('admin-ajax.php'), [
            'timeout'   => self::SAFETY_CHECK_TIMEOUT,
            'blocking'  => true,
            'sslverify' => $sslVerify,
            'headers'   => $headers,
            'body'      => [
                'action' => 'wpstg_can_use_optimizer',
                'secret' => $secret,
            ],
        ]);

 
        wp_cache_delete(self::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL, 'options');
        wp_cache_delete('alloptions', 'options');
        if (get_option(self::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL, null) !== null) {
            return;
        }

 
        add_option(self::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL, '0');
    }





    public function ajaxCanUseOptimizer()
    {
        $providedSecret = isset($_REQUEST['secret']) ? sanitize_text_field($_REQUEST['secret']) : '';
        if (empty($providedSecret)) {
            wp_send_json_error();
        }

        $expectedSecret = get_transient(self::TRANSIENT_CHECK_SECRET);
        if (empty($expectedSecret) || !hash_equals($expectedSecret, $providedSecret)) {
            wp_send_json_error();
        }

        delete_transient(self::TRANSIENT_CHECK_SECRET);

        wp_send_json_success();
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






    public function clearDisabledAfterFatalFlag()
    {
        delete_option(self::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL);
        delete_transient(self::TRANSIENT_CHECK_SECRET);
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




    private function isOptimizerSettingEnabled(): bool
    {
        return WPStaging::make(SettingsDTO::class)->isOptimizer();
    }
}
