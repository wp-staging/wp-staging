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
            "UPLOADS"             => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getUploadFolder())),
            "WP_PLUGIN_DIR"       => '__DIR__ . "/wp-content/plugins"',
            "WP_LANG_DIR"         => '__DIR__ . "/wp-content/languages"',
            "WP_HOME"             => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl())),
            "WP_SITEURL"          => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl())),
            "WP_CACHE"            => 'false',
            "WP_ENVIRONMENT_TYPE" => sprintf("'%s'", 'staging'),
        ];
        if ($this->dto->isExternal()) {
            $replaceOrAdd['DB_HOST']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseHost()));
            $replaceOrAdd['DB_USER']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseUser()));
            $replaceOrAdd['DB_PASSWORD'] = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabasePassword()));
            $replaceOrAdd['DB_NAME']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseName()));
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

        /**
         * Allows to filter the constants to be replaced/added.
         *
         * @param array $replaceOrAdd The array of constants to be replaced in the staging site's wp-config.php
         *
         * @return array The array of constants.
         */
        $replaceOrAdd = (array)apply_filters('wpstg_constants_replace_or_add', $replaceOrAdd);

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
     * Helper function to return a string with single
     * quotes escaped.
     *
     * @param string $string
     *
     * @return string
     */
    private function escapeSingleQuotes($string)
    {
        return str_replace("'", "\'", $string);
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

        $replace = sprintf("define('%s', %s);", $constant, $newDefinition);

        // escaping dollar sign in the value
        $replacementEscapedCharacter = addcslashes($replace, '\\$');

        $content = preg_replace([$pattern], $replacementEscapedCharacter, $content);

        if ($content === null) {
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

             // escaping dollar sign in the value
            $replaceEscaped = addcslashes($replace, '\\$');

            if (($content = preg_replace([$this->abspathRegex], $replaceEscaped, $content)) === null) {
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
        if (($content = preg_replace([$pattern], $replace, $content)) === null) {
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
