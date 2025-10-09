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

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\UpdateWpConfigConstants
 */
class UpdateWpConfigConstantsTask extends FileAdjustmentTask
{
    /** @var string */
    const ABSPATH_REGEX = "/if\s*\(\s*\s*!\s*defined\s*\(\s*['\"]ABSPATH['\"]\s*(.*)\s*\)\s*\)/";

    /**
     * @var Directory
     */
    protected $directory;

    /**
     * @var Jetpack
     */
    protected $jetpack;

    /**
     * @var string
     */
    protected $absPath;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Urls $urls
     * @param Filesystem $filesystem
     * @param Directory $directory
     * @param SiteInfo $siteInfo
     * @param Jetpack $jetpack
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Filesystem $filesystem, Directory $directory, SiteInfo $siteInfo, Jetpack $jetpack)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls, $filesystem, $siteInfo);
        $this->directory = $directory;
        $this->jetpack   = $jetpack;
        $this->absPath   = rtrim($directory->getAbsPath(), '/');
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_wp_config_constants';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Adjusting constants in the staging site `wp_config.php` file.';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->logger->info('Adjusting constants in wp-config.php file for staging site');
        if ($this->jobDataDto->getIsWpConfigExcluded()) {
            $this->logger->warning("wp-config.php is excluded by filter, skipping adjustments.");
            return $this->generateResponse();
        }

        $isWpContentOutsideAbspath = $this->siteInfo->isWpContentOutsideAbspath();
        $isExternalDatabase        = $this->jobDataDto->getUseCustomDatabase();

        $replaceOrAdd = [
            "WP_LANG_DIR"         => $this->getStagingLangPath(),
            "WP_HOME"             => sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl())),
            "WP_SITEURL"          => sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl())),
            "WP_CACHE"            => 'false',
            "DISABLE_WP_CRON"     => $this->jobDataDto->getCronDisabled() ? 'true' : 'false',
            "WP_ENVIRONMENT_TYPE" => sprintf("'%s'", 'staging'),
            "WP_DEVELOPMENT_MODE" => sprintf("'%s'", 'all'),
            "WPSTAGING_DEV_SITE"  => 'true',
        ];

        if (!$isWpContentOutsideAbspath) {
            $replaceOrAdd["UPLOADS"]       = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUploads()));
            $replaceOrAdd["WP_PLUGIN_DIR"] = '__DIR__ . "' . $this->getRelativePluginsDir() . '"';
            $replaceOrAdd["WP_PLUGIN_URL"] = sprintf("'%s'", $this->escapeSingleQuotes($this->jobDataDto->getStagingSiteUrl() . $this->getRelativePluginsDir()));
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
            //It's OK to attempt replacing multi-site constants even in single-site jobs as they will not be present in a single-site wp-config.php
            $replaceOrSkip["WP_ALLOW_MULTISITE"] = 'false';
            $replaceOrSkip["MULTISITE"]          = 'false';
        }

        // turn off debug constants on staging site
        $replaceOrAdd['WP_DEBUG']         = 'false';
        $replaceOrAdd['WP_DEBUG_LOG']     = 'false';
        $replaceOrAdd['WP_DEBUG_DISPLAY'] = 'false';
        if ($this->jetpack->isJetpackActive()) {
            $replaceOrAdd[Jetpack::STAGING_MODE_CONST] = 'true';
        }

        $delete = [];

        // Don't delete custom wp-content path constants
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

        if ($isWpContentOutsideAbspath && !$this->siteInfo->isFlywheel()) {
            $delete[] = "WP_CONTENT_DIR";
            $delete[] = "WP_CONTENT_URL";
        }

        /**
         * Allows to filter the constants to be replaced/added.
         *
         * @param array $replaceOrAdd The array of constants to be replaced in the staging site's wp-config.php
         *
         * @return array The array of constants.
         */
        $replaceOrAdd = (array)apply_filters('wpstg_constants_replace_or_add', $replaceOrAdd);

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

    /**
     * @return string
     */
    protected function getRelativeWpContentDir(): string
    {
        return rtrim(str_replace($this->absPath, '', $this->directory->getWpContentDirectory()), '/');
    }

    /**
     * @return string
     */
    protected function getRelativePluginsDir(): string
    {
        return rtrim(str_replace($this->absPath, '', $this->directory->getPluginsDirectory()), '/');
    }

    /**
     * @return string
     */
    protected function getStagingLangPath(): string
    {
        if ($this->siteInfo->isWpContentOutsideAbspath()) {
            return '__DIR__ . "/wp-content/languages"';
        }

        return sprintf("__DIR__ . '/%s/languages'", $this->escapeSingleQuotes(trim($this->getRelativeWpContentDir(), '/')));
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return string
     * @throws RuntimeException
     */
    protected function replaceExistingDefinition(string $constant, string $content, string $newDefinition): string
    {
        $pattern = $this->getDefineRegex($constant);
        preg_match($pattern, $content, $matches);

        if (empty($matches[0])) {
            $this->logger->debug("Constant " . $constant . " not defined in wp-config.php. Skipping.");
            return $content;
        }

        $replace = sprintf("define('%s', %s);", $constant, $newDefinition);

        // escaping dollar sign in the value
        $replacementEscapedCharacter = addcslashes($replace, '\\$');

        $content = preg_replace([$pattern], $replacementEscapedCharacter, $content);

        if ($content === null) {
            throw new RuntimeException("Failed to change " . $constant);
        }

        $this->logger->info("Updated: " . $constant . ".");
        return $content;
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return string
     * @throws RuntimeException
     */
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

        // escaping dollar sign
        $replacementEscaped = addcslashes($replacement, '\\$');

        if (($content = preg_replace(self::ABSPATH_REGEX, $replacementEscaped, $content)) === null) {
            throw new RuntimeException("Failed to update constant " . $constant);
        }

        $this->logger->info("Added constant: " . $constant);
        return $content;
    }

    /**
     * @param string $constant
     * @param string $content
     * @return string
     * @throws RuntimeException
     */
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

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return string
     */
    protected function replaceOrAddDefinition(string $constant, string $content, string $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->logger->debug("Constant " . $constant . " not defined in wp-config.php. Creating new entry.");
            $newContent = $this->addDefinition($constant, $content, $newDefinition);
        }

        return $newContent;
    }

    /**
     * @param string $constant
     * @param string $content
     * @param string $newDefinition
     * @return bool|string|string[]|null
     */
    protected function replaceOrSkipDefinition(string $constant, string $content, string $newDefinition)
    {
        $newContent = $this->replaceExistingDefinition($constant, $content, $newDefinition);
        if (!$newContent) {
            $this->logger->info("Skipping: " . $constant . " not defined in wp-config.php.");
            return $content;
        }

        return $newContent;
    }


    /**
     * @param string $content string
     * @return bool
     */
    private function abspathConstantExists(string $content): bool
    {
        preg_match(self::ABSPATH_REGEX, $content, $matches);
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
     * @param string $constant string Name of the constant
     * @return bool
     */
    private function maybeAddDefinedCondition(string $constant): bool
    {
        if ($constant === 'WP_ENVIRONMENT_TYPE' || $constant === 'WPSTAGING_DEV_SITE') {
            return true;
        }

        // Staging on playground/wpnow
        if ($constant === 'WP_SITEURL' || $constant === 'WP_HOME') {
            return true;
        }

        return false;
    }

    /**
     * Helper function to return a string with single
     * quotes escaped.
     *
     * @param string $string
     *
     * @return string
     */
    private function escapeSingleQuotes(string $string): string
    {
        return str_replace("'", "\'", $string);
    }
}
