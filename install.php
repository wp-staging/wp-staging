<?php

namespace WPStaging;

use WPStaging\WPStaging;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Cron\Cron;
use WPStaging\Utils\IISWebConfig;
use WPStaging\Utils\Htaccess;
use WPStaging\Utils\Filesystem;

/**
 * Install Function
 *
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

/*
 * Install Multisite
 * check first if multisite is enabled
 * @since 2.1.1
 * 
 */

class Install {

    public function __construct() {
        register_activation_hook( __DIR__ . DIRECTORY_SEPARATOR . WPSTG_PLUGIN_SLUG . '.php', array($this, 'activation') );
    }

    public static function activation() {
        $this->installOptimizer();
        $this->createHtaccess();
        $this->createIndex();
        $this->createWebConfig();
    }

    public function installOptimizer() {
        // Register cron job.
        $cron = new \WPStaging\Cron\Cron;
        $cron->schedule_event();

        // Install Optimizer 
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();

        // Add the transient to redirect for class Welcome (not for multisites)
        set_transient( 'wpstg_activation_redirect', true, 3600 );
    }

    public function createHtaccess() {
        $htaccess = new Htaccess();
        $htaccess->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . '.htaccess' );
        $htaccess->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . 'logs/.htaccess' );

        if( extension_loaded( 'litespeed' ) ) {
            $htaccess->createLitespeed( ABSPATH . '.htaccess' );
        }
    }

    public function createIndex() {
        $filesystem = new Filesystem();
        $filesystem->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . 'index.php', "<?php // silence" );
        $filesystem->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . 'logs/index.php', "<?php // silence" );
    }

    public function createWebConfig() {
        $webconfig = new IISWebConfig();
        $webconfig->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . 'web.config' );
        $webconfig->create( trailingslashit( \WPStaging\WPStaging::getContentDir() ) . 'logs/web.config' );
    }

}

new Install();
