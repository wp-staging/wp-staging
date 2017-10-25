<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Utils\Logger;
use WPStaging\WPStaging;

/**
 * Class Data
 * @package WPStaging\Backend\Modules\Jobs
 */
class Data extends JobExecutable
{

    /**
     * @var \wpdb
     */
    private $db;

    /**
     * @var string
     */
    private $prefix;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->db       = WPStaging::getInstance()->get("wpdb");

        //$this->prefix   = "wpstg{$this->options->cloneNumber}_";
        $this->prefix   = $this->options->prefix;

        // Fix current step
        if (0 == $this->options->currentStep)
        {
            $this->options->currentStep = 1;
        }
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 6;
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveOptions();

        // Prepare response
        $this->response = array(
            "status"        => true,
            "percentage"    => 100,
            "total"         => $this->options->totalSteps,
            "step"          => $this->options->totalSteps,
            "last_msg"      => $this->logger->getLastLogMsg(),
            "running_time"  => $this->time() - time(),
            "job_done"      => true
        );

        return (object) $this->response;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Fatal error. Let this happen never and break here immediately
        if ($this->isRoot()){
            return false;
        }
        
        // Over limits threshold
        if ($this->isOverThreshold())
        {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->isFinished())
        {
            $this->prepareResponse(true, false);
            return false;
        }

        // Execute step
        $stepMethodName = "step" . $this->options->currentStep;
        if (!$this->{$stepMethodName}())
        {
            $this->prepareResponse(false, false);
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
    private function isFinished()
    {
        return (
            $this->options->currentStep > $this->options->totalSteps ||
            !method_exists($this, "step" . $this->options->currentStep)
        );
    }
    
    /**
     * Check if current operation is done on the root folder or on the live DB
     * @return boolean
     */
    private function isRoot(){
        
        // Prefix is the same as the one of live site
        $wpdb = WPStaging::getInstance()->get("wpdb");
        if ($wpdb->prefix === $this->prefix){
            return true;
        }
        
        // CloneName is empty
        $name = (array)$this->options->cloneDirectoryName;
        if (empty($name)){
            return true;
        }
        
        // Live Path === Staging path
        if (get_home_url() . $this->options->cloneDirectoryName === get_home_url()){
            return true;
        }
        
        return false;
    }

        
    /**
     * Check if table exists
     * @param string $table
     * @return boolean
     */
    protected function isTable($table){
      if($this->db->get_var("SHOW TABLES LIKE '{$table}'") != $table ){
         $this->log( "Table {$table} does not exists", Logger::TYPE_ERROR );
         return false;
      }
      return true;
    }

    /**
     * Replace "siteurl"
     * @return bool
     */
    protected function step1() {
      $this->log( "Search & Replace: Updating siteurl and homeurl in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_INFO );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

      // Installed in sub-directory
      if( isset( $this->settings->wpSubDirectory ) && "1" === $this->settings->wpSubDirectory ) {
         $subDirectory = str_replace( get_home_path(), '', ABSPATH );
         $this->log( "Updating siteurl and homeurl to " . get_home_url() . '/' . $subDirectory . $this->options->cloneDirectoryName );
         // Replace URLs
         $result = $this->db->query(
                 $this->db->prepare(
                         "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'", get_home_url() . '/' . $subDirectory . $this->options->cloneDirectoryName
                 )
         );
      } else {
         $this->log( "Search & Replace:: Updating siteurl and homeurl to " . get_home_url() . '/' . $this->options->cloneDirectoryName );
         // Replace URLs
         $result = $this->db->query(
                 $this->db->prepare(
                         "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'", get_home_url() . '/' . $this->options->cloneDirectoryName
                 )
         );
      }


      // All good
      if( $result ) {
         return true;
      }

      $this->log( "Search & Replace: Failed to update siteurl and homeurl in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
      return false;
   }

   /**
     * Update "wpstg_is_staging_site"
     * @return bool
     */
    protected function step2()
    {
              
        $this->log( "Search & Replace: Updating row wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'wpstg_is_staging_site'",
                "true"
            )
        );

        // No errors but no option name such as wpstg_is_staging_site
        if ('' === $this->db->last_error && 0 == $result)
        {
            $result = $this->db->query(
                $this->db->prepare(
                    "INSERT INTO {$this->prefix}options (option_name,option_value) VALUES ('wpstg_is_staging_site',%s)",
                    "true"
                )
            );
        }

        // All good
        if ($result)
        {
            return true;
        }

        $this->log("Search & Replace: Failed to update wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR);
        return false;
    }

    /**
     * Update rewrite_rules
     * @return bool
     */
    protected function step3()
    {
       
        $this->log("Search & Replace: Updating rewrite_rules in {$this->prefix}options {$this->db->last_error}");
        
      if( false === $this->isTable( $this->prefix . 'options' ) ) {
         return true;
      }
        
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'rewrite_rules'",
                ' '
            )
        );

        // All good
        if ($result)
        {
            return true;
        }

        $this->log("Failed to update rewrite_rules in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR);
        return true;
    }

    /**
     * Update Table Prefix in meta_keys
     * @return bool
     */
    protected function step4() {
        $this->log( "Search & Replace: Updating {$this->prefix}usermeta db prefix {$this->db->last_error}" );

      if( false === $this->isTable( $this->prefix . 'usermeta' ) ) {
         return true;
      }

        $resultOptions = $this->db->query(
                $this->db->prepare(
                        "UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s", $this->db->prefix, $this->prefix, $this->db->prefix . "_%"
                )
        );

        if( !$resultOptions ) {
            $this->log( "Search & Replace: Failed to update usermeta meta_key database table prefixes; {$this->db->last_error}", Logger::TYPE_ERROR );
            return false;
        }

        $this->log( "Updating {$this->prefix}options, option_name database table prefixes; {$this->db->last_error}" );

        $resultUserMeta = $this->db->query(
                $this->db->prepare(
                        "UPDATE {$this->prefix}options SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s", $this->db->prefix, $this->prefix, $this->db->prefix . "_%"
                )
        );

        if( !$resultUserMeta ) {
            $this->log( "Search & Replace: Failed to update options, option_name database table prefixes; {$this->db->last_error}", Logger::TYPE_ERROR );
            return false;
        }

        return true;
    }

    /**
     * Update $table_prefix in wp-config.php
     * @return bool
     */
    protected function step5()
    {
        $path = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";
        
        $this->log("Search & Replace: Updating table_prefix in {$path} to " . $this->prefix);
        if (false === ($content = file_get_contents($path)))
        {
            $this->log("Search & Replace: Failed to update table_prefix in {$path}. Can't read contents", Logger::TYPE_ERROR);
            return false;
        }
        
        // Replace table prefix
        $content = str_replace('$table_prefix', '$table_prefix = \'' . $this->prefix . '\';//', $content);
        
        // Replace URLs
        $content = str_replace(get_home_url(), get_home_url() . '/' . $this->options->cloneDirectoryName, $content);

        if (false === @file_put_contents($path, $content))
        {
            $this->log("Search & Replace: Failed to update $table_prefix in {$path} to " .$this->prefix . ". Can't save contents", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Reset index.php to original file
     * Check first if main wordpress is used in subfolder and index.php in parent directory
     * @see: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
     * @return bool
     */
    protected function step6()
    {
        // No settings, all good
        if (!isset($this->settings->wpSubDirectory) || "1" !== $this->settings->wpSubDirectory)
        {
            $this->log("Search & Replace: WP installation is not in a subdirectory! All good, skipping this step");
            return true;
        }

        $path = ABSPATH . $this->options->cloneDirectoryName . "/index.php";

        if (false === ($content = file_get_contents($path)))
        {
            $this->log("Search & Replace: Failed to reset {$path} for sub directory; can't read contents", Logger::TYPE_ERROR);
            return false;
        }


        if (!preg_match("/(require(.*)wp-blog-header.php' \);)/", $content, $matches))
        {
            $this->log(
                "Search & Replace: Failed to reset index.php for sub directory; wp-blog-header.php is missing",
                Logger::TYPE_ERROR
            );
            return false;
        }

        $pattern = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);/";

        $replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' ); // " . $matches[0];
        $replace.= " // Changed by WP-Staging";

        if (null === preg_replace($pattern, $replace, $content))
        {
            $this->log("Search & Replace: Failed to reset index.php for sub directory; replacement failed", Logger::TYPE_ERROR);
            return false;
        }

        if (false === @file_put_contents($path, $content))
        {
            $this->log("Search & Replace: Failed to reset index.php for sub directory; can't save contents", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }
}