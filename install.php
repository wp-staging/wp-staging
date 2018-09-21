<?php

namespace WPStaging;

use WPStaging\WPStaging;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Cron\Cron;

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
      // Register cron job.
      $cron = new \WPStaging\Cron\Cron;
      $cron->schedule_event();

      // Install Optimizer 
      $optimizer = new Optimizer();
      $optimizer->installOptimizer();

      // Add the transient to redirect for class Welcome (not for multisites)
      set_transient( 'wpstg_activation_redirect', true, 3600 );
   }

}

new Install();
