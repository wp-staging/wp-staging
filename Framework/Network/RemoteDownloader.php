<?php

namespace WPStaging\Framework\Network;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Sanitize;

/**
 * Downloads remote files in chunks and probes remote metadata needed for resumable downloads.
 */
class RemoteDownloader
{
    /**
     * Default timeout in seconds.
     * @var int
     */
    const DEFAULT_TIMEOUT = 60;

    /**
     * Extension added to files while they are being downloaded.
     * @var string
     */
    const UPLOADING_EXTENSION = 'uploading';

    /**
     * Number of body bytes to read when probing size with GET.
     * @var int
     */
    const FILE_SIZE_PROBE_RESPONSE_LIMIT = 1;

    /**
     * Number of bytes copied from the temporary chunk file into the final upload file at once.
     * @var int
     */
    const FILE_APPEND_BLOCK_SIZE = 1048576;

    /**
     * Error code used when the remote server returns a full response to a partial download request.
     * @var string
     */
    const ERROR_PARTIAL_DOWNLOAD_NOT_SUPPORTED = 'partial_download_not_supported';

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

    /** @var string */
    private $errorCode = '';

    /** @var int */
    private $timeout = self::DEFAULT_TIMEOUT;

    /** @var resource|null */
    private $fileHandle = null;

    /** @var array<string, string> */
    private $customHeaders = [];

    /** @var bool */
    private $followRedirects = true;

    /** @var bool */
    private $allowUnknownRemoteFileSize = false;

    /**
     * @param Sanitize $sanitize
     */
    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

    /**
     * Ensure file handle is closed when object is destroyed
     */
    public function __destruct()
    {
        $this->closeFileHandle();
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
     * @param bool $allow
     * @return void
     */
    public function setAllowUnknownRemoteFileSize(bool $allow)
    {
        $this->allowUnknownRemoteFileSize = $allow;
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

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRemoteFileSize(): int
    {
        return $this->remoteFileSize;
    }

    public function getUploadPath(): string
    {
        return $this->localPath . '.' . self::UPLOADING_EXTENSION;
    }

    /**
     * @param array<string, string> $headers
     * @return void
     */
    public function setCustomHeaders(array $headers)
    {
        $this->customHeaders = $headers;
    }

    /**
     * @param bool $follow
     * @return void
     */
    public function setFollowRedirects(bool $follow)
    {
        $this->followRedirects = $follow;
    }

    /**
     * @param string $message
     * @param bool $success
     * @param bool $completed
     * @param string $errorCode
     * @return void
     */
    public function setResponse(string $message, bool $success = false, bool $completed = false, string $errorCode = '')
    {
        $this->message   = esc_html($message);
        $this->success   = $success;
        $this->completed = $completed;
        $this->errorCode = $errorCode;
    }

    /**
     * Download a chunk of the remote file.
     * @return void
     */
    public function downloadChunk()
    {
        $this->errorCode = '';

        if ($this->remoteFileSize <= 0 && !$this->allowUnknownRemoteFileSize) {
            $this->lastDownloadedBytes = 0;
            $this->setResponse(__('Remote file size is unknown.', 'wp-staging'), false, true);
            return;
        }

        $isUnknownSize = $this->remoteFileSize <= 0;
        // Ensure we don't request beyond the file size when it is known.
        $this->endByte = $isUnknownSize ?
            $this->startByte + $this->chunkSize - 1 :
            min($this->startByte + $this->chunkSize - 1, $this->remoteFileSize - 1);

        $requestedBytes    = max(1, $this->endByte - $this->startByte + 1);
        $limitResponseSize = $isUnknownSize ? $this->startByte + $requestedBytes : $requestedBytes;
        $chunkPath         = $this->getChunkDownloadPath();
        $this->deleteFile($chunkPath);

        $args = [
            'method'              => 'GET',
            'timeout'             => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify'           => false,
            'stream'              => true,
            'filename'            => $chunkPath,
            'limit_response_size' => $limitResponseSize,
            'headers'             => [
                'Accept-Encoding' => 'identity',
                'Cache-Control'   => 'no-cache',
                'Range'           => "bytes={$this->startByte}-{$this->endByte}",
            ],
        ];

        $response = $this->makeRemoteRequest($args);
        if (is_wp_error($response)) {
            $this->deleteFile($chunkPath);
            $this->lastDownloadedBytes = 0;
            $this->message             = $response->get_error_message();
            $this->success             = false;
            $this->completed           = true;
            return;
        }

        if ($this->isAuthenticationFailure($response)) {
            $this->deleteFile($chunkPath);
            $this->lastDownloadedBytes = 0;
            $this->setAuthenticationFailureMessage($response);
            $this->success   = false;
            $this->completed = true;

            return;
        }

        if ($this->isUnknownSizeEndOfFileResponse($response, $isUnknownSize)) {
            $this->deleteFile($chunkPath);
            $this->success             = true;
            $this->lastDownloadedBytes = 0;
            $this->maybeFinishDownload(0);
            return;
        }

        if (!$this->hasSuccessfulChunkResponse($response, $requestedBytes)) {
            $this->deleteFile($chunkPath);
            $this->lastDownloadedBytes = 0;
            $this->success             = false;
            $this->completed           = true;
            return;
        }

        $downloadedBytes = file_exists($chunkPath) ? (int)filesize($chunkPath) : 0;
        if ($downloadedBytes > 0) {
            $bytesToSkip = $this->getChunkBytesToSkip($response, $isUnknownSize, $downloadedBytes, $requestedBytes);
            $newBytes    = max(0, $downloadedBytes - $bytesToSkip);

            $this->success             = $this->appendChunkToLocalFile($chunkPath, $bytesToSkip);
            $this->lastDownloadedBytes = $this->success ? $newBytes : 0;
            $this->deleteFile($chunkPath);
            if ($this->success) {
                $this->maybeFinishDownload($this->lastDownloadedBytes);
            } else {
                $this->completed = true;
            }
        } else {
            $this->deleteFile($chunkPath);
            // Handle empty response - might be at end of file
            $this->success             = true;
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
     * Append the streamed chunk file to the local upload file without loading it fully into memory.
     *
     * @param string $chunkPath
     * @param int $bytesToSkip
     * @return bool
     */
    private function appendChunkToLocalFile(string $chunkPath, int $bytesToSkip = 0)
    {
        $chunkHandle = null;

        try {
            if (empty($this->fileHandle)) {
                $this->fileHandle = fopen($this->getUploadPath(), FileObject::MODE_APPEND_AND_READ);
            }

            if (empty($this->fileHandle)) {
                $this->message = 'Failed to open local file for writing.';
                return false;
            }

            $chunkHandle = fopen($chunkPath, FileObject::MODE_READ);
            if (empty($chunkHandle)) {
                $this->message = 'Failed to open temporary chunk file for reading.';
                return false;
            }

            if ($bytesToSkip > 0 && fseek($chunkHandle, $bytesToSkip) !== 0) {
                $this->message = 'Failed to seek temporary chunk file.';
                return false;
            }

            while (!feof($chunkHandle)) {
                $buffer = fread($chunkHandle, self::FILE_APPEND_BLOCK_SIZE);
                if ($buffer === false) {
                    $this->message = 'Failed to read temporary chunk file.';
                    return false;
                }

                if ($buffer === '') {
                    continue;
                }

                $bytesWritten = fwrite($this->fileHandle, $buffer);
                if ($bytesWritten === false || $bytesWritten !== strlen($buffer)) {
                    $this->message = 'Failed to write to the local file.';
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            $this->message = 'Error writing local file: ' . $e->getMessage();
            return false;
        } finally {
            if (!empty($chunkHandle) && is_resource($chunkHandle)) {
                fclose($chunkHandle);
            }
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
            'fileSize'  => $this->remoteFileSize,
        ]);
    }

    /**
     * Get the remote file size.
     * @param bool $sslVerify
     * @return int
     */
    public function fetchRemoteFileSize(bool $sslVerify = true): int
    {
        $args = [
            'method'      => 'HEAD',
            'timeout'     => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'redirection' => 5,
            'sslverify'   => $sslVerify,
            'headers'     => [
                'Accept-Encoding' => 'identity',
                'Cache-Control'   => 'no-cache',
            ],
        ];

        $response = $this->makeRemoteRequest($args);

        if (is_wp_error($response)) {
            $this->message = $response->get_error_message();
            return 0;
        }

        if ($this->isAuthenticationFailure($response)) {
            $this->setAuthenticationFailureMessage($response);

            return 0;
        }

        if (!$this->hasSuccessfulResponseCode($response, [200, 204, 206])) {
            return 0;
        }

        return $this->getContentLengthFromResponse($response);
    }

    /**
     * Get the remote file size.
     * @param bool $sslVerify
     * @return int
     */
    public function fetchRemoteFileSizeByGet(bool $sslVerify = true): int
    {
        $args = [
            'method'              => 'GET',
            'timeout'             => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'redirection'         => 5,
            'headers'             => array_merge(
                [
                    'Accept-Encoding' => 'identity',
                    'Cache-Control'   => 'no-cache',
                    'Range'           => 'bytes=0-0',
                ],
                $this->customHeaders
            ),
            'limit_response_size' => self::FILE_SIZE_PROBE_RESPONSE_LIMIT,
            'sslverify'           => $sslVerify,
        ];

        $response = $this->makeRemoteRequest($args);
        if (is_wp_error($response)) {
            $this->message = $response->get_error_message();
            return 0;
        }

        if ($this->isAuthenticationFailure($response)) {
            $this->setAuthenticationFailureMessage($response);

            return 0;
        }

        if (!$this->hasSuccessfulResponseCode($response, [200, 206])) {
            return 0;
        }

        $contentRangeSize = $this->getContentRangeSizeFromResponse($response);
        if ($contentRangeSize > 0) {
            return $contentRangeSize;
        }

        if ((int)wp_remote_retrieve_response_code($response) === 206) {
            return 0;
        }

        return $this->getContentLengthFromResponse($response);
    }

    /**
     * Try all lightweight remote file size probes.
     *
     * @return int
     */
    public function fetchRemoteFileSizeWithFallbacks(): int
    {
        $fileSize = $this->fetchRemoteFileSize();
        if ($fileSize > 0) {
            return $fileSize;
        }

        $fileSize = $this->fetchRemoteFileSize(false);
        if ($fileSize > 0) {
            return $fileSize;
        }

        $fileSize = $this->fetchRemoteFileSizeByGet();
        if ($fileSize > 0) {
            return $fileSize;
        }

        return $this->fetchRemoteFileSizeByGet(false);
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
        $isUnknownSize = $this->remoteFileSize <= 0 && $this->allowUnknownRemoteFileSize;
        if ($isUnknownSize && $contentLength === 0 && $this->startByte === 0) {
            $this->message   = esc_html__('Unable to download remote file chunk.', 'wp-staging');
            $this->success   = false;
            $this->completed = true;
            return;
        }

        $isLastChunk   = $isUnknownSize ?
            $contentLength < $this->chunkSize :
            ($contentLength < $this->chunkSize || $this->startByte + $contentLength >= $this->remoteFileSize);

        if (!$isLastChunk) {
            return;
        }

        $originalPath = $this->localPath;
        if (file_exists($this->localPath)) {
            $info            = pathinfo($originalPath);
            $this->localPath = $info['dirname'] . '/' . $info['filename'] . '.wpstg';
        }

        $uploadPath = $originalPath . '.' . self::UPLOADING_EXTENSION;
        if (!file_exists($uploadPath)) {
            $this->message   = 'Upload file does not exist';
            $this->success   = false;
            $this->completed = true;
            return;
        }

        if (!rename($uploadPath, $this->localPath)) {
            $this->message   = 'Failed to rename upload file';
            $this->success   = false;
            $this->completed = true;
            return;
        }

        if (!$isUnknownSize && file_exists($this->localPath) && filesize($this->localPath) < $this->remoteFileSize) {
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
                'Accept-Encoding' => 'identity',
                'Cache-Control'   => 'no-cache',
            ],
        ];

        $response = $this->makeRemoteRequest($args);
        if ($this->isSuccessfulRemoteFileExistsResponse($response)) {
            return true;
        }

        if ($this->isAuthenticationFailure($response)) {
            $this->setAuthenticationFailureMessage($response);
            $this->success   = false;
            $this->completed = true;

            return false;
        }

        $response = $this->makeRemoteRequest([
            'method'              => 'GET',
            'timeout'             => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify'           => false,
            'limit_response_size' => self::FILE_SIZE_PROBE_RESPONSE_LIMIT,
            'headers'             => [
                'Accept-Encoding' => 'identity',
                'Cache-Control'   => 'no-cache',
                'Range'           => 'bytes=0-0',
            ],
        ]);

        if ($this->isAuthenticationFailure($response)) {
            $this->setAuthenticationFailureMessage($response);
            $this->success   = false;
            $this->completed = true;

            return false;
        }

        if ($this->isSuccessfulRemoteFileExistsResponse($response)) {
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
            'method'              => 'GET',
            'timeout'             => Hooks::applyFilters('wpstg.downloader_timeout', $this->timeout),
            'sslverify'           => false,
            'limit_response_size' => max(1, $endByte - $startByte + 1),
            'headers'             => [
                'Accept-Encoding' => 'identity',
                'Cache-Control'   => 'no-cache',
                'Range'           => "bytes={$startByte}-{$endByte}",
            ],
        ];

        $response = $this->makeRemoteRequest($args);

        if ($this->isAuthenticationFailure($response)) {
            $this->setAuthenticationFailureMessage($response);

            return '';
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        if (is_array($response) && in_array((int)$responseCode, [200, 206])) {
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

        if (!$this->followRedirects && !isset($args['redirection'])) {
            $args['redirection'] = 0;
        }

        if (!isset($args['method'])) {
            $args['method'] = 'POST';
        }

        if (!empty($this->customHeaders)) {
            $args['headers'] = array_merge(
                isset($args['headers']) ? $args['headers'] : [],
                $this->customHeaders
            );
        }

        return wp_remote_request($this->remoteUrl, $args);
    }

    /**
     * @param array|\WP_Error $response
     * @param array<int, int> $acceptedCodes
     * @return bool
     */
    private function hasSuccessfulResponseCode($response, array $acceptedCodes): bool
    {
        if (!is_array($response)) {
            return false;
        }

        return in_array((int)wp_remote_retrieve_response_code($response), $acceptedCodes, true);
    }

    /**
     * @param array|\WP_Error $response
     * @return bool
     */
    private function isSuccessfulRemoteFileExistsResponse($response): bool
    {
        return $this->hasSuccessfulResponseCode($response, [200, 206]);
    }

    /**
     * @param array|\WP_Error $response
     * @param int             $requestedBytes
     * @return bool
     */
    private function hasSuccessfulChunkResponse($response, int $requestedBytes): bool
    {
        if (!is_array($response)) {
            $this->message = esc_html__('Unable to download remote file chunk.', 'wp-staging');
            return false;
        }

        $responseCode = (int)wp_remote_retrieve_response_code($response);
        if ($responseCode === 206) {
            return true;
        }

        if ($responseCode === 200 && $this->remoteFileSize <= 0 && $this->allowUnknownRemoteFileSize) {
            return true;
        }

        if ($responseCode === 200 && $this->startByte === 0 && $requestedBytes >= $this->remoteFileSize) {
            return true;
        }

        if ($responseCode === 200) {
            $this->message   = esc_html__('The remote server does not support partial file downloads. It returned the full file while WP Staging requested only a chunk.', 'wp-staging');
            $this->errorCode = self::ERROR_PARTIAL_DOWNLOAD_NOT_SUPPORTED;
            return false;
        }

        $this->message = sprintf(
            esc_html__('Unable to download remote file chunk. The remote server returned HTTP status %d.', 'wp-staging'),
            $responseCode
        );

        return false;
    }

    /**
     * @return string
     */
    private function getChunkDownloadPath(): string
    {
        return $this->getUploadPath() . '.chunk';
    }

    /**
     * @param array<string, mixed>|\WP_Error $response
     * @param bool $isUnknownSize
     * @return bool
     */
    private function isUnknownSizeEndOfFileResponse($response, bool $isUnknownSize): bool
    {
        return $isUnknownSize &&
            $this->startByte > 0 &&
            is_array($response) &&
            (int)wp_remote_retrieve_response_code($response) === 416;
    }

    /**
     * When size is unknown and the server ignores Range, the streamed chunk
     * contains bytes from the beginning of the file. Skip already downloaded
     * bytes before appending the new tail.
     *
     * @param array<string, mixed>|\WP_Error $response
     * @param bool $isUnknownSize
     * @param int $downloadedBytes
     * @param int $requestedBytes
     * @return int
     */
    private function getChunkBytesToSkip($response, bool $isUnknownSize, int $downloadedBytes, int $requestedBytes): int
    {
        if (!$isUnknownSize || (int)wp_remote_retrieve_response_code($response) !== 200) {
            return 0;
        }

        if ($downloadedBytes <= $requestedBytes) {
            return 0;
        }

        return $this->startByte;
    }

    /**
     * @param string $path
     * @return void
     */
    private function deleteFile(string $path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * @param array|\WP_Error $response
     * @return int
     */
    private function getContentLengthFromResponse($response): int
    {
        return $this->getPositiveIntegerHeaderFromResponse($response, 'content-length');
    }

    /**
     * @param array|\WP_Error $response
     * @return int
     */
    private function getContentRangeSizeFromResponse($response): int
    {
        foreach ($this->getHeaderValues($response, 'content-range') as $headerValue) {
            if (preg_match('/\/\s*(\d+)\s*$/', $headerValue, $matches)) {
                return (int)$matches[1];
            }
        }

        return 0;
    }

    /**
     * @param array|\WP_Error $response
     * @param string $headerName
     * @return int
     */
    private function getPositiveIntegerHeaderFromResponse($response, string $headerName): int
    {
        $headerValues = array_reverse($this->getHeaderValues($response, $headerName));
        foreach ($headerValues as $headerValue) {
            $parts = array_reverse(explode(',', $headerValue));
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '' && ctype_digit($part) && (int)$part > 0) {
                    return (int)$part;
                }
            }
        }

        return 0;
    }

    /**
     * @param array|\WP_Error $response
     * @param string $headerName
     * @return array<int, string>
     */
    private function getHeaderValues($response, string $headerName): array
    {
        $headerValue = wp_remote_retrieve_header($response, $headerName);
        if (empty($headerValue)) {
            $headers = wp_remote_retrieve_headers($response);
            if (!is_array($headers)) {
                return [];
            }

            foreach ($headers as $name => $value) {
                if (strtolower((string)$name) === strtolower($headerName)) {
                    $headerValue = $value;
                    break;
                }
            }

            if (empty($headerValue)) {
                return [];
            }
        }

        if (!is_array($headerValue)) {
            return [(string)$headerValue];
        }

        $values = [];
        foreach ($headerValue as $value) {
            if (is_scalar($value)) {
                $values[] = (string)$value;
            }
        }

        return $values;
    }

    /**
     * @param array|\WP_Error $response
     * @return bool
     */
    private function isAuthenticationFailure($response): bool
    {
        if (is_wp_error($response) || !is_array($response)) {
            return false;
        }

        $responseCode = (int) wp_remote_retrieve_response_code($response);

        return in_array($responseCode, [401, 403], true);
    }

    /**
     * @param array|\WP_Error $response
     * @return void
     */
    private function setAuthenticationFailureMessage($response)
    {
        $responseCode = (int) wp_remote_retrieve_response_code($response);

        $this->message = sprintf(
            esc_html__('Authentication failed (%d). Please check your HTTP authentication credentials.', 'wp-staging'),
            $responseCode
        );
    }
}
