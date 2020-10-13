<?php


namespace WPStaging\Framework\CloningProcess\Data;

class UpdateWpConfigConstants extends FileCloningService
{
    protected $abspathRegex = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";

    /**
     * @return bool
     * @throws \Exception
     */
    protected function internalExecute()
    {
        $this->log("Updating constants in wp-config.php");
        $content = $this->readWpConfig();

        $replaceOrAdd = [
            "UPLOADS" => '"' . $this->dto->getUploadFolder() . '"',
            "WP_PLUGIN_DIR" => 'dirname(__FILE__) . "/wp-content/plugins"',
            "WP_LANG_DIR" => 'dirname(__FILE__) . "/wp-content/languages"',
            // Below disabled. It can lead to deleting plugins after login to admin dashboard
            //"WP_TEMP_DIR" => 'dirname(__FILE__) . "/wp-content/temp"',
            "WP_HOME" => '"' . $this->dto->getStagingSiteUrl() . '"',
            "WP_SITEURL" => '"' . $this->dto->getStagingSiteUrl() . '"',
            "WP_CACHE" => 'false',
        ];
        if ($this->dto->isExternal()) {
            $replaceOrAdd['DB_HOST'] = '"' . $this->dto->getExternalDatabaseHost() . '"';
            $replaceOrAdd['DB_USER'] = '"' . $this->dto->getExternalDatabaseUser() . '"';
            $replaceOrAdd['DB_PASSWORD'] = '"' . $this->dto->getExternalDatabasePassword() . '"';
            $replaceOrAdd['DB_NAME'] = '"' . $this->dto->getExternalDatabaseName() . '"';
        }
        //It's OK to attempt replacing multi-site constants even in single-site jobs as they will not be present in a single-site wp-config.php
        $replaceOrSkip = [
            "WP_ALLOW_MULTISITE" => 'false',
            "MULTISITE" => 'false',
        ];
        //In the old job structure, these were deleted for the single-site non-external job only. Now they are deleted everywhere
        $delete = [
            "WP_CONTENT_DIR",
            "WP_CONTENT_URL",
        ];
        foreach ($replaceOrAdd as $constant => $newDefinition) {
            $content = $this->replaceOrAddDefinition($constant, $content, $newDefinition);
        }
        foreach ($replaceOrSkip as $constant => $newDefinition) {
            $content = $this->replaceOrSkipDefinition($constant, $content, $newDefinition);
        }
        foreach ($delete as $constant) {
            $content = $this->deleteDefinition($constant, $content);
        }
        $this->writeWpConfig($content);
        //$this->log("Done");
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
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            return false;
        }
        $replace = "define('" . $constant . "', " . $newDefinition . ");";
        if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
            throw new \RuntimeException("Failed to change " . $constant);
        }
        $this->log("Updated: " . $constant);
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
            $replace = "define('" . $constant . "', " . $newDefinition . "); \n" .
                "if ( ! defined( 'ABSPATH' ) )";
            if (null === ($content = preg_replace(array($this->abspathRegex), $replace, $content))) {
                throw new \RuntimeException("Failed to change " . $constant);
            }
        } else {
            throw new \RuntimeException("Can not add " . $constant . " constant to wp-config.php. Can not find free position to add it.");
        }
        $this->log("Added:" . $constant);
        return $content;
    }

    /**
     * @param string $constant
     * @param string $content
     * @return bool|string|string[]|null
     * @throws \Exception
     */
    protected function deleteDefinition($constant, $content)
    {
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            //$this->log($constant . " not found in wp-config.php. Skipping");
            return $content;
        }
        $replace = "";
        if (null === ($content = preg_replace(array($pattern), $replace, $content))) {
            throw new \RuntimeException("Failed to change " . $constant);
        }
        $this->log("Deleted: " . $constant);
        return $content;
    }

    /**
     * @param $constant
     * @param $content
     * @param $newDefinition
     * @return bool|string|string[]|null
     * @throws \Exception
     */
    protected function replaceOrAddDefinition($constant, $content, $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->debugLog($constant . " not defined in wp-config.php. Creating new entry.");
            $newContent = $this->addDefinition($constant, $content, $newDefinition);
        }
        return $newContent;
    }

    /**
     * @param $constant
     * @param $content
     * @param $newDefinition
     * @return bool|string|string[]|null
     * @throws \Exception
     */
    protected function replaceOrSkipDefinition($constant, $content, $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->log("Skipping: " . $constant . " not defined in wp-config.php.");
            return $content;
        }
        return $newContent;
    }
}
