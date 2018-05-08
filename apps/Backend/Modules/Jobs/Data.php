<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

use WPStaging\Utils\Logger;
use WPStaging\WPStaging;
use WPStaging\Utils\Helper;

/**
 * Class Data
 * @package WPStaging\Backend\Modules\Jobs
 */
class Data extends JobExecutable {

   /**
    * @var \wpdb
    */
   private $db;

   /**
    * @var string
    */
   private $prefix;

   /**
    *
    * @var string
    */
   private $homeUrl;

   /**
    * Initialize
    */
   public function initialize() {
      $this->db = WPStaging::getInstance()->get( "wpdb" );

      $this->prefix = $this->options->prefix;

      $helper = new Helper();

      $this->homeUrl = $helper->get_home_url();


      // Fix current step
      if( 0 == $this->options->currentStep ) {
         $this->options->currentStep = 1;
      }
   }

   /**
    * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
    * @return void
    */
   protected function calculateTotalSteps() {
      $this->options->totalSteps = 11;
   }

   /**
    * Start Module
    * @return object
    */
   public function start() {
      // Execute steps
      $this->run();

      // Save option, progress
      $this->saveOptions();

      return ( object ) $this->response;
   }

   /**
    * Execute the Current Step
    * Returns false when over threshold limits are hit or when the job is done, true otherwise
    * @return bool
    */
   protected function execute() {
      // Fatal error. Let this happen never and break here immediately
      if( $this->isRoot() ) {
         return false;
      }

      // Over limits threshold
      if( $this->isOverThreshold() ) {
         // Prepare response and save current progress
         $this->prepareResponse( false, false );
         $this->saveOptions();
         return false;
      }

      // No more steps, finished
      if( $this->isFinished() ) {
         $this->prepareResponse( true, false );
         return false;
      }

      // Execute step
      $stepMethodName = "step" . $this->options->currentStep;
      if( !$this->{$stepMethodName}() ) {
         $this->prepareResponse( false, false );
         return false;
      }

      // Prepare Response
      $this->prepareResponse();

      // Not finished
      return true;
   }

   /**
    * Checks Whether There is Any Job to Execute or Not
    * @return bool
    */
   protected function isFinished() {
      return (
              $this->options->currentStep > $this->options->totalSteps ||
              !method_exists( $this, "step" . $this->options->currentStep )
              );
   }

   /**
    * Check if current operation is done on the root folder or on the live DB
    * @return boolean
    */
   protected function isRoot() {

      // Prefix is the same as the one of live site
      $wpdb = WPStaging::getInstance()->get( "wpdb" );
      if( $wpdb->prefix === $this->prefix ) {
         return true;
      }

      // CloneName is empty
      $name = ( array ) $this->options->cloneDirectoryName;
      if( empty( $name ) ) {
         return true;
      }

      // Live Path === Staging path
      if( $this->homeUrl . $this->options->cloneDirectoryName === $this->homeUrl ) {
         return true;
      }

      return false;
   }

   /**
    * Check if table exists
    * @param string $table
    * @return boolean
    */
   protected function isTable( $table ) {
      if( $this->db->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
         $this->log( "Table {$table} does not exists", Logger::TYPE_ERROR );
         return false;
      }
      return true;
   }

   /**
    * Get the install sub directory if WP is installed in sub directory
    * @return string
    */
   protected function getSubDir() {
      $home = get_option( 'home' );
      $siteurl = get_option( 'siteurl' );

      if( empty( $home ) || empty( $siteurl ) ) {
         return '/';
      }

      $dir = str_replace( $home, '', $siteurl );
      return '/' . str_replace( '/', '', $dir ) . '/';
   }

   /**
    * Replace "siteurl" and "home"
    * @return bool
    */
   protected function step1() {
      $this->log( "Preparing Data Step1: Updating siteurl and homeurl in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_INFO );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      // Installed in sub-directory
      if( $this->isSubDir() ) {
         $this->log( "Preparing Data Step1: Updating siteurl and homeurl to " . rtrim( $this->homeUrl, "/" ) . $this->getSubDir() . $this->options->cloneDirectoryName );
         // Replace URLs
         $result = $this->db->query(
                 $this->db->prepare(
                         "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'", rtrim( $this->homeUrl, "/" ) . $this->getSubDir() . $this->options->cloneDirectoryName
                 )
         );
      } else {
         $this->log( "Preparing Data Step1: Updating siteurl and homeurl to " . rtrim( $this->homeUrl, "/" ) . '/' . $this->options->cloneDirectoryName );
         // Replace URLs
         $result = $this->db->query(
                 $this->db->prepare(
                         "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'", $this->homeUrl . '/' . $this->options->cloneDirectoryName
                 )
         );
      }


      // All good
      if( $result ) {
         return true;
      }

      $this->log( "Preparing Data Step1: Failed to update siteurl and homeurl in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
      return false;
   }

   /**
    * Update "wpstg_is_staging_site"
    * @return bool
    */
   protected function step2() {

      $this->log( "Preparing Data Step2: Updating row wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      $result = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'wpstg_is_staging_site'", "true"
              )
      );

      // No errors but no option name such as wpstg_is_staging_site
      if( '' === $this->db->last_error && 0 == $result ) {
         $result = $this->db->query(
                 $this->db->prepare(
                         "INSERT INTO {$this->prefix}options (option_name,option_value) VALUES ('wpstg_is_staging_site',%s)", "true"
                 )
         );
      }

      // All good
      if( $result ) {
         return true;
      }

      $this->log( "Preparing Data Step2: Failed to update wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
      return false;
   }

   /**
    * Update rewrite_rules
    * @return bool
    */
   protected function step3() {

      $this->log( "Preparing Data Step3: Updating rewrite_rules in {$this->prefix}options {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      $result = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'rewrite_rules'", ' '
              )
      );

      // All good
      if( $result ) {
         return true;
      }

      $this->log( "Preparing Data Step3: Failed to update rewrite_rules in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
      return true;
   }

   /**
    * Update Table Prefix in meta_keys
    * @return bool
    */
   protected function step4() {
      $this->log( "Preparing Data Step4: Updating db prefix in {$this->prefix}usermeta. Error: {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'usermeta' ) ) {
         return true;
      }

      $update = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s", $this->db->prefix, $this->prefix, $this->db->prefix . "_%"
              )
      );

      if( !$update ) {
         $this->log( "Preparing Data Step4: Failed to update {$this->prefix}usermeta meta_key database table prefixes; {$this->db->last_error}", Logger::TYPE_ERROR );
         $this->returnException( "Data Crunching Step 4: Failed to update {$this->prefix}usermeta meta_key database table prefixes; {$this->db->last_error}" );
         return false;
      }

      $this->log( "Updating db prefixes in {$this->prefix}options. Error: {$this->db->last_error}" );

      $resultUserMeta = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s", $this->db->prefix, $this->prefix, $this->db->prefix . "_%"
              )
      );

      if( !$resultUserMeta ) {
         $this->log( "Preparing Data Step4: Failed to update db prefixes in {$this->prefix}options. Error: {$this->db->last_error}", Logger::TYPE_ERROR );
         $this->returnException( "Data Crunching Step 4: Failed to update db prefixes in {$this->prefix}options. Error: {$this->db->last_error}" );
         return false;
      }

      return true;
   }

   /**
    * Update $table_prefix in wp-config.php
    * @return bool
    */
   protected function step5() {
      $path = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";

      $this->log( "Preparing Data Step5: Updating table_prefix in {$path} to " . $this->prefix );
      if( false === ($content = file_get_contents( $path )) ) {
         $this->log( "Preparing Data Step5: Failed to update table_prefix in {$path}. Can't read contents", Logger::TYPE_ERROR );
         return false;
      }

      // Replace table prefix
      $content = str_replace( '$table_prefix', '$table_prefix = \'' . $this->prefix . '\';//', $content );

      // Replace URLs
      $content = str_replace( $this->homeUrl, $this->homeUrl . '/' . $this->options->cloneDirectoryName, $content );

      if( false === @file_put_contents( $path, $content ) ) {
         $this->log( "Preparing Data Step5: Failed to update $table_prefix in {$path} to " . $this->prefix . ". Can't save contents", Logger::TYPE_ERROR );
         return false;
      }

      return true;
   }

   /**
    * Reset index.php to original file
    * This is needed if live site is located in subfolder
    * Check first if main wordpress is used in subfolder and index.php in parent directory
    * @see: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
    * @return bool
    */
   protected function step6() {

      if( !$this->isSubDir() ) {
         $this->debugLog( "Preparing Data Step6: WP installation is not in a subdirectory! All good, skipping this step" );
         return true;
      }

      $path = ABSPATH . $this->options->cloneDirectoryName . "/index.php";

      if( false === ($content = file_get_contents( $path )) ) {
         $this->log( "Preparing Data Step6: Failed to reset {$path} for sub directory; can't read contents", Logger::TYPE_ERROR );
         return false;
      }


      if( !preg_match( "/(require(.*)wp-blog-header.php' \);)/", $content, $matches ) ) {
         $this->log(
                 "Preparing Data Step6: Failed to reset index.php for sub directory; wp-blog-header.php is missing", Logger::TYPE_ERROR
         );
         return false;
      }
      $this->log( "Preparing Data: WP installation is in a subdirectory. Progressing..." );

      $pattern = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);/";

      $replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' ); // " . $matches[0];
      $replace.= " // Changed by WP-Staging";



      if( null === ($content = preg_replace( array($pattern), $replace, $content )) ) {
         $this->log( "Preparing Data: Failed to reset index.php for sub directory; replacement failed", Logger::TYPE_ERROR );
         return false;
      }

      if( false === @file_put_contents( $path, $content ) ) {
         $this->log( "Preparing Data: Failed to reset index.php for sub directory; can't save contents", Logger::TYPE_ERROR );
         return false;
      }
      $this->Log( "Preparing Data: Finished Step 6 successfully" );
      return true;
   }

   /**
    * Update wpstg_rmpermalinks_executed
    * @return bool
    */
   protected function step7() {

      $this->log( "Preparing Data Step7: Updating wpstg_rmpermalinks_executed in {$this->prefix}options {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      $result = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'wpstg_rmpermalinks_executed'", ' '
              )
      );

      // All good
      if( $result ) {
         $this->Log( "Preparing Data Step7: Finished Step 7 successfully" );
         return true;
      }

      $this->log( "Failed to update wpstg_rmpermalinks_executed in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_WARNING );
      return true;
   }

   /**
    * Update permalink_structure
    * @return bool
    */
   protected function step8() {

      $this->log( "Preparing Data Step8: Updating permalink_structure in {$this->prefix}options {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      $result = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'permalink_structure'", ' '
              )
      );

      // All good
      if( $result ) {
         $this->Log( "Preparing Data Step8: Finished Step 8 successfully" );
         return true;
      }

      $this->log( "Failed to update permalink_structure in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
      return true;
   }

   /**
    * Update blog_public option to not allow staging site to be indexed by search engines
    * @return bool
    */
   protected function step9() {

      $this->log( "Preparing Data Step9: Set staging site to noindex" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      $result = $this->db->query(
              $this->db->prepare(
                      "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'blog_public'", '0'
              )
      );

      // All good
      if( $result ) {
         $this->Log( "Preparing Data Step9: Finished Step 9 successfully" );
         return true;
      }

      $this->log( "Can not update staging site to noindex. Possible already done!", Logger::TYPE_WARNING );
      return true;
   }

   /**
    * Update WP_HOME in wp-config.php
    * @return bool
    */
   protected function step10() {
      $path = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";

      $this->log( "Preparing Data Step10: Updating WP_HOME in wp-config.php to " . $this->getStagingSiteUrl() );

      if( false === ($content = file_get_contents( $path )) ) {
         $this->log( "Preparing Data Step10: Failed to update WP_HOME in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR );
         return false;
      }


      // Get WP_HOME from wp-config.php
      preg_match( "/define\s*\(\s*'WP_HOME'\s*,\s*(.*)\s*\);/", $content, $matches );

      if( !empty( $matches[1] ) ) {
         $matches[1];

         $pattern = "/define\s*\(\s*'WP_HOME'\s*,\s*(.*)\s*\);/";

         $replace = "define('WP_HOME','" . $this->getStagingSiteUrl() . "'); // " . $matches[1];
         $replace.= " // Changed by WP-Staging";

         if( null === ($content = preg_replace( array($pattern), $replace, $content )) ) {
            $this->log( "Preparing Data: Failed to reset index.php for sub directory; replacement failed", Logger::TYPE_ERROR );
            return false;
         }
      } else {
         $this->log( "Preparing Data Step10: WP_HOME not defined in wp-config.php. Skipping this step." );
      }

      if( false === @file_put_contents( $path, $content ) ) {
         $this->log( "Preparing Data Step11: Failed to update WP_SITEURL. Can't save contents", Logger::TYPE_ERROR );
         return false;
      }
      $this->Log( "Preparing Data: Finished Step 11 successfully" );
      return true;
   }

   /**
    * Update WP_SITEURL in wp-config.php
    * @return bool
    */
   protected function step11() {
      $path = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";

      $this->log( "Preparing Data Step11: Updating WP_SITEURL in wp-config.php to " . $this->getStagingSiteUrl() );

      if( false === ($content = file_get_contents( $path )) ) {
         $this->log( "Preparing Data Step11: Failed to update WP_SITEURL in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR );
         return false;
      }


      // Get WP_SITEURL from wp-config.php
      preg_match( "/define\s*\(\s*'WP_SITEURL'\s*,\s*(.*)\s*\);/", $content, $matches );

      if( !empty( $matches[1] ) ) {
         $matches[1];

         $pattern = "/define\s*\(\s*'WP_SITEURL'\s*,\s*(.*)\s*\);/";

         $replace = "define('WP_SITEURL','" . $this->getStagingSiteUrl() . "'); // " . $matches[1];
         $replace.= " // Changed by WP-Staging";

         if( null === ($content = preg_replace( array($pattern), $replace, $content )) ) {
            $this->log( "Preparing Data Step11: Failed to update WP_SITEURL", Logger::TYPE_ERROR );
            return false;
         }
      } else {
         $this->log( "Preparing Data Step11: WP_SITEURL not defined in wp-config.php. Skipping this step." );
      }


      if( false === @file_put_contents( $path, $content ) ) {
         $this->log( "Preparing Data Step11: Failed to update WP_SITEURL. Can't save contents", Logger::TYPE_ERROR );
         return false;
      }
      $this->Log( "Preparing Data: Finished Step 11 successfully" );
      return true;
   }

   /**
    * Return URL to staging site
    * @return string
    */
   protected function getStagingSiteUrl() {
      if( $this->isSubDir() ) {
         return rtrim( $this->homeUrl, "/" ) . $this->getSubDir() . $this->options->cloneDirectoryName;
      }

      return rtrim( $this->homeUrl, "/" ) . '/' . $this->options->cloneDirectoryName;
   }

   /**
    * Check if WP is installed in subdir
    * @return boolean
    */
   protected function isSubDir() {
      if( get_option( 'siteurl' ) !== get_option( 'home' ) ) {
         return true;
      }
      return false;
   }

}
