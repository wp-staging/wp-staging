<?php

namespace WPStaging;

use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Utils\IISWebConfig;
use WPStaging\Utils\Htaccess;
use WPStaging\Utils\Filesystem;

/**
 * Install Class
 *
 */
class Install
{
    private $bootstrap;

    public function __construct(\WpstgBootstrapInterface $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function activation()
    {
        $this->bootstrap->checkRequirements();
        $this->bootstrap->bootstrap();

        if ($this->bootstrap->passedRequirements()) {
            $this->initCron();
            $this->installOptimizer();
            $this->createHtaccess();
            $this->createIndex();
            $this->createWebConfig();
        }
    }

    private function initCron()
    {
        // Register cron job.
        $cron = new \WPStaging\Cron\Cron;
        $cron->schedule_event();
    }

    private function installOptimizer()
    {

        // Install Optimizer
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();

        if (!defined('WPSTGPRO_VERSION')) {
            // Add the transient to redirect for class Welcome (not for multisites) and not for Pro version
            set_transient('wpstg_activation_redirect', true, 3600);
        }
    }

    private function createHtaccess()
    {
        $htaccess = new Htaccess();
        $htaccess->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . '.htaccess');
        $htaccess->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . 'logs/.htaccess');

        if (extension_loaded('litespeed')) {
            $htaccess->createLitespeed(ABSPATH . '.htaccess');
        }
    }

    private function createIndex()
    {
        $filesystem = new Filesystem();
        $filesystem->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . 'index.php', "<?php // silence");
        $filesystem->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . 'logs/index.php', "<?php // silence");
    }

    private function createWebConfig()
    {
        $webconfig = new IISWebConfig();
        $webconfig->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . 'web.config');
        $webconfig->create(trailingslashit(\WPStaging\WPStaging::getContentDir()) . 'logs/web.config');
    }
}
