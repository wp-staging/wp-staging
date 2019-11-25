<?php

/**
 * Chron relevant stuff
 */

namespace WPStaging\Cron;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

class Cron {

   
   public function __construct() {
      add_filter( 'cron_schedules', array($this, 'add_new_intervals') );
   }

   /**
    * Add new intervals for wp cron jobs
    * @param type $schedules
    * @return type
    */
   public function add_new_intervals( $schedules ) {
      // add weekly and monthly intervals
      $schedules['weekly'] = array(
          'interval' => 604800,
          'display' => __( 'Once Weekly' )
      );

      $schedules['monthly'] = array(
          'interval' => 2635200,
          'display' => __( 'Once a month' )
      );

      return $schedules;
   }

   public function schedule_event() {
      if( !wp_next_scheduled( 'wpstg_weekly_event' ) ) {
         wp_schedule_event( time(), 'weekly', 'wpstg_weekly_event' );
      }
   }
}
