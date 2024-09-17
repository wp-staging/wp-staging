<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Adapter\Directory;

class DebugLogReader extends LogFiles
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     * @param Directory $logsDirectory
     */
    public function __construct(Filesystem $filesystem, Directory $logsDirectory)
    {
        parent::__construct($logsDirectory);
        $this->filesystem = $filesystem;
    }

    /**
     * Deletes a log file if requested.
     * Used by WPStaging\Framework\CommonServiceProvider::registerClasses()
     */
    public function listenDeleteLogRequest()
    {
        if (!isset($_GET['deleteLog']) || !isset($_GET['deleteLogNonce'])) {
            return;
        }

        if (!current_user_can((new Capabilities())->manageWPSTG()) || !wp_verify_nonce($_GET['deleteLogNonce'], 'wpstgDeleteLogNonce')) {
            return;
        }

        if ($_GET['deleteLog'] === 'wpstaging') {
            $this->deleteWpStagingDebugLogFile();
        }

        if ($_GET['deleteLog'] === 'php') {
            $this->deletePhpDebugLogFile();
        }

        // Redirect to prevent refresh from deleting the log again
        wp_redirect(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info');
        exit;
    }

    /**
     * @return bool|null Whether the log file was deleted or not.
     */
    public function deletePhpDebugLogFile()
    {
        $phpDebugLogFile = ini_get('error_log');

        if (file_exists($phpDebugLogFile) && is_writable($phpDebugLogFile)) {
            return unlink($phpDebugLogFile);
        }

        return null;
    }

    /**
     * @return bool|null
     */
    public function deleteWpStagingDebugLogFile()
    {
        if (file_exists(WPSTG_DEBUG_LOG_FILE) && is_writable(WPSTG_DEBUG_LOG_FILE)) {
            return unlink(WPSTG_DEBUG_LOG_FILE);
        }

        return null;
    }

    /**
     * @param int $maxSizeEach Max size in bytes to fetch from each log.
     * @param bool $withWpstgDebugLog Whether to include WP STAGING custom log entries.
     * @param bool $withPhpDebugLog Whether to include PHP error_log entries.
     *
     * @return string A formatted text with the last log entries from the debug log files.
     */
    public function getLastLogEntries(int $maxSizeEach, bool $withWpstgDebugLog = true, bool $withPhpDebugLog = true): string
    {
        $content = '';

        if ($withWpstgDebugLog) {
            if (defined('WPSTG_DEBUG_LOG_FILE')) {
                $wpstgDebugLogFile = WPSTG_DEBUG_LOG_FILE;

                if ($this->filesystem->isReadableFile($wpstgDebugLogFile)) {
                    $wpstgDebugLogFileSize = filesize($wpstgDebugLogFile);

                    $content .= sprintf(
                        "--- WP STAGING Debug Logs\nFile: %s\nTotal file size: %s\nShowing last: %s\n\n=== START ===\n\n",
                        $wpstgDebugLogFile,
                        size_format($wpstgDebugLogFileSize),
                        size_format($maxSizeEach)
                    );

                    if ($wpstgDebugLogFileSize > $maxSizeEach) {
                        $content .= $this->getDebugLogLines($wpstgDebugLogFile, $maxSizeEach);
                    } else {
                        $content .= file_get_contents($wpstgDebugLogFile);
                    }
                    $content .= "=== END ===\n\n";
                } else {
                    $content .= "\n=== File WPSTG_DEBUG_LOG_FILE is not readable or does not exist ===\n";
                }
            } else {
                $content .= "\n=== WPSTG_DEBUG_LOG_FILE NOT DEFINED ===\n";
            }
        }

        if ($withPhpDebugLog) {
            /** @see \wp_debug_mode to understand why it uses ini_get() */
            $phpDebugLogFile = ini_get('error_log');

            if ($this->filesystem->isReadableFile($phpDebugLogFile)) {
                $phpDebugLogFileSize = filesize($phpDebugLogFile);

                $content .= sprintf(
                    "--- PHP debug.log \nFile: %s\nTotal file size: %s\nShowing last: %s\n\n=== START ===\n\n",
                    $phpDebugLogFile,
                    size_format($phpDebugLogFileSize),
                    size_format($maxSizeEach)
                );

                if ($phpDebugLogFileSize > $maxSizeEach) {
                    $content .= $this->getDebugLogLines($phpDebugLogFile, $maxSizeEach);
                } else {
                    $content .= file_get_contents($phpDebugLogFile);
                }

                $content .= "=== END ===\n\n";
            } else {
                $content .= "\n=== PHP DEBUG LOG FILE IS NOT A FILE OR IS NOT READABLE ===\n";
            }
        }

        return $content;
    }

    /**
     * @param $debugLogPath
     * @param $maxSize
     * @return string
     */
    protected function getDebugLogLines($debugLogPath, $maxSize): string
    {
        if (!is_file($debugLogPath) || !is_readable($debugLogPath)) {
            return '';
        }

        try {
            $debugFile = new FileObject($debugLogPath, 'r');

            $negativeOffset = $maxSize;

            // Set the pointer to the end of the file, minus the negative offset for which to start looking for errors.
            $debugFile->fseek(max($debugFile->getSize() - $negativeOffset, 0), SEEK_SET);

            $debugLines = [];

            do {
                $line         = trim($debugFile->readAndMoveNext());
                $line         = html_entity_decode($line);
                $line         = sanitize_text_field($line);
                $debugLines[] = $line;
            } while ($debugFile->valid());

            return implode("\n", $debugLines);
        } catch (\Exception $e) {
            return '';
        }
    }

   /**
    * @return string
    */
    public function maybeFixHtmlEntityDecode(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        $content = esc_html(wp_strip_all_tags($content));
        return str_replace(['&quot;', '&#039;', '&amp;'], ['"', "'", "&"], $content);
    }
}
