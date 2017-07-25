<?php
namespace WPStaging\Backend\Optimizer;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

/**
 * Optimizer
 */

class Optimizer {

   private $mudir;
   private $source;
   private $dest;

   public function __construct() {
      $this->mudir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
 
      $this->source = trailingslashit( WPSTG_PLUGIN_DIR ) . 'apps/Backend/Optimizer/wp-staging-optimizer.php';
      $this->dest = trailingslashit( $this->mudir ) . 'wp-staging-optimizer.php';
   }

   public function installOptimizer() {
      if( wp_mkdir_p( $this->mudir ) ) {
         $this->copy();
      } 
      return false;
   }

   public function unstallOptimizer() {
      if( file_exists( $this->dest ) && !unlink( $this->dest ) ) {
         return false;
      }
   }

   private function copy() {
      if( !copy( $this->source, $this->dest ) ) {
         return false;
      }
   }

}
