<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Adapter\Directory;

class DebugLogReader extends LogFiles
{



    protected $filesystem;





    public function __construct(Filesystem $filesystem, Directory $logsDirectory)
    {
        parent::__construct($logsDirectory);
        $this->filesystem = $filesystem;
    }





    public function listenDeleteLogRequest()
    {
        if (!isset($_GET['deleteLog']) || !isset($_GET['deleteLogNonce'])) {
            return;
        }

        if (!current_user_can((new Capabilities())->manageWPSTG()) || !wp_verify_nonce($_GET['deleteLogNonce'], 'wpstgDeleteLogNonce')) {
            return;
        }

        $deleted = false;

        if ($_GET['deleteLog'] === 'wpstaging') {
            $deleted = $this->deleteWpStagingDebugLogFile();
        }

        if ($_GET['deleteLog'] === 'php') {
            $deleted = $this->deletePhpDebugLogFile();
        }

 
        $redirectUrl = add_query_arg([
            'page'            => 'wpstg-tools',
            'tab'             => 'system-info',
            'logDeleteStatus' => $deleted ? 'success' : 'failed',
        ], admin_url('admin.php')) . '#logs';

        wp_safe_redirect($redirectUrl);
        exit;
    }




    public function deletePhpDebugLogFile(): bool
    {
        return $this->deleteLogFile((string)ini_get('error_log'));
    }




    public function deleteWpStagingDebugLogFile(): bool
    {
        if (!defined('WPSTG_DEBUG_LOG_FILE')) {
            return false;
        }

        return $this->deleteLogFile(WPSTG_DEBUG_LOG_FILE);
    }








    private function deleteLogFile(string $path): bool
    {
        if ($path === '' || !file_exists($path)) {
            return false;
        }

        if (!is_file($path)) {
            return false;
        }

        return $this->filesystem->delete($path);
    }








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






    protected function getDebugLogLines($debugLogPath, $maxSize): string
    {
        if (!is_file($debugLogPath) || !is_readable($debugLogPath)) {
            return '';
        }

        try {
            $debugFile = new FileObject($debugLogPath, 'r');

            $negativeOffset = $maxSize;

 
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




    public function maybeFixHtmlEntityDecode(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        $content = esc_html(wp_strip_all_tags($content));
        return str_replace(['&quot;', '&#039;', '&amp;'], ['"', "'", "&"], $content);
    }
}
