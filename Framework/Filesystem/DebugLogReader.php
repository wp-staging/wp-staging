<?php

namespace WPStaging\Framework\Filesystem;

class DebugLogReader
{
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Deletes a log file if requested.
     */
    public function listenDeleteLogRequest()
    {
        if (isset($_GET['deleteLog']) && isset($_GET['deleteLogNonce'])) {
            if (current_user_can((new \WPStaging\Framework\Security\Capabilities())->manageWPSTG()) && wp_verify_nonce($_GET['deleteLogNonce'], 'wpstgDeleteLogNonce')) {
                if ($_GET['deleteLog'] === 'wpstaging') {
                    $this->deleteWpStagingDebugLogFile();
                } elseif ($_GET['deleteLog'] === 'php') {
                    $this->deletePhpDebugLogFile();
                }

                // Redirect to prevent refresh from deleting the log again
                wp_redirect(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info');
                exit;
            }
        }
    }

    public function deletePhpDebugLogFile()
    {
        $phpDebugLogFile = ini_get('error_log');

        if (file_exists($phpDebugLogFile) && is_writable($phpDebugLogFile)) {
            return unlink($phpDebugLogFile);
        }

        return null;
    }

    public function deleteWpStagingDebugLogFile()
    {
        if (file_exists(WPSTG_DEBUG_LOG_FILE) && is_writable(WPSTG_DEBUG_LOG_FILE)) {
            return unlink(WPSTG_DEBUG_LOG_FILE);
        }

        return null;
    }

    /**
     * @param int  $maxSizeEach Max size in bytes to fetch from each log.
     * @param bool $withWpstgDebugLog Whether to include WP STAGING custom log entries.
     * @param bool $withPhpDebugLog Whether to include PHP error_log entries.
     *
     * @return string A formatted text with the last log entries from the debug log files.
     */
    public function getLastLogEntries($maxSizeEach, $withWpstgDebugLog = true, $withPhpDebugLog = true)
    {
        $errors = '';

        if ($withWpstgDebugLog) {
            if (defined('WPSTG_DEBUG_LOG_FILE')) {
                if ($this->filesystem->isReadableFile(WPSTG_DEBUG_LOG_FILE)) {
                    $errors .= sprintf(
                        "--- WPSTAGING Debug Logs\nFile: %s\nTotal file size: %s\nShowing last: %s\n\n=== START ===\n\n",
                        WPSTG_DEBUG_LOG_FILE,
                        size_format(filesize(WPSTG_DEBUG_LOG_FILE)),
                        size_format($maxSizeEach)
                    );
                    $errors .= $this->getDebugLogLines(WPSTG_DEBUG_LOG_FILE, $maxSizeEach);
                    $errors .= "=== END ===\n\n";
                } else {
                    $errors .= "\n=== File WPSTG_DEBUG_LOG_FILE is not readable or does not exist ===\n";
                }
            } else {
                $errors .= "\n=== WPSTG_DEBUG_LOG_FILE NOT DEFINED ===\n";
            }
        }

        if ($withPhpDebugLog) {
            /** @see \wp_debug_mode to understand why it uses ini_get() */
            $phpDebugLogFile = ini_get('error_log');

            if ($this->filesystem->isReadableFile($phpDebugLogFile)) {
                $errors .= sprintf(
                    "--- PHP debug.log \nFile: %s\nTotal file size: %s\nShowing last: %s\n\n=== START ===\n\n",
                    $phpDebugLogFile,
                    size_format(filesize($phpDebugLogFile)),
                    size_format($maxSizeEach)
                );
                $errors .= $this->getDebugLogLines($phpDebugLogFile, $maxSizeEach);
                $errors .= "=== END ===\n\n";
            } else {
                $errors .= "\n=== PHP DEBUG LOG FILE IS NOT A FILE OR IS NOT READABLE ===\n";
            }
        }

        return $errors;
    }

    protected function getDebugLogLines($debugLogPath, $maxSize)
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
                $line = trim($debugFile->readAndMoveNext());
                $line = html_entity_decode($line);
                $line = sanitize_text_field($line);
                $debugLines[] = $line;
            } while ($debugFile->valid());

            return implode("\n\n", $debugLines);
        } catch (\Exception $e) {
            return '';
        }
    }
}
