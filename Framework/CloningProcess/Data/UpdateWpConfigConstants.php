<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Support\ThirdParty\Jetpack;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use RuntimeException;

class UpdateWpConfigConstants extends FileCloningService
{
    protected $abspathRegex = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";

    /**
     * @return bool
     */
    protected function internalExecute()
    {
        $this->log("Updating constants in wp-config.php");

        if ($this->isExcludedWpConfig()) {
            $this->log("Excluded: wp-config.php is excluded by filter");
            return true;
        }

        $content = $this->readWpConfig();

        $replaceOrAdd = [
            "UPLOADS"             => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getUploadFolder())),
            "WP_PLUGIN_DIR"       => '__DIR__ . "' . (new WpDefaultDirectories())->getRelativePluginPath(SlashMode::LEADING_SLASH) . '"',
            "WP_PLUGIN_URL"       => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl() . (new WpDefaultDirectories())->getRelativePluginPath(SlashMode::LEADING_SLASH))),
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

            if ($this->dto->getExternalDatabaseSsl()) {
                $replaceOrAdd['MYSQL_CLIENT_FLAGS'] = 'MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT';
            }
        }

        $replaceOrSkip = [];
        if ($this->isNetworkClone()) {
            $replaceOrAdd['DOMAIN_CURRENT_SITE'] = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteDomain()));
            $replaceOrAdd['PATH_CURRENT_SITE']   = sprintf("'%s'", trailingslashit($this->escapeSingleQuotes($this->dto->getStagingSitePath())));
            $replaceOrSkip["WP_ALLOW_MULTISITE"] = 'true';
            $replaceOrSkip["MULTISITE"]          = 'true';
        } else {
            //It's OK to attempt replacing multi-site constants even in single-site jobs as they will not be present in a single-site wp-config.php
            $replaceOrSkip["WP_ALLOW_MULTISITE"] = 'false';
            $replaceOrSkip["MULTISITE"]          = 'false';
        }

        /** @var  $jetpackHelper */
        $jetpackHelper = WPStaging::make(Jetpack::class);
        if ($jetpackHelper->isJetpackActive()) {
            $replaceOrAdd[Jetpack::STAGING_MODE_CONST] = 'true';
        }

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
     * @throws RuntimeException
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
            throw new RuntimeException("Failed to change " . $constant);
        }

        $this->log("Updated: " . $constant);
        return $content;
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return string
     * @throws RuntimeException
     */
    protected function addDefinition($constant, $content, $newDefinition)
    {
        if (!$this->abspathConstantExists($content)) {
            throw new RuntimeException("Can not add " . $constant . " constant to wp-config.php. Can not find ABSPATH constant.");
        }

        if ($this->maybeAddDefinedCondition($constant)) {
            $replacement = <<<EOT
if ( ! defined( '$constant' ) ) {
    define('$constant', $newDefinition);
}
if ( ! defined( 'ABSPATH' ) )
EOT;
        } else {
            $replacement = <<<EOT
define('$constant', $newDefinition);
if ( ! defined( 'ABSPATH' ) )
EOT;
        }

        // escaping dollar sign
        $replacementEscaped = addcslashes($replacement, '\\$');

        if (($content = preg_replace([$this->abspathRegex], $replacementEscaped, $content)) === null) {
            throw new RuntimeException("Failed to update constant " . $constant);
        }

        $this->log("Added constant:" . $constant);
        return $content;
    }

    /**
     * @param $content string
     * @return bool
     */
    private function abspathConstantExists($content)
    {
        preg_match($this->abspathRegex, $content, $matches);
        if (empty($matches[0])) {
            return false;
        }
        return true;
    }

    /**
     * Treat certain constants differently because other plugins or themes could declare this constant outside wp-config.php.
     * E.g. Local by Flywheel does.
     * We don't add the defined condition to all constants because it would make it
     * difficult to debug and find out why a staging site breaks if client site overwrites a default constant outside wp-config.php.
     * So we treat some constants like WP_ENVIRONMENT_TYPE differently.
     *
     * @param $constant string Name of the constant
     * @return bool
     */
    private function maybeAddDefinedCondition($constant)
    {
        if ($constant === 'WP_ENVIRONMENT_TYPE') {
            return true;
        }
        return false;
    }

    /**
     * @param string $constant
     * @param string $content
     * @return string|array|null
     * @throws RuntimeException
     */
    protected function deleteDefinition($constant, $content)
    {
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            return $content;
        }
        $replace = "";
        if (($content = preg_replace([$pattern], $replace, $content)) === null) {
            throw new RuntimeException("Failed to change " . $constant);
        }
        $this->log("Deleted: " . $constant);
        return $content;
    }

    /**
     * @param $constant
     * @param $content
     * @param $newDefinition
     * @return string
     */
    protected function replaceOrAddDefinition($constant, $content, $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->debugLog("Constant" . $constant . " not defined in wp-config.php. Creating new entry.");
            $newContent = $this->addDefinition($constant, $content, $newDefinition);
        }
        return $newContent;
    }

    /**
     * @param $constant
     * @param $content
     * @param $newDefinition
     * @return bool|string|string[]|null
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
