<?php

/**
 * This class is not PSR-3 compliant. Currently just added the basic functionality to make the "change" easier
 * in the future. For now, there are just few things to make transition easy.
 *
 */

namespace WPStaging\Core\Utils;

use WPStaging\Core\DTO\Settings;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Vendor\Psr\Log\LogLevel;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\SiteInfo;

/**
 * Class Logger
 * @package WPStaging\Core\Utils
 */
class Logger implements LoggerInterface, ShutdownableInterface
{
    const TYPE_ERROR    = "ERROR";

    const TYPE_CRITICAL = "CRITICAL";

    const TYPE_FATAL    = "FATAL";

    const TYPE_WARNING  = "WARNING";

    const TYPE_INFO     = "INFO";

    const TYPE_DEBUG    = "DEBUG";

    const TYPE_INFO_SUB = "INFO_SUB";

    /**
     * @var string 
     */
    const LOG_DATETIME_FORMAT = "Y/m/d H:i:s";

    /**
     * Log directory (full path)
     * @var string
     */
    private $logDir;

    /**
     * Log file extension
     * @var string
     */
    private $logExtension   = "log";

    /**
     * Messages to log
     * @var array
     */
    private $messages       = [];

    /**
     * Forced filename for the log
     * @var null|string
     */
    private $fileName       = null;

    /** @var Settings */
    private $settingsDto;

    /**
     * Logger constructor.
     *
     * @param null|string $logDir
     * @param null|string $logExtension
     *
     * @throws \Exception
     */
    public function __construct($logDir = null, $logExtension = null, $settingsDto = null)
    {
        // Set log directory
        if (!empty($logDir) && is_dir($logDir)) {
            $this->logDir = rtrim($logDir, "/\\") . DIRECTORY_SEPARATOR;
        } else {
            // Set default
            $this->logDir = WPStaging::getContentDir() . "logs" . DIRECTORY_SEPARATOR;
        }

        // Set log extension
        if (!empty($logExtension)) {
            $this->logExtension = $logExtension;
        }

        /**
         * If log directory doesn't exist, create it.
         * @see \WPStaging\Framework\Notices\Notices::renderNotices Notice that shows if log directory couldn't be created.
         */
        (new Filesystem())->mkdir($this->logDir);

        $this->settingsDto = $settingsDto;
        if ($this->settingsDto === null) {
            $this->settingsDto = WPStaging::make(Settings::class);
        }
    }

    public function onWpShutdown()
    {
        $this->commit();
    }

    /**
     * @return void
     */
    public function writeLogHeader()
    {
        $systemInfo = WPStaging::make(SystemInfo::class);

        /** @var SiteInfo */
        $siteInfo   = WPStaging::make(SiteInfo::class);
        // Keeping these non-translated
        $host       = 'General';
        if ($siteInfo->isHostedOnWordPressCom()) {
            $host   = 'WordPress.com';
        } elseif ($siteInfo->isFlywheel()) {
            $host   = 'Flywheel';
        }

        $this->info(esc_html('WP Staging Version: ' . $systemInfo->getWpStagingVersion()));
        $this->info(esc_html('PHP Version: ' . $systemInfo->getPhpVersion()));
        $this->info(esc_html('Server: ' . $systemInfo->getWebServerInfo()));
        $this->info(esc_html('MySQL: ' . $systemInfo->getMySqlVersionCompact()));
        $this->info(esc_html('WP Version: ' . get_bloginfo("version")));
        $this->info(esc_html('Host: ' . $host));
        $this->info(esc_html('PHP Memory Limit: ' . wp_convert_hr_to_bytes(ini_get("memory_limit"))));
        $this->info(esc_html('WP Memory Limit: ' . (defined('WP_MEMORY_LIMIT') ? wp_convert_hr_to_bytes(WP_MEMORY_LIMIT) : '')));
        $this->info(esc_html('PHP Max Execution Time: ' . ini_get("max_execution_time")));
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->add($message, $level);
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function add($message, $type = self::TYPE_ERROR)
    {
        $this->messages[] = [
            "type"      => $type,
            "date"      => current_time(self::LOG_DATETIME_FORMAT),
            "message"   => wp_kses($message, [])
        ];
    }

    /**
     * @return null|string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        if (empty($this->messages)) {
            return true;
        }

        $messageString = '';
        foreach ($this->messages as $message) {
            if (is_array($message)) {
                $messageString .= "[{$message["type"]}]-[{$message["date"]}] {$message["message"]}" . PHP_EOL;
            }
        }

        if (strlen($messageString) < 1) {
            return true;
        }

        return (@file_put_contents($this->getLogFile(), $messageString, FILE_APPEND));
    }

    /**
     * @param null|string $file
     *
     * @return string
     */
    public function read($file = null)
    {
        return @file_get_contents($this->getLogFile($file));
    }

    /**
     * @param null|string $fileName
     *
     * @return string
     */
    public function getLogFile($fileName = null)
    {
        // Default
        if ($fileName === null) {
            $fileName = ($this->fileName !== null) ? $this->fileName : date("Y_m_d");
        }

        return $this->logDir . $fileName . '.' . $this->logExtension;
    }

    /**
     * Delete a log file
     *
     * @param string $logFileName
     *
     * @return bool
     * @throws \Exception
     */
    public function delete($logFileName)
    {
        $logFile = $this->logDir . $logFileName . '.' . $this->logExtension;

        if (@unlink($logFile) === false) {
            throw new \Exception("Couldn't delete cache: {$logFileName}. Full Path: {$logFile}");
        }

        return true;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return $this->logDir;
    }

    /**
     * @return string
     */
    public function getLogExtension()
    {
        return $this->logExtension;
    }

    /**
     * Get last element of logging data array
     * @return array
     */
    public function getLastLogMsg()
    {
        // return all messages
        if (count($this->messages) > 1) {
            return $this->messages;
        } else {
            // Return last message
            return $this->messages[] = array_pop($this->messages);
        }
    }

    /**
     * Get last error message
     * @param array $types - Types in which search the last logged message
     *                     - Default [ERROR, CRITICAL]
     *
     * @return array|false
     */
    public function getLastErrorMsg($types = [self::TYPE_ERROR, self::TYPE_CRITICAL])
    {
        if (count($this->messages) === 0) {
            return false;
        }

        foreach (array_reverse($this->messages) as $message) {
            // Skip if type is not set
            if (empty($message['type'])) {
                continue;
            }

            if (in_array(strtoupper($message['type']), $types)) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = [])
    {
        $this->add($message, LogLevel::EMERGENCY);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = [])
    {
        $this->add($message, LogLevel::ALERT);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = [])
    {
        $this->add($message, LogLevel::CRITICAL);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = [])
    {
        $this->add($message, LogLevel::ERROR);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = [])
    {
        $this->add($message, LogLevel::WARNING);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = [])
    {
        $this->add($message, LogLevel::NOTICE);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
        $this->add($message, LogLevel::INFO);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        if ($this->isDebugMode()) {
            $this->add($message, LogLevel::DEBUG);
        }
    }

    /**
     * @return void
     */
    public function writeInstalledPluginsAndThemes()
    {
        $allPlugins = get_plugins();
        $activePlugins = get_option("active_plugins", []);
        $allThemes = wp_get_themes();
        $activeTheme = wp_get_theme();

        $networkActivePlugins = [];
        if (is_multisite()) {
            $networkActivePlugins = array_keys(get_site_option("active_sitewide_plugins", []));
        }

        $this->info('Installed Plugins And Themes');

        $this->listActivePlugins($allPlugins, $activePlugins, $networkActivePlugins);
        $allActivePlugins = array_merge($activePlugins, $networkActivePlugins);
        $this->listInactivePlugins($allPlugins, $allActivePlugins);
        $this->listActiveTheme($activeTheme);
        $this->listInactiveThemes($allThemes, $activeTheme);
    }


    /** @return bool */
    protected function isDebugMode()
    {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG === true) {
            return true;
        }

        return $this->settingsDto->isDebugMode();
    }


    /**
     * @param array $allPlugins
     * @param array $activePlugins
     * @param array $networkActivePlugins
     * @return void
     */
    protected function listActivePlugins(array $allPlugins, array $activePlugins, array $networkActivePlugins = [])
    {
        $isMultisite = is_multisite();

        if ($isMultisite) {
            $this->info("Activated Plugins on this Site");
        } else {
            $this->info("Activated Plugins");
        }

        foreach ($allPlugins as $path => $plugin) {
            if (in_array($path, $activePlugins)) {
                $this->add("- {$plugin['Name']} : {$plugin['Version']}", self::TYPE_INFO_SUB);
            }
        }

        if ($isMultisite) {
            $this->info("Activated Network Plugins");

            foreach ($networkActivePlugins as $pluginPath) {
                if (isset($allPlugins[$pluginPath])) {
                    $plugin = $allPlugins[$pluginPath];
                    $this->add("- {$plugin['Name']} : {$plugin['Version']}", self::TYPE_INFO_SUB);
                }
            }
        }
    }

    /**
     * @param array $allPlugins
     * @param array $allActivePlugins
     * @return void
     */
    protected function listInactivePlugins(array $allPlugins, array $allActivePlugins)
    {
        $this->info("Inactive Plugins");

        foreach ($allPlugins as $path => $plugin) {
            if (!in_array($path, $allActivePlugins)) {
                $this->add("- {$plugin['Name']} : {$plugin['Version']}", self::TYPE_INFO_SUB);
            }
        }
    }

    /**
     * @param \WP_Theme $activeTheme
     * @return void
     */
    protected function listActiveTheme(\WP_Theme $activeTheme)
    {
        $this->info("Activated Theme");
        $this->add("- {$activeTheme->get('Name')} : {$activeTheme->get('Version')}", self::TYPE_INFO_SUB);
    }

    /**
     * @param array $allThemes
     * @param \WP_Theme $activeTheme
     * @return void
     */
    protected function listInactiveThemes(array $allThemes, \WP_Theme $activeTheme)
    {
        $this->info("Inactive Themes");

        foreach ($allThemes as $theme) {
            if ($theme->get_stylesheet() !== $activeTheme->get_stylesheet()) {
                $this->add("- {$theme->get('Name')} : {$theme->get('Version')}", self::TYPE_INFO_SUB);
            }
        }
    }
}
