<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\ThirdParty\Jetpack;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use RuntimeException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\UpdateWpConfigConstantsTask;
use WPStaging\Framework\SiteInfo;

class UpdateWpConfigConstants extends FileCloningService
{






    const FILTER_PRESERVE_DEBUG_CONSTANTS = 'wpstg.cloning.preserve_debug_constants';

 
    protected $abspathRegex = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";




    protected function internalExecute(): bool
    {
        $this->log("Updating constants in wp-config.php");

        if ($this->isExcludedWpConfig()) {
            $this->log("Excluded: wp-config.php is excluded by filter");
            return true;
        }

        $isWpContentOutsideAbspath = $this->isWpContentOutsideAbspath();
        $isUploadsOutsideAbspath   = $this->isUploadsOutsideAbspath();
        $relativePluginPath        = $this->getRelativePluginPath();
        $isDefaultPluginPath       = trim($relativePluginPath, '/') === 'wp-content/plugins';

        $replaceOrAdd = [
            "WP_LANG_DIR"         => $this->getStagingLangPath(),
            "WP_HOME"             => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl())),
            "WP_SITEURL"          => sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl())),
            "WP_CACHE"            => 'false',
            "DISABLE_WP_CRON"     => empty($this->dto->getJob()->getOptions()->isCronEnabled) ? 'true' : 'false',
            "WP_ENVIRONMENT_TYPE" => sprintf("'%s'", 'staging'),
            "WP_DEVELOPMENT_MODE" => sprintf("'%s'", 'all'),
            "WPSTAGING_DEV_SITE"  => 'true',
        ];

        if (!$isWpContentOutsideAbspath) {
            $replaceOrAdd["UPLOADS"] = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getUploadFolder()));
 
 
            if (!$isDefaultPluginPath) {
                $replaceOrAdd["WP_PLUGIN_DIR"] = '__DIR__ . "' . $relativePluginPath . '"';
                $replaceOrAdd["WP_PLUGIN_URL"] = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteUrl() . $relativePluginPath));
            }
        }

        if ($this->dto->isExternal()) {
            $replaceOrAdd['DB_HOST']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseHost()));
            $replaceOrAdd['DB_USER']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseUser()));
            $replaceOrAdd['DB_PASSWORD'] = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabasePassword()));
            $replaceOrAdd['DB_NAME']     = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getExternalDatabaseName()));
        }

        if ($this->dto->isExternal() && $this->dto->getExternalDatabaseSsl()) {
            $replaceOrAdd['MYSQL_CLIENT_FLAGS'] = 'MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT';
        }

        $replaceOrSkip = [];
        if ($this->isNetworkClone()) {
            $replaceOrAdd['DOMAIN_CURRENT_SITE']  = sprintf("'%s'", $this->escapeSingleQuotes($this->dto->getStagingSiteDomain()));
            $replaceOrAdd['PATH_CURRENT_SITE']    = sprintf("'%s'", trailingslashit($this->escapeSingleQuotes($this->dto->getStagingSitePath())));
            $replaceOrAdd["WP_ALLOW_MULTISITE"]   = 'true';
            $replaceOrAdd["MULTISITE"]            = 'true';
            $replaceOrAdd["SUBDOMAIN_INSTALL"]    = is_subdomain_install() ? 'true' : 'false';
            $replaceOrAdd["SITE_ID_CURRENT_SITE"] = SITE_ID_CURRENT_SITE;
            $replaceOrAdd["BLOG_ID_CURRENT_SITE"] = BLOG_ID_CURRENT_SITE;
        } else {
 
            $replaceOrSkip["WP_ALLOW_MULTISITE"] = 'false';
            $replaceOrSkip["MULTISITE"]          = 'false';
        }

 
        if (!apply_filters(self::FILTER_PRESERVE_DEBUG_CONSTANTS, false)) {
            $replaceOrAdd['WP_DEBUG']         = 'false';
            $replaceOrAdd['WP_DEBUG_LOG']     = 'false';
            $replaceOrAdd['WP_DEBUG_DISPLAY'] = 'false';
        }

 
        $jetpackHelper = WPStaging::make(Jetpack::class);
        if ($jetpackHelper->isJetpackActive()) {
            $replaceOrAdd[Jetpack::STAGING_MODE_CONST] = 'true';
        }

        $delete = [];

 
        if ('wp-content' === trim($this->getRelativeWpContentDir(), '/')) {
            $delete[] = "WP_CONTENT_DIR";
            $delete[] = "WP_CONTENT_URL";
        }

        if ($isUploadsOutsideAbspath) {
            $delete[] = "UPLOADS";
            $delete[] = "WP_CONTENT_DIR";
            $delete[] = "WP_CONTENT_URL";
        }

        if ($isWpContentOutsideAbspath) {
            $delete[] = "UPLOADS";
            $delete[] = "WP_PLUGIN_DIR";
            $delete[] = "WP_PLUGIN_URL";
            $delete[] = "WPMU_PLUGIN_DIR";
            $delete[] = "WPMU_PLUGIN_URL";
        }

        if ($isDefaultPluginPath) {
            $delete[] = "WP_PLUGIN_DIR";
            $delete[] = "WP_PLUGIN_URL";
            $delete[] = "WPMU_PLUGIN_DIR";
            $delete[] = "WPMU_PLUGIN_URL";
        }

        if ($isWpContentOutsideAbspath && !$this->isFlywheelHosting()) {
            $delete[] = "WP_CONTENT_DIR";
            $delete[] = "WP_CONTENT_URL";
        }

        if ($this->dto->isExternal() && !$this->dto->getExternalDatabaseSsl()) {
            $delete[] = "MYSQL_CLIENT_FLAGS";
        }








        $replaceOrAdd = (array)apply_filters(UpdateWpConfigConstantsTask::FILTER_CONSTANTS_REPLACE_OR_ADD, $replaceOrAdd);

        $content = $this->readWpConfig();
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




    protected function isWpContentOutsideAbspath(): bool
    {
 
        $siteInfo = WPStaging::make(SiteInfo::class);

        return $siteInfo->isWpContentOutsideAbspath();
    }




    protected function isUploadsOutsideAbspath(): bool
    {
 
        $siteInfo = WPStaging::make(SiteInfo::class);

        return $siteInfo->isUploadsOutsideAbspath();
    }




    protected function isFlywheelHosting(): bool
    {
 
        $siteInfo = WPStaging::make(SiteInfo::class);

        return $siteInfo->isFlywheel();
    }




    protected function getRelativeWpContentDir(): string
    {
 
        $directory = WPStaging::make(Directory::class);

        return str_replace($directory->getAbsPath(), '', $directory->getWpContentDirectory());
    }




    protected function getRelativePluginPath(): string
    {
        return (new WpDefaultDirectories())->getRelativePluginPath(SlashMode::LEADING_SLASH);
    }




    protected function getStagingLangPath(): string
    {
        if ($this->isWpContentOutsideAbspath()) {
            return '__DIR__ . "/wp-content/languages"';
        }

        return sprintf("__DIR__ . '/%s/languages'", $this->escapeSingleQuotes(trim($this->getRelativeWpContentDir(), '/')));
    }









    private function escapeSingleQuotes(string $string): string
    {
        return str_replace("'", "\'", $string);
    }








    protected function replaceExistingDefinition(string $constant, string $content, string $newDefinition)
    {
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            return false;
        }

        $replace = sprintf("define('%s', %s);", $constant, $newDefinition);

 
        $replacementEscapedCharacter = addcslashes($replace, '\\$');

        $content = preg_replace([$pattern], $replacementEscapedCharacter, $content);

        if ($content === null) {
            throw new RuntimeException("Failed to change " . $constant);
        }

        $this->log("Updated: " . $constant);
        return $content;
    }








    protected function addDefinition(string $constant, string $content, string $newDefinition)
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

 
        $replacementEscaped = addcslashes($replacement, '\\$');

        if (($content = preg_replace([$this->abspathRegex], $replacementEscaped, $content)) === null) {
            throw new RuntimeException("Failed to update constant " . $constant);
        }

        $this->log("Added constant: " . $constant);
        return $content;
    }





    private function abspathConstantExists(string $content): bool
    {
        preg_match($this->abspathRegex, $content, $matches);
        if (empty($matches[0])) {
            return false;
        }

        return true;
    }











    private function maybeAddDefinedCondition(string $constant): bool
    {
        if ($constant === 'WP_ENVIRONMENT_TYPE' || $constant === 'WPSTAGING_DEV_SITE') {
            return true;
        }

 
        if ($constant === 'WP_SITEURL' || $constant === 'WP_HOME') {
            return true;
        }

        return false;
    }







    protected function deleteDefinition(string $constant, string $content)
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







    protected function replaceOrAddDefinition(string $constant, string $content, string $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->debugLog("Constant " . $constant . " not defined in wp-config.php. Creating new entry.");
            $newContent = $this->addDefinition($constant, $content, $newDefinition);
        }

        return $newContent;
    }







    protected function replaceOrSkipDefinition(string $constant, string $content, string $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->log("Skipping: " . $constant . " not defined in wp-config.php.");
            return $content;
        }

        return $newContent;
    }
}
