<?php

namespace WPStaging\Framework\Network;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Sanitize;

class RemoteDownloader
{
    /**
     * Default timeout in seconds.
     * @var int
     */
    const DEFAULT_TIMEOUT = 60;

    /**
     * Default chunk size in bytes.
     * @var int
     */
    private $chunkSize = 5 * 1024 * 1024;

    /** @var Sanitize */
    protected $sanitize;

    /** @var string */
    protected $remoteUrl = '';

    /** @var string */
    protected $localPath = '';

    /** @var string */
    protected $fileName = '';

    /** @var int */
    protected $remoteFileSize = 0;

    /** @var int */
    private $startByte = 0;

    /** @var int */
    private $endByte = 0;

    /** @var int */
    private $lastDownloadedBytes = 0;

    /** @var bool */
    private $success = false;

    /** @var bool */
    private $completed = false;

    /** @var string */
    private $message = '';

    /** @var int */
    private $timeout = self::DEFAULT_TIMEOUT;

    /** @var resource|null */
    private $fileHandle = null;

    /**
     * @param Sanitize $sanitize
     */
    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

    /**
     * @param string $url
     * @return void
     */
    public function setRemoteUrl(string $url)
    {
        $this->remoteUrl = $this->sanitize->sanitizeUrl($url);
    }

    /**
     * @param string $localPath
     * @return void
     */
    public function setLocalPath(string $localPath)
    {
        $this->localPath = $this->sanitize->sanitizePath($localPath);
    }

    /**
     * @param string $fileName
     * @return void
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @param int $fileSize
     * @return void
     */
    public function setRemoteFileSize(int $fileSize)
    {
        $this->remoteFileSize = $this->sanitize->sanitizeInt($fileSize);
    }

    /**
     * @param int $chunkSize
     * @return void
     */
    public function setChunkSize(int $chunkSize)
    {
        $chunkSize = $this->sanitize->sanitizeInt($chunkSize, true);
        if ($chunkSize >= MB_IN_BYTES) {
            $this->chunkSize = $chunkSize;
        }
    }

    /**
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @param int $startByte
     * @return void
     */
    public function setStartByte(int $startByte)
    {
        $this->startByte = $this->sanitize->sanitizeInt($startByte);
    }

    public function getStartByte(): int
    {
        return $this->startByte;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getIsCompleted(): bool
    {
        return $this->completed;
    }

    public function getIsSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get the number of bytes downloaded in the last successful chunk.
     * @return int
     */
    public function getLastDownloadedBytes(): int
    {
        return $this->lastDownloadedBytes;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRemoteFileSize(): int
    {
        return $this->remoteFileSize;
    }

    public function getUploadPath(): string
    {
        return $this->localPath . '.uploading';
    }

    /**
     * @param string $message
     * @param bool $success
     * @param bool $completed
     * @return void
     */
    public function setResponse(string $message, bool $success = false, bool $completed = false)
    {
        $this->message   = esc_html($message);
        $this->success   = $success;
        $this->completed = $completed;
    }

    /**
     * Download a chunk of the remote file.
     * @return void
     */
    public function downloadChunk()
    {
        // Ensure we don't request beyond the file size
        $this->endByte = min($this->startByte + $this->chunkSize - 1, $this->remoteFileSize - 1);

        $args = [
            'method'    => 'GET',
            'timeout'   => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify' => false,
            'headers'   => [
                'Range' => "bytes={$this->startByte}-{$this->endByte}",
            ],
        ];

        $response = $this->makeRemoteRequest($args);
        if (is_wp_error($response)) {
            $this->message  = $response->get_error_message();
            $this->success  = false;
            $this->completed = true;
            return;
        }

        $fileContent = wp_remote_retrieve_body($response);
        if (!empty($fileContent)) {
            $this->success = $this->writeToLocalFile($fileContent);
            $contentLength = strlen($fileContent);
            $this->lastDownloadedBytes = $contentLength;
            $this->maybeFinishDownload($contentLength);
        } else {
            // Handle empty response - might be at end of file
            $this->success = true;
            $this->lastDownloadedBytes = 0;
            $this->maybeFinishDownload(0);
        }
    }

    /**
     * @return void
     */
    public function closeFileHandle()
    {
        if (!empty($this->fileHandle)) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Write content to the local file.
     * @param string $fileContent
     * @return bool
     */
    private function writeToLocalFile(string $fileContent)
    {
        try {
            if (empty($this->fileHandle)) {
                $this->fileHandle = fopen($this->getUploadPath(), FileObject::MODE_APPEND_AND_READ);
            }

            if (empty($this->fileHandle)) {
                $this->message = 'Failed to open local file for writing.';
                return false;
            }

            $bytesWritten = fwrite($this->fileHandle, $fileContent);
            if ($bytesWritten === false) {
                $this->message = 'Failed to write to the local file.';
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->message = 'Error writing local file: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Send a JSON response for the current download state.
     * @return void
     */
    public function writeResponse()
    {
        wp_send_json([
            'success'   => $this->success,
            'message'   => esc_html($this->message),
            'complete'  => $this->completed,
            'startByte' => $this->startByte,
            'fileSize'  => $this->remoteFileSize
        ]);
    }

    /**
     * Get the remote file size.
     * @return int
     */
    public function fetchRemoteFileSize()
    {
        $args = [
            'method'    => 'HEAD',
            'timeout'   => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify' => false,
        ];

        $response = $this->makeRemoteRequest($args);
        if (is_wp_error($response)) {
            $this->message = $response->get_error_message();
            return 0;
        }

        $contentLength = wp_remote_retrieve_header($response, 'content-length');
        if (empty($contentLength)) {
            return 0;
        }

        return intval($contentLength);
    }

    /**
     * Verify if the download is complete.
     * If the download is complete, rename the temporary file to the original file name.
     * @param int $contentLength
     * @return void
     */
    public function maybeFinishDownload(int $contentLength)
    {
        // Check if this is the last chunk (either smaller than chunk size OR we've reached the end of file)
        $isLastChunk = ($contentLength < $this->chunkSize) || ($this->startByte + $contentLength >= $this->remoteFileSize);

        if (!$isLastChunk) {
            return;
        }

        $originalPath = $this->localPath;
        if (file_exists($this->localPath)) {
            $info = pathinfo($originalPath);
            $this->localPath = $info['dirname'] . '/' . $info['filename'] . '.wpstg';
        }

        $uploadPath = $originalPath . '.uploading';
        if (!file_exists($uploadPath)) {
            $this->message = 'Upload file does not exist';
            $this->success = false;
            $this->completed = true;
            return;
        }

        if (!rename($uploadPath, $this->localPath)) {
            $this->message = 'Failed to rename upload file';
            $this->success = false;
            $this->completed = true;
            return;
        }

        if (file_exists($this->localPath) && filesize($this->localPath) < $this->remoteFileSize) {
            if (!$this->remoteFileExists()) {
                return;
            }

            $this->message   = sprintf(esc_html__('File upload incomplete. Expected Size: %s, Actual Size: %s', 'wp-staging'), $this->remoteFileSize, filesize($this->localPath));
            $this->success   = false;
            $this->completed = true;
            return;
        }

        $this->message   = esc_html__('File uploaded successfully', 'wp-staging');
        $this->success   = true;
        $this->completed = true;
    }

    /**
     * Move the start byte forward by the actual bytes downloaded.
     * @return void
     */
    public function advanceStartByte()
    {
        $this->startByte += $this->lastDownloadedBytes;
    }

    /**
     * Check if the remote file exists.
     * @return bool
     */
    public function remoteFileExists()
    {
        $args = [
            'method'    => 'HEAD',
            'timeout'   => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify' => false,
            'headers'   => [
                'Cache-Control' => 'no-cache',
            ]
        ];

        $response     = $this->makeRemoteRequest($args);
        $responseCode = wp_remote_retrieve_response_code($response);
        if (is_array($response) && !is_wp_error($response) && (int)$responseCode === 200) {
            return true;
        }

        $this->message   = esc_html__('File not available on remote server', 'wp-staging');
        $this->success   = false;
        $this->completed = true;

        return false;
    }

    /**
     * Get a chunk of remote file content.
     * @param int $startByte
     * @param int $endByte
     * @return string
     */
    public function fetchRemoteFileContent(int $startByte, int $endByte)
    {
        $args = [
            'method'    => 'GET',
            'timeout'   => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify' => false,
            'headers'   => [
                'Cache-Control' => 'no-cache',
                'Range'         => "bytes={$startByte}-{$endByte}",
            ]
        ];

        $response = $this->makeRemoteRequest($args);
        $responseCode = wp_remote_retrieve_response_code($response);
        if (is_array($response) && !is_wp_error($response) && in_array((int)$responseCode, [200, 206])) {
            return wp_remote_retrieve_body($response);
        }

        return '';
    }

    /**
     * Make a remote request.
     * @param array $args
     * @return array|\WP_Error
     */
    protected function makeRemoteRequest(array $args)
    {
        $args['user-agent'] = 'Mozilla/5.0 (compatible; wp-staging/' . WPStaging::getVersion() . '; +https://wp-staging.com)';
        if (!isset($args['method'])) {
            $args['method'] = 'POST';
        }

        return wp_remote_request($this->remoteUrl, $args);
    }
}
