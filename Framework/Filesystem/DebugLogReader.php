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
                        "--- WPSTAGING Debug Logs\nFile: %s\nTotal file size: %s\nShowing last: %s\n=== START ===\n",
                        WPSTG_DEBUG_LOG_FILE,
                        size_format(filesize(WPSTG_DEBUG_LOG_FILE)),
                        size_format($maxSizeEach)
                    );
                    $errors .= $this->getDebugLogLines(WPSTG_DEBUG_LOG_FILE, $maxSizeEach);
                    $errors .= "=== END ===\n\n";
                } else {
                    $errors .= "\n=== WPSTG_DEBUG_LOG_FILE IS NOT A FILE OR IS NOT READABLE ===\n";
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
                    "--- PHP Debug Logs\nFile: %s\nTotal file size: %s\nShowing last: %s\n=== START ===\n",
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
                $line = trim($debugFile->fgets());
                $line = html_entity_decode($line);
                $line = sanitize_text_field($line);
                $debugLines[] = $line;
            } while ($debugFile->valid());

            return implode("\n", $debugLines);
        } catch (\Exception $e) {
            return '';
        }
    }
}
