<?php


namespace WPStaging\Service\CloningProcess;

use WPStaging\Backend\Modules\Jobs\JobExecutable;
use WPStaging\Utils\Logger;

class WpConfigEditor
{
    protected $abspathRegex = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";
    protected $uploadConstant = "UPLOADS";
    protected $pluginConstant = "WP_PLUGIN_DIR";
    protected $langConstant = "WP_LANG_DIR";
    protected $tempConstant = "WP_TEMP_DIR";

    /**
     * @var JobExecutable
     */
    protected $job;

    /**
     * @var integer
     */
    protected $stepNumber;

    /**
     * @var string
     */
    protected $uploadFolder;

    /**
     * WpConfigEditor constructor.
     * @param JobExecutable $job
     * @param int $stepNumber
     * @param string $uploadFolder
     */
    public function __construct(JobExecutable $job, $stepNumber, $uploadFolder)
    {
        $this->job = $job;
        $this->stepNumber = $stepNumber;
        $this->uploadFolder = $uploadFolder;
    }

    /**
     * @return bool
     */
    public function replaceOrAddDefinitions()
    {
        $path = $this->job->getOptions()->destinationDir . "wp-config.php";
        $this->log("Updating constants in wp-config.php");
        if (false === ($content = file_get_contents($path))) {
            $this->log("Error - can't read wp-config.php", Logger::TYPE_ERROR);
            return false;
        }

        $constants = [
            $this->uploadConstant => $this->uploadFolder,
            $this->pluginConstant => 'wp-content/plugins',
            $this->langConstant => 'wp-content/languages',
            $this->tempConstant => 'wp-content/temp'
        ];
        foreach ($constants as $constant => $newDefinition) {
            $content = $this->replaceOrAddDefinition($constant, $content, $newDefinition);
            if (!$content) {
                return false;
            }
        }

        if (false === wpstg_put_contents($path, $content)) {
            $this->log("Failed to update constants. Can't save contents", Logger::TYPE_ERROR);
            return false;
        }
        $this->log("Finished successfully");
        return true;
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return bool|string|string[]|null
     * @throws \Exception
     */
    protected function replaceExistingDefinition($constant, $content, $newDefinition)
    {
        $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(.*)\s*\);/";
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            return false;
        }
        $replace = "define('" . $constant . "', '" . $newDefinition . "');";
        if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
            throw new \RuntimeException("Failed to change " . $constant);
        }
        return $content;
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return string|string[]|null
     * @throws \Exception
     */
    protected function addDefinition($constant, $content, $newDefinition)
    {
        preg_match($this->abspathRegex, $content, $matches);
        if (!empty($matches[0])) {
            $matches[0];
            $replace = "define('" . $constant . "', '" . $newDefinition . "'); \n" .
                "if ( ! defined( 'ABSPATH' ) )";
            if (null === ($content = preg_replace(array($this->abspathRegex), $replace, $content))) {
                throw new \RuntimeException("Failed to change " . $constant);
            }
        } else {
            $this->log(
                "Can not add " . $constant . " constant to wp-config.php. Can not find free position to add it.",
                Logger::TYPE_ERROR
            );
        }
        return $content;
    }

    protected function replaceOrAddDefinition($constant, $content, $newDefinition)
    {
        try {
            $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
            if (!$newContent) {
                $this->log($constant . " not defined in wp-config.php. Creating new entry.");
                $newContent = $this->addDefinition($constant, $content, $newDefinition);
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage(), Logger::TYPE_ERROR);
            return false;
        }
        return $newContent;
    }

    protected function log($message, $type = Logger::TYPE_INFO)
    {
        $this->job->log("Preparing Data Step" . $this->stepNumber . ": " . $message, $type);
    }
}