<?php

namespace WPStaging\Staging\Tasks\StagingSite\FileAdjustment;

use RuntimeException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\ThirdParty\Jetpack;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Tasks\FileAdjustmentTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;






class UpdateWpConfigConstantsTask extends FileAdjustmentTask
{
 
    const ABSPATH_REGEX = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";

 
    const FILTER_CONSTANTS_REPLACE_OR_ADD = 'wpstg_constants_replace_or_add';




    protected $directory;




    protected $jetpack;




    protected $absPath;












    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Filesystem $filesystem, Directory $directory, SiteInfo $siteInfo, Jetpack $jetpack)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls, $filesystem, $siteInfo);
        $this->directory = $directory;
        $this->jetpack   = $jetpack;
        $this->absPath   = rtrim($directory->getAbsPath(), '/');
    }




    public static function getTaskName()
    {
        return 'staging_update_wp_config_constants';
    }




    public static function getTaskTitle()
    {
        return 'Adjusting constants in the staging site `wp_config.php` file.';
    }




    public function execute()
    {
        $this->logger->info('Adjusting constants in wp-config.php file for staging site');
        if ($this->jobDataDto->getIsWpConfigExcluded()) {
            $this->logger->warning("wp-config.php is excluded by filter, skipping adjustments.");
            return $this->generateResponse();
        }

        $isWpContentOutsideAbspath = $this->siteInfo->isWpContentOutsideAbspath();
        $isExternalDatabase        = $this->jobDataDto->getUseCustomDatabase();
        $relativePluginPath        = $this->getRelativePluginsDir();
        $isDefaultPluginPath       = trim($relativePluginPath, '/') === 'wp-content/plugins';

        $replaceOrAdd = [
            "WP_LANG_DIR"         => $this->getStagingLangPath(),
            "WP_HOME"             => sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl())),
            "WP_SITEURL"          => sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl())),
            "WP_CACHE"            => 'false',
            "DISABLE_WP_CRON"     => $this->jobDataDto->getIsCronEnabled() ? 'false' : 'true',
            "WP_ENVIRONMENT_TYPE" => sprintf("'%s'", 'staging'),
            "WP_DEVELOPMENT_MODE" => sprintf("'%s'", 'all'),
            "WPSTAGING_DEV_SITE"  => 'true',
        ];

        if (!$isWpContentOutsideAbspath) {
            $replaceOrAdd["UPLOADS"] = sprintf("'%s'", $this->escapeSingleQuotes(untrailingslashit($this->jobDataDto->getStagingSiteUploads())));
 
 
            if (!$isDefaultPluginPath) {
                $replaceOrAdd["WP_PLUGIN_DIR"] = '__DIR__ . "' . $this->getRelativePluginsDir() . '"';
                $replaceOrAdd["WP_PLUGIN_URL"] = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl() . $this->getRelativePluginsDir()));
            }
        }

        if ($isExternalDatabase) {
            $replaceOrAdd['DB_HOST']     = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getDatabaseServer()));
            $replaceOrAdd['DB_USER']     = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getDatabaseUser()));
            $replaceOrAdd['DB_PASSWORD'] = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getDatabasePassword()));
            $replaceOrAdd['DB_NAME']     = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getDatabaseName()));
        }

        if ($isExternalDatabase && $this->jobDataDto->getDatabaseSsl()) {
            $replaceOrAdd['MYSQL_CLIENT_FLAGS'] = 'MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT';
        }

        $replaceOrSkip = [];
        if ($this->jobDataDto->getIsStagingNetwork()) {
            $replaceOrAdd['DOMAIN_CURRENT_SITE']  = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingNetworkDomain()));
            $replaceOrAdd['PATH_CURRENT_SITE']    = sprintf("'%s'", trailingslashit($this->escapeSingleQuotes($this->jobDataDto->getStagingNetworkPath())));
            $replaceOrAdd["WP_ALLOW_MULTISITE"]   = 'true';
            $replaceOrAdd["MULTISITE"]            = 'true';
            $replaceOrAdd["SUBDOMAIN_INSTALL"]    = is_subdomain_install() ? 'true' : 'false';
            $replaceOrAdd["SITE_ID_CURRENT_SITE"] = SITE_ID_CURRENT_SITE;
            $replaceOrAdd["BLOG_ID_CURRENT_SITE"] = BLOG_ID_CURRENT_SITE;
        } else {
 
            $replaceOrSkip["WP_ALLOW_MULTISITE"] = 'false';
            $replaceOrSkip["MULTISITE"]          = 'false';
        }

 
        $replaceOrAdd['WP_DEBUG']         = 'false';
        $replaceOrAdd['WP_DEBUG_LOG']     = 'false';
        $replaceOrAdd['WP_DEBUG_DISPLAY'] = 'false';
        if ($this->jetpack->isJetpackActive()) {
            $replaceOrAdd[Jetpack::STAGING_MODE_CONST] = 'true';
        }

        $delete = [];

 
        if ('wp-content' === trim($this->getRelativeWpContentDir(), '/')) {
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

        if ($isWpContentOutsideAbspath && !$this->siteInfo->isFlywheel()) {
            $delete[] = "WP_CONTENT_DIR";
            $delete[] = "WP_CONTENT_URL";
        }








        $replaceOrAdd = (array)apply_filters(self::FILTER_CONSTANTS_REPLACE_OR_ADD, $replaceOrAdd);

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


        return $this->generateResponse();
    }




    protected function getRelativeWpContentDir(): string
    {
        return rtrim(str_replace($this->absPath, '', $this->directory->getWpContentDirectory()), '/');
    }




    protected function getRelativePluginsDir(): string
    {
        return rtrim(str_replace($this->absPath, '', $this->directory->getPluginsDirectory()), '/');
    }




    protected function getStagingLangPath(): string
    {
        if ($this->siteInfo->isWpContentOutsideAbspath()) {
            return '__DIR__ . "/wp-content/languages"';
        }

        return sprintf("__DIR__ . '/%s/languages'", $this->escapeSingleQuotes(trim($this->getRelativeWpContentDir(), '/')));
    }








    protected function replaceExistingDefinition(string $constant, string $content, string $newDefinition): string
    {
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            $this->logger->debug("Constant " . $constant . " not defined in wp-config.php.");
            return $content;
        }

        $replace = sprintf("define('%s', %s);", $constant, $newDefinition);

 
        $replacementEscapedCharacter = addcslashes($replace, '\\$');

        $content = preg_replace([$pattern], $replacementEscapedCharacter, $content);

        if ($content === null) {
            throw new RuntimeException("Failed to change " . $constant);
        }

        $this->logger->info("Updated: " . $constant . ".");
        return $content;
    }






    protected function hasDefinition(string $constant, string $content): bool
    {
        preg_match($this->getDefineRegex($constant), $content, $matches);

        return !empty($matches[0]);
    }








    protected function addDefinition(string $constant, string $content, string $newDefinition): string
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

        if (($content = preg_replace(self::ABSPATH_REGEX, $replacementEscaped, $content)) === null) {
            throw new RuntimeException("Failed to update constant " . $constant);
        }

        $this->logger->info("Added constant: " . $constant);
        return $content;
    }







    protected function deleteDefinition(string $constant, string $content): string
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

        $this->logger->info("Deleted: " . $constant);
        return $content;
    }







    protected function replaceOrAddDefinition(string $constant, string $content, string $newDefinition)
    {
        if (!$this->hasDefinition($constant, $content)) {
            $this->logger->debug("Constant " . $constant . " not defined in wp-config.php. Creating new entry.");
            return $this->addDefinition($constant, $content, $newDefinition);
        }

        return $this->replaceExistingDefinition($constant, $content, $newDefinition);
    }







    protected function replaceOrSkipDefinition(string $constant, string $content, string $newDefinition)
    {
        if (!$this->hasDefinition($constant, $content)) {
            $this->logger->info("Skipping: " . $constant . " not defined in wp-config.php.");
            return $content;
        }

        return $this->replaceExistingDefinition($constant, $content, $newDefinition);
    }






    private function abspathConstantExists(string $content): bool
    {
        preg_match(self::ABSPATH_REGEX, $content, $matches);
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









    private function escapeSingleQuotes(string $string): string
    {
        return str_replace("'", "\'", $string);
    }
}
