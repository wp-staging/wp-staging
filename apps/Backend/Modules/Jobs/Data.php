<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

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

        $this->prefix   = "wpstg{$this->options->cloneNumber}_";

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
            "step"          => $this->options->totalSteps
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
     * Replace "siteurl"
     * @return bool
     */
    protected function step1()
    {
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'",
                get_home_url() . '/' . $this->options->cloneDirectoryName
            )
        );

        // All good
        if ($result)
        {
            return true;
        }

        // TODO log $this->db->last_error
        return false;
    }

    /**
     * Update "wpstg_is_staging_site"
     * @return bool
     */
    protected function step2()
    {
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

        // TODO log $this->db->last_error
        return false;
    }

    /**
     * Update rewrite_rules
     * @return bool
     */
    protected function step3()
    {
        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_value = %s WHERE option_name = 'rewrite_rules'",
                ''
            )
        );

        // All good
        if ($result)
        {
            return true;
        }

        // TODO log $this->db->last_error
        return false;
    }

    /**
     * Update Table Prefix in meta_keys
     * @return bool
     */
    protected function step4()
    {
        $resultOptions = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s",
                $this->db->prefix,
                $this->prefix,
                $this->db->prefix . "_%"
            )
        );

        if (!$resultOptions)
        {
            // TODO log $this->db->last_error
            return false;
        }

        $resultUserMeta = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->prefix}options SET option_name =replace(option_name, %s, %s) WHERE option_name LIKE %s",
                $this->db->prefix,
                $this->prefix,
                $this->db->prefix . "_%"
            )
        );

        if (!$resultUserMeta)
        {
            // TODO log $this->db->last_error
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
        $path = get_home_path() . $this->options->cloneDirectoryName . "/wp-config.php";

        if (false === ($content = file_get_contents($path)))
        {
            // TODO log
            return false;
        }

        // Replace table prefix
        $content = str_replace('$table_prefix', '$table_prefix = \'' . $this->prefix . '\';//', $content);

        // Replace URLs
        $content = str_replace(get_home_url(), get_home_url() . $this->options->cloneDirectoryName, $content);

        if (false === @file_put_contents($path, $content))
        {
            // TODO log
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
            return true;
        }

        $path = get_home_path() . $this->options->cloneDirectoryName . "/index.php";

        if (false === ($content = file_get_contents($path)))
        {
            // TODO log
            return false;
        }


        if (!preg_match("/(require(.*)wp-blog-header.php' \);)/", $content, $matches))
        {
            // TODO log
            return false;
        }

        $pattern = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);/";

        $replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' ); // " . $matches[0];
        $replace.= " // Changed by WP-Staging";

        if (null === preg_replace($pattern, $replace, $content))
        {
            // TODO log
            return false;
        }

        if (false === @file_put_contents($path, $content))
        {
            // TODO log
            return false;
        }

        return true;
    }
}