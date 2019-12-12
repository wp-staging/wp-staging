<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Utils\Logger;
use WPStaging\WPStaging;
use WPStaging\Utils\Helper;
use WPStaging\Utils\Multisite;
use WPStaging\Utils\Strings;
use WPStaging\Backend\Modules\Jobs\JobExecutable;

/**
 * Class Data
 * @package WPStaging\Backend\Modules\Jobs
 */
class DataExternal extends JobExecutable
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
     * Tables e.g wpstg3_options
     * @var array
     */
    private $tables;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->db           = $this->getStagingDB();
        $this->productionDb = WPStaging::getInstance()->get("wpdb");
        $this->prefix       = $this->options->prefix;
        $this->db->prefix   = $this->options->databasePrefix;

        $this->getTables();

        // Fix current step
        if (0 == $this->options->currentStep) {
            $this->options->currentStep = 0;
        }
    }

    /**
     * Get database object to interact with
     */
    private function getStagingDB()
    {
        return new \wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);
    }

    /**
     * Get a list of tables to copy
     */
    private function getTables()
    {
        $strings      = new Strings();
        $this->tables = array();
        foreach ($this->options->tables as $table) {
            $this->tables[] = $this->options->prefix.$strings->str_replace_first($this->productionDb->prefix, null, $table);
        }
        // Add extra global tables from main multisite (wpstgx_users and wpstgx_usermeta)
        $this->tables[] = $this->options->prefix.$strings->str_replace_first($this->productionDb->prefix, null, 'users');
        $this->tables[] = $this->options->prefix.$strings->str_replace_first($this->productionDb->prefix, null, 'usermeta');
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 23;
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
        if ($this->isRoot()) {
            return false;
        }

        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        // Execute step
        $stepMethodName = "step".$this->options->currentStep;
        if (!$this->{$stepMethodName}()) {
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
    protected function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep > $this->options->totalSteps ||
            !method_exists($this, "step".$this->options->currentStep)
        ;
    }

    /**
     * Check if current operation is done on the root folder or on the live DB
     * @return boolean
     */
    protected function isRoot()
    {

        // Prefix is the same as the one of live site
//      $wpdb = WPStaging::getInstance()->get( "wpdb" );
//      if( $wpdb->prefix === $this->prefix ) {
//         return true;
//      }
        // CloneName is empty
        $name = (array) $this->options->cloneDirectoryName;
        if (empty($name)) {
            return true;
        }

        // Live domain === Staging domain
        if ($this->multisiteHomeDomain.$this->options->cloneDirectoryName === $this->multisiteHomeDomain) {
            return true;
        }

        return false;
    }

    /**
     * Check if table exists
     * @param string $table
     * @return boolean
     */
    protected function isTable($table)
    {
        if ($this->db->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            $this->log("Table {$table} does not exist", Logger::TYPE_ERROR);
            return false;
        }
        return true;
    }

    /**
     * Copy wp-config.php from the staging site if it is located outside of root one level up or
     * copy default wp-config.php if production site uses bedrock or any other boilerplate solution that stores wp default config data elsewhere.
     * @return boolean
     */
    protected function step0()
    {
        $this->log("Preparing Data Step0: Copy wp-config.php file", Logger::TYPE_INFO);

        $dir = trailingslashit(dirname(ABSPATH));

        $source = $dir.'wp-config.php';

        $destination = $this->options->destinationDir.'wp-config.php';

        // Check if there is already a valid wp-config.php in root of staging site
        if ($this->isValidWpConfig($destination)) {
            $this->log("Preparing Data Step0: Found wp-config.php file in folder {$destination}", Logger::TYPE_INFO);
            return true;
        }

        // Check if there is a valid wp-config.php outside root of wp production site
        if ($this->isValidWpConfig($source)) {
            // Copy it to staging site
            if ($this->copy($source, $destination)) {
                $this->log("Preparing Data Step0: Copy wp-config.php file from source {$source} to {$destination}", Logger::TYPE_INFO);
                return true;
            }
        }

        // No valid wp-config.php found so let's copy wp stagings default wp-config.php to staging site
        $source = WPSTG_PLUGIN_DIR."Backend/helpers/wp-config.php";

        $this->log("Preparing Data Step0: Copy default wp-config.php file from source {$source} to {$destination}", Logger::TYPE_INFO);

        if ($this->copy($source, $destination)) {
            // add missing db credentials to wp-config.php
            if (!$this->alterWpConfig($destination)) {
                $this->log("Preparing Data Step0: Can not alter db credentials in wp-config.php", Logger::TYPE_INFO);
                return false;
            }
        }

        $this->log("Preparing Data Step0: Successful", Logger::TYPE_INFO);
        return true;
    }

    /**
     * Copy files with symlink support
     * @param type $source
     * @param type $destination
     * @return boolean
     */
    protected function copy($source, $destination)
    {
        // Copy symbolic link
        if (is_link($source)) {
            $this->log("Preparing Data: Symbolic link found...", Logger::TYPE_INFO);
            if (!@copy(readlink($source), $destination)) {
                $errors = error_get_last();
                $this->log("Preparing Data: Failed to copy {$source} Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
                return false;
            }
        }

        // Copy file
        if (!@copy($source, $destination)) {
            $errors = error_get_last();
            $this->log("Preparing Data Step0: Failed to copy {$source}! Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Make sure wp-config.php contains correct db credentials
     * @param type $source
     * @return boolean
     */
    protected function alterWpConfig($source)
    {
        $this->log("Preparing Data: Alter wp-config.php", Logger::TYPE_INFO);

        $content = file_get_contents($source);

        if (false === ($content = file_get_contents($source))) {
            return false;
        }

        $search = "// ** MySQL settings ** //";

        $replace = "// ** MySQL settings ** //\r\n
define( 'DB_NAME', '".DB_NAME."' );\r\n
/** MySQL database username */\r\n
define( 'DB_USER', '".DB_USER."' );\r\n
/** MySQL database password */\r\n
define( 'DB_PASSWORD', '".DB_PASSWORD."' );\r\n
/** MySQL hostname */\r\n
define( 'DB_HOST', '".DB_HOST."' );\r\n
/** Database Charset to use in creating database tables. */\r\n
define( 'DB_CHARSET', '".DB_CHARSET."' );\r\n
/** The Database Collate type. Don't change this if in doubt. */\r\n
define( 'DB_COLLATE', '".DB_COLLATE."' );\r\n";

        $content = str_replace($search, $replace, $content);

        if (false === @wpstg_put_contents($source, $content)) {
            $this->log("Preparing Data: Can't save wp-config.php", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Check if wp-config.php contains important constants
     * @param type $source
     * @return boolean
     */
    protected function isValidWpConfig($source)
    {

        if (!is_file($source) && !is_link($source)) {
            $this->log("Preparing Data Step0: Can not find {$source}", Logger::TYPE_INFO);
            return false;
        }

        $content = file_get_contents($source);

        if (false === ($content = file_get_contents($source))) {
            $this->log("Preparing Data Step0: Can not read {$source}", Logger::TYPE_INFO);
            return false;
        }

        // Get DB_NAME from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (empty($matches[1])) {
            $this->log("Preparing Data Step0: Can not find DB_NAME in wp-config.php", Logger::TYPE_INFO);
            return false;
        }

        // Get DB_USER from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (empty($matches[1])) {
            $this->log("Preparing Data Step0: Can not find DB_USER in wp-config.php", Logger::TYPE_INFO);
            return false;
        }

        // Get DB_PASSWORD from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (empty($matches[1])) {
            $this->log("Preparing Data Step0: Can not find DB_PASSWORD in wp-config.php", Logger::TYPE_INFO);
            return false;
        }

        // Get DB_HOST from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (empty($matches[1])) {
            $this->log("Preparing Data Step0: Can not find DB_HOST in wp-config.php", Logger::TYPE_INFO);
            return false;
        }
        return true;
    }

    /**
     * Replace "siteurl" and "home"
     * @return bool
     */
    protected function step1()
    {
        $this->log("Preparing Data Step1: Updating siteurl and homeurl in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_INFO);

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }
        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step1: Skipping");
            return true;
        }

        $this->log("Preparing Data Step1: Updating siteurl and homeurl to ".$this->getStagingSiteUrl());
        // Replace URLs
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'", $this->getStagingSiteUrl()
            )
        );



        // All good
        if ($result) {
            return true;
        }

        $this->log("Preparing Data Step1: Skip updating siteurl and homeurl in {$this->prefix}options. Probably already did! {$this->db->last_error}", Logger::TYPE_WARNING);
        return true;
    }

    /**
     * Update "wpstg_is_staging_site"
     * @return bool
     */
    protected function step2()
    {

        $this->log("Preparing Data Step2: Updating row wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            $this->log("Preparing Data Step2: Skipping");
            return true;
        }
        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step2: Skipping");
            return true;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'wpstg_is_staging_site'", "true"
            )
        );

        // No errors but no option name such as wpstg_is_staging_site
        if ('' === $this->db->last_error && 0 == $result) {
            $result = $this->db->query(
                $this->db->prepare(
                    "INSERT INTO {$this->prefix}options (option_name,option_value) VALUES ('wpstg_is_staging_site',%s)", "true"
                )
            );
        }

        // All good
        if ($result) {
            return true;
        }

        $this->log("Preparing Data Step2: Failed to update wpstg_is_staging_site in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR);
        return false;
    }

    /**
     * Update rewrite_rules
     * @return bool
     */
    protected function step3()
    {

        $this->log("Preparing Data Step3: Updating rewrite_rules in {$this->prefix}options {$this->db->last_error}");

        // Keep Permalinks
        if (isset($this->settings->keepPermalinks) && $this->settings->keepPermalinks
            === "1") {
            $this->log("Preparing Data Step3: Skipping");
            return true;
        }

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step3: Skipping");
            return true;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'rewrite_rules'", ' '
            )
        );

        // All good
        if ($result) {
            return true;
        }

        //$this->log( "Preparing Data Step3: Failed to update rewrite_rules in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR );
        return true;
    }

    /**
     * Update Table Prefix in wp_usermeta
     * @return bool
     */
    protected function step4()
    {
        $this->log("Preparing Data Step4: Updating db prefix in {$this->prefix}usermeta. ");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'usermeta')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'usermeta', $this->tables)) {
            $this->log("Preparing Data Step4: Skipping");
            return true;
        }



        // Skip - Target prefix is the same as production site prefix
        if ($this->db->prefix === $this->prefix) {
            $this->log("Preparing Data Step4: Target prefix equals production site prefix. Skipping");
            return true;
        }

        // Change the base table prefix of the main network site
        $this->debugLog("Preparing Data Step4: UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, {$this->productionDb->base_prefix}, {$this->prefix}) WHERE meta_key LIKE  {$this->productionDb->base_prefix}_%");
        $update = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s", $this->productionDb->base_prefix, $this->prefix, $this->productionDb->base_prefix."_%"
            )
        );

        if (false === $update) {
            $this->log("Preparing Data Step4: Skip updating {$this->prefix}usermeta meta_key database base_prefix; {$this->db->last_error}", Logger::TYPE_INFO);
        }

        // Change the table prefix of the child network site
        $this->debugLog("Preparing Data Step4: UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, {$this->db->prefix}, {$this->prefix}) WHERE meta_key LIKE  {$this->db->prefix}_%");
        $update = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s", $this->db->prefix, $this->prefix, $this->db->prefix."_%"
            )
        );

        if (false === $update) {
            $this->log("Preparing Data Step4: Failed to update {$this->prefix}usermeta meta_key database table prefixes; {$this->db->last_error}", Logger::TYPE_ERROR);
            $this->returnException("Data Crunching Step 4: Failed to update {$this->prefix}usermeta meta_key database table prefixes; {$this->db->last_error}");
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
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step5: Updating table_prefix in {$path} to ".$this->prefix);
        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step5: Failed to update table_prefix in {$path}. Can't read contents", Logger::TYPE_ERROR);
            return false;
        }

        // Replace table prefix
        $pattern     = '/\$table_prefix\s*=\s*(.*).*/';
        $replacement = '$table_prefix = \''.$this->prefix.'\'; // Changed by WP Staging';
        $content     = preg_replace($pattern, $replacement, $content);

        if (null === $content) {
            $this->log("Preparing Data Step5: Failed to update table_prefix in {$path}. Can't read contents", Logger::TYPE_ERROR);
            return false;
        }

        // Replace URLs
        $content = str_replace($this->multisiteHomeDomain, $this->getStagingSiteUrl(), $content);

        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step5: Failed to update $table_prefix in {$path} to ".$this->prefix.". Can't save contents", Logger::TYPE_ERROR);
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
    protected function step6()
    {

        if (!$this->isSubDir()) {
            $this->debugLog("Preparing Data Step6: WP installation is not in a subdirectory! All good, skipping this step");
            return true;
        }

        $path = $this->options->destinationDir."index.php";

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step6: Failed to reset {$path} for sub directory; can't read contents", Logger::TYPE_ERROR);
            return false;
        }


        if (!preg_match("/(require(.*)wp-blog-header.php' \);)/", $content, $matches)) {
            $this->log(
                "Preparing Data Step6: Failed to reset index.php for sub directory; wp-blog-header.php is missing", Logger::TYPE_ERROR
            );
            return false;
        }
        $this->log("Preparing Data: WP installation is in a subdirectory. Progressing...");

        $pattern = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);.*/";

        $replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' ); // ".$matches[0];
        //$replace.= " // Changed by WP-Staging";



        if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
            $this->log("Preparing Data: Failed to reset index.php for sub directory; replacement failed", Logger::TYPE_ERROR);
            return false;
        }

        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data: Failed to reset index.php for sub directory; can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step6: Finished successfully");
        return true;
    }

    /**
     * Update wpstg_rmpermalinks_executed
     * @return bool
     */
    protected function step7()
    {

        $this->log("Preparing Data Step7: Updating wpstg_rmpermalinks_executed in {$this->prefix}options {$this->db->last_error}");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step7: Skipping");
            return true;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'wpstg_rmpermalinks_executed'", ' '
            )
        );

        $this->Log("Preparing Data Step7: Finished successfully");
        return true;
    }

    /**
     * Update permalink_structure
     * @return bool
     */
    protected function step8()
    {

        $this->log("Preparing Data Step8: Updating permalink_structure in {$this->prefix}options {$this->db->last_error}");

        // Keep Permalinks
        if (isset($this->settings->keepPermalinks) && $this->settings->keepPermalinks
            === "1") {
            $this->log("Preparing Data Step8: Skipping");
            return true;
        }

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step8: Skipping");
            return true;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'permalink_structure'", ' '
            )
        );

        // All good
        if ($result) {
            $this->Log("Preparing Data Step8: Finished successfully");
            return true;
        }

        $this->log("Failed to update permalink_structure in {$this->prefix}options {$this->db->last_error}", Logger::TYPE_ERROR);
        return true;
    }

    /**
     * Update blog_public option to not allow staging site to be indexed by search engines
     * @return bool
     */
    protected function step9()
    {

        $this->log("Preparing Data Step9: Set staging site to noindex");

        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step9: Skipping");
            return true;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'blog_public'", '0'
            )
        );

        // All good
        if ($result) {
            $this->Log("Preparing Data Step9: Finished successfully");
            return true;
        }

        $this->log("Can not update staging site to noindex. Possible already done!", Logger::TYPE_WARNING);
        return true;
    }

    /**
     * Update WP_HOME in wp-config.php
     * @return bool
     */
    protected function step10()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step10: Updating WP_HOME in wp-config.php to ".$this->getStagingSiteUrl());

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step10: Failed to update WP_HOME in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get WP_HOME from wp-config.php
        preg_match("/define\s*\(\s*['\"]WP_HOME['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]WP_HOME['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('WP_HOME','".$this->getStagingSiteUrl()."'); // ".$matches[1];
            //$replace .= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to update WP_HOME", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step10: WP_HOME not defined in wp-config.php. Skipping this step.");
        }

        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step10: Failed to update WP_HOME. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step 10: Finished successfully");
        return true;
    }

    /**
     * Update WP_SITEURL in wp-config.php
     * @return bool
     */
    protected function step11()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step11: Updating WP_SITEURL in wp-config.php to ".$this->getStagingSiteUrl());

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step11: Failed to update WP_SITEURL in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get WP_SITEURL from wp-config.php
        preg_match("/define\s*\(\s*['\"]WP_SITEURL['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]WP_SITEURL['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('WP_SITEURL','".$this->getStagingSiteUrl()."'); // ".$matches[1];
            //$replace .= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data Step11: Failed to update WP_SITEURL", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step11: WP_SITEURL not defined in wp-config.php. Skipping this step.");
        }


        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step11: Failed to update WP_SITEURL. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step 11: Finished successfully");
        return true;
    }

    /**
     * Update WP_ALLOW_MULTISITE constant in wp-config.php
     * @return bool
     */
    protected function step12()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step12: Updating WP_ALLOW_MULTISITE in wp-config.php to false");

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step12: Failed to update WP_ALLOW_MULTISITE in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get WP_SITEURL from wp-config.php
        preg_match("/define\s*\(\s*['\"]WP_ALLOW_MULTISITE['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]WP_ALLOW_MULTISITE['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('WP_ALLOW_MULTISITE',false); // ".$matches[1];
            //$replace .= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data Step12: Failed to update WP_ALLOW_MULTISITE", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step12: WP_ALLOW_MULTISITE not defined in wp-config.php. Skipping this step.");
        }


        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step12: Failed to update WP_ALLOW_MULTISITE. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data: Finished Step 12 successfully");
        return true;
    }

    /**
     * Update MULTISITE constant in wp-config.php
     * @return bool
     */
    protected function step13()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step13: Updating MULTISITE in wp-config.php to false");

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step13: Failed to update MULTISITE in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get WP_SITEURL from wp-config.php
        preg_match("/define\s*\(\s*['\"]MULTISITE['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]MULTISITE['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('MULTISITE',false); // ".$matches[1];
            //$replace .= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data Step13: Failed to update MULTISITE", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step13: MULTISITE not defined in wp-config.php. Skipping this step.");
        }


        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step13: Failed to update MULTISITE. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step13: Finished successfully");
        return true;
    }

    /**
     * Get active_sitewide_plugins from wp_sitemeta and active_plugins from subsite
     * Merge both arrays and copy them to the staging site into active_plugins
     */
    protected function step14()
    {


        $this->log("Data Crunching Step14: Updating active_plugins");

        if (false === $this->isTable($this->prefix.'options')) {
            $this->log('Data Crunching Step14: Fatal Error '.$this->prefix.'options does not exist');
            $this->returnException('Data Crunching Step14: Fatal Error '.$this->prefix.'options does not exist');
            return false;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step14: Skipping");
            return true;
        }

        // Get active_plugins value from sub site options table
        $active_plugins = $this->productionDb->get_var("SELECT option_value FROM {$this->productionDb->prefix}options WHERE option_name = 'active_plugins' ");

        if (!$active_plugins) {
            $this->log("Data Crunching Step14: Option active_plugins are empty ");
            $active_plugins = array();
        }
        // Get active_sitewide_plugins value from main multisite wp_sitemeta table
        $active_sitewide_plugins = $this->productionDb->get_var("SELECT meta_value FROM {$this->productionDb->base_prefix}sitemeta WHERE meta_key = 'active_sitewide_plugins' ");

        if (!$active_sitewide_plugins) {
            $this->log("Data Crunching Step14: Options {$this->productionDb->base_prefix}active_sitewide_plugins is empty ");
            $active_sitewide_plugins = array();
        }

        $active_sitewide_plugins = unserialize($active_sitewide_plugins);
        $active_plugins          = unserialize($active_plugins);

        $all_plugins = array_merge($active_plugins, array_keys($active_sitewide_plugins));

        sort($all_plugins);


        // Update active_plugins
        $update = $this->db->query(
            "UPDATE {$this->prefix}options SET option_value = '".serialize($all_plugins)."' WHERE option_name = 'active_plugins'"
        );

        if (false === $update) {
            $this->log("Data Crunching Step14: Can not update option active_plugins in {$this->prefix}options", Logger::TYPE_WARNING);
            return false;
        }

        $this->log("Data Crunching Step14: Successful!");
        return true;
    }

    /**
     * Update Table Prefix in wp_options
     * @return bool
     */
    protected function step15()
    {
        $this->log("Preparing Data Step15: Updating db prefix in {$this->prefix}options.");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step4: Skipping");
            return true;
        }


        $this->log("Updating db option_names in {$this->prefix}options. ");

        // Filter the rows below. Do not update them!
        $filters = array(
            'wp_mail_smtp',
            'wp_mail_smtp_version',
            'wp_mail_smtp_debug',
        );

        $filters = apply_filters('wpstg_data_excl_rows', $filters);

        $where = "";
        foreach ($filters as $filter) {
            $where .= " AND option_name <> '".$filter."'";
        }

        $updateOptions = $this->db->query(
            $this->db->prepare(
                "UPDATE IGNORE {$this->prefix}options SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s".$where, $this->productionDb->prefix, $this->prefix, $this->productionDb->prefix."_%"
            )
        );

        if (false === $updateOptions) {
            $this->log("Preparing Data Step15: Failed to update db option_names in {$this->prefix}options. Error: {$this->db->last_error}", Logger::TYPE_WARNING);
            //$this->returnException( "Data Crunching Step 15: Failed to update db option_names in {$this->prefix}options. Error: {$this->db->last_error}" );
            return true;
        }


        return true;
    }

    /**
     * Change upload_path in wp_options (if it is defined)
     * @return bool
     */
    protected function step16()
    {
        $this->log("Preparing Data Step16: Updating upload_path {$this->prefix}options.");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        $newUploadPath = $this->getNewUploadPath();

        if (false === $newUploadPath) {
            $this->log("Preparing Data Step16: Skipping");
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step16: Skipping");
            return true;
        }

        $error = isset($this->db->last_error) ? 'Last error: '.$this->db->last_error
                : '';

        $this->log("Updating upload_path in {$this->prefix}options. {$error}");

        $updateOptions = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'upload_path'", $newUploadPath
            )
        );

        if (false === $updateOptions) {
            $this->log("Preparing Data Step16: Failed to update upload_path in {$this->prefix}options. {$error}", Logger::TYPE_ERROR);
            return true;
        }
        $this->Log("Preparing Data: Finished Step 16 successfully");
        return true;
    }

    /**
     * Update WP_CACHE in wp-config.php
     * @return bool
     */
    protected function step17()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step17: Set WP_CACHE in wp-config.php to false");

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step17: Failed to update WP_CACHE in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get WP_CACHE from wp-config.php
        preg_match("/define\s*\(\s*['\"]WP_CACHE['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]WP_CACHE['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('WP_CACHE',false); // ".$matches[1];
            //$replace.= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to change WP_CACHE", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step17: WP_CACHE not defined in wp-config.php. Skipping this step.");
        }

        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step17: Failed to update WP_CACHE. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data: Finished Step 17 successfully");
        return true;
    }

    /**
     * Add UPLOADS constant in wp-config.php or change it to correct destination (multisite type /sites/2/)
     * @return bool
     */
    protected function step18()
    {
        $path    = $this->options->destinationDir."wp-config.php";
        $this->log("Preparing Data Step18: Update UPLOADS constant in wp-config.php");
        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step18: Failed to get UPLOADS in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }
        // Get UPLOADS from wp-config.php if there is already one
        preg_match("/define\s*\(\s*['\"]UPLOADS['\"]\s*,\s*(.*)\s*\);/", $content, $matches);
        $uploadFolder = $this->getMultisiteUploadFolder();
        if (!empty($matches[0])) {
            $pattern = "/define\s*\(\s*'UPLOADS'\s*,\s*(.*)\s*\);/";

            $replace = "define('UPLOADS', '".$uploadFolder."');";
            $this->log("Preparing Data Step18: Change UPLOADS constant in wp-config.php to {$uploadFolder}.");
            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data Step 18: Failed to change UPLOADS", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step18: UPLOADS not defined in wp-config.php. Creating new entry.");
            // Find ABSPATH and add UPLOAD constant above
            preg_match("/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/", $content, $matches);
            if (!empty($matches[0])) {
                $matches[0];
                $pattern = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";
                $replace = "define('UPLOADS', '".$uploadFolder."'); \n".
                    "if ( ! defined( 'ABSPATH' ) )";
                $this->log("Preparing Data Step18: Change UPLOADS constant in wp-config.php to {$uploadFolder}.");
                if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                    $this->log("Preparing Data Step 18: Failed to change UPLOADS", Logger::TYPE_ERROR);
                    return false;
                }
            } else {
                $this->log("Preparing Data Step 18: Can not add UPLOAD constant to wp-config.php. Can not find free position to add it.", Logger::TYPE_ERROR);
            }
        }
        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step18: Failed to update UPLOADS. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step18: Finished successfully");
        return true;
    }

    /**
     * Update database credentials in wp-config.php
     * @return bool
     */
    protected function step19()
    {
        $path = $this->options->destinationDir."wp-config.php";

        $this->log("Preparing Data Step19: Change database credentials in wp-config.php");

        if (false === ($content = file_get_contents($path))) {
            $this->log("Preparing Data Step19: Failed to update database credentials in wp-config.php. Can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }


        // Get DB_NAME from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*'DB_NAME'\s*,\s*(.*)\s*\);.*/";

            $replace = "define('DB_NAME','{$this->options->databaseDatabase}'); // ".$matches[1];
            //$replace.= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to change DB_NAME", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step19: DB_NAME not defined in wp-config.php. Skipping this step.");
        }
        // Get DB_USER from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('DB_USER','{$this->options->databaseUser}'); // ".$matches[1];
            //$replace.= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to change DB_USER", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step19: DB_USER not defined in wp-config.php. Skipping this step.");
        }
        // Get DB_PASSWORD from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('DB_PASSWORD','{$this->options->databasePassword}'); // Changed by WP Staging";
            //$replace.= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to change DB_PASSWORD", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step19: DB_PASSWORD not defined in wp-config.php. Skipping this step.");
        }
        // Get DB_HOST from wp-config.php
        preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*(.*)\s*\);/", $content, $matches);

        if (!empty($matches[1])) {
            $matches[1];

            $pattern = "/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*(.*)\s*\);.*/";

            $replace = "define('DB_HOST','{$this->options->databaseServer}'); // ".$matches[1];
            //$replace.= " // Changed by WP-Staging";

            if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
                $this->log("Preparing Data: Failed to change DB_HOST", Logger::TYPE_ERROR);
                return false;
            }
        } else {
            $this->log("Preparing Data Step19: DB_HOST not defined in wp-config.php. Skipping this step.");
        }


        if (false === @wpstg_put_contents($path, $content)) {
            $this->log("Preparing Data Step19: Failed to update database credentials in wp-config.php. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->Log("Preparing Data Step 19: Finished successfully");
        return true;
    }

    /**
     * Save hostname of parent production site in option_name wpstg_connection
     * @return boolean
     */
    protected function step20()
    {

        $table = $this->prefix.'options';

        $siteurl = get_site_url();

        $connection = json_encode(array('prodHostname' => $siteurl));

        $data = array(
            'option_name' => 'wpstg_connection',
            'option_value' => $connection
        );

        $format = array('%s', '%s');

        $result = $this->db->replace($table, $data, $format);

        if (false === $result) {
            $this->Log("Preparing Data Step20: Could not save {$siteurl} in {$table}", Logger::TYPE_ERROR);
        }
        return true;
    }

    /**
     * Add option_name wpstg_execute and set it to true
     * This option is used to determine if the staging website has not been loaded initiall for executing certain custom actions from \WPStaging\initActions()
     * @return boolean
     */
    protected function step21()
    {

        $table = $this->prefix.'options';

        // Skip - Table does not exist
        if (false === $this->isTable($table)) {
            return true;
        }


        $result = $this->db->query(
            $this->db->prepare(
                "INSERT INTO {$this->prefix}options (option_name,option_value) VALUES ('wpstg_execute',%s) ON DUPLICATE KEY UPDATE option_value = %s", "true", "true"
            )
        );

        if (false === $result) {
            $this->Log("Preparing Data Step21: Could not save wpstg_execute in {$table}", Logger::TYPE_ERROR);
        }
        return true;
    }

    /**
     * Preserve data and prevents data in wp_options from beeing cloned to staging site
     * @return bool
     */
    protected function step22()
    {
        $this->log("Preparing Data Step22: Preserve Data in ".$this->prefix."options");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'options')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'options', $this->tables)) {
            $this->log("Preparing Data Step22: Skipped");
            return true;
        }

        $sql = '';

        $preserved_option_names = array('wpstg_existing_clones_beta');

        $preserved_option_names    = apply_filters('wpstg_preserved_options_cloning', $preserved_option_names);
        $preserved_options_escaped = esc_sql($preserved_option_names);

        $preserved_options_data = array();

        // Get preserved data in wp_options tables
        $table                                           = $this->db->prefix.'options';
        $preserved_options_data[$this->prefix.'options'] = $this->db->get_results(
            sprintf(
                "SELECT * FROM `{$table}` WHERE `option_name` IN ('%s')", implode("','", $preserved_options_escaped)
            ), ARRAY_A
        );

        // Create preserved data queries for options tables
        foreach ($preserved_options_data as $key => $value) {
            if (false === empty($value)) {
                foreach ($value as $option) {
                    $sql .= $this->db->prepare(
                        "DELETE FROM `{$key}` WHERE `option_name` = %s;\n", $option['option_name']
                    );

                    $sql .= $this->db->prepare(
                        "INSERT INTO `{$key}` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s );\n", $option['option_name'], $option['option_value'], $option['autoload']
                    );
                }
            }
        }

        $this->debugLog("Preparing Data Step22: Preserve values ".json_encode($preserved_options_data), Logger::TYPE_INFO);

        $this->executeSql($sql);

        $this->log("Preparing Data Step22: Successful!");
        return true;
    }

    /**
     * Check if there is a multisite super administrator and add it to usermeta
     * @return bool
     */
    protected function step23()
    {
        $this->log("Preparing Data Step23: Add network administrators");

        // Skip - Table does not exist
        if (false === $this->isTable($this->prefix.'usermeta')) {
            return true;
        }

        // Skip - Table is not selected or updated
        if (!in_array($this->prefix.'usermeta', $this->tables)) {
            $this->log("Preparing Data Step23: Skipping");
            return true;
        }

        // Get all super admins
        $superAdmins = get_super_admins();

        // Make sure all super admins are available in usermeta with correct table prefix
        $sql = '';
        foreach ($superAdmins as $username) {

            // Get user id
            $userId = $this->productionDb->get_var("SELECT ID FROM {$this->productionDb->base_prefix}users WHERE user_login = '{$username}' ");

            // Check if user capability already exists
            $capabilityExists = $this->db->get_var("SELECT user_id FROM {$this->prefix}usermeta WHERE user_id = '{$userId}' AND meta_key = '{$this->prefix}capabilities' ");

            // Do nothing if already exists
            if (!empty($capabilityExists)) {
                continue;
            }

            // Add new capability
            $sql .= $this->db->prepare(
                "INSERT INTO `{$this->prefix}usermeta` ( `umeta_id`, `user_id`, `meta_key`, `meta_value` ) VALUES ( NULL , %s, %s, %s );\n", $userId, $this->prefix.'capabilities', serialize(array(
                'administrator' => true))
            );
        }
        if (!empty($sql)) {
            $this->executeSql($sql);
        }
        return true;
    }

    /**
     * Execute a batch of sql queries
     * @param string $sqlbatch
     */
    private function executeSql($sqlbatch)
    {
        $queries = array_filter(explode(";\n", $sqlbatch));

        foreach ($queries as $query) {
            if (false === $this->db->query($query)) {
                $this->log("Data Crunching Warning:  Can not execute query {$query}", Logger::TYPE_WARNING);
            }
        }
        return true;
    }

    /**
     * Get relative path to the uploads media folder of multisite e.g.
     * wp-content/uploads/sites/SITEID or old wordpress structure wp-content/blogs.dir/SITEID/files
     * @return boolean
     */
    protected function getMultisiteUploadFolder()
    {
        $strings = new Strings();
        // Get absolute path to uploads folder
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];
        // Get relative upload path
        $relDir  = str_replace(wpstg_replace_windows_directory_separator(ABSPATH), null, wpstg_replace_windows_directory_separator($basedir));
        return $relDir;
    }

    /**
     * Get Upload Path to staging site
     * @return boolean|string
     */
    protected function getNewUploadPath()
    {
        $uploadPath = get_option('upload_path');

        if (!$uploadPath) {
            return false;
        }

        $customSlug = str_replace(wpstg_replace_windows_directory_separator(\WPStaging\WPStaging::getWPpath()), '', wpstg_replace_windows_directory_separator($uploadPath));

        $newUploadPath = wpstg_replace_windows_directory_separator(\WPStaging\WPStaging::getWPpath()).$this->options->cloneDirectoryName.'/'.$customSlug;

        return $newUploadPath;
    }

    /**
     * Return URL to staging site
     * @return string
     */
    protected function getStagingSiteUrl()
    {

        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

        if ($this->isSubDir()) {
            return trailingslashit($this->multisiteHomeDomain).trailingslashit($this->getSubDir()).$this->options->cloneDirectoryName;
        }

        // Get the path to the main multisite without appending and trailingslash e.g. wordpress
        $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
        $url           = rtrim($this->multisiteHomeDomain, '/\\').$multisitePath.$this->options->cloneDirectoryName;

        return $url;
    }

    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    protected function isSubDir()
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home    = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        if ($home !== $siteurl) {
            return true;
        }
        return false;
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    protected function getSubDir()
    {
        $home    = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl)) {
            return '';
        }

        $dir = str_replace($home, '', $siteurl);
        return str_replace('/', '', $dir);
    }
}
