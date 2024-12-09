<?php

namespace WPStaging\Framework\Network;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Utils\Sanitize;

class RemoteDownloader
{
    /** @var int */
    const TIMEOUT = 60;

    /** @var int */
    private $chunkSize = 5 * 1024 * 1024;

    /** @var Auth */
    protected $auth;

    /** @var Sanitize */
    protected $sanitize;

    /** @var string */
    protected $remoteFileUrl = '';

    /** @var string */
    protected $localFilePath = '';

    /** @var string */
    protected $fileName = '';

    /** @var int */
    protected $fileSize = 0;

    /** @var int */
    private $startByte = 0;

    /** @var int */
    private $endByte = 0;

    /** @var bool */
    private $isSuccess = false;

    /** @var bool */
    private $isProcessCompleted = false;

    /** @var string */
    private $response = '';

    public function __construct()
    {
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->auth     = WPStaging::make(Auth::class);
    }

    /**
     * @param string $url
     * @return void
     */
    public function setRemoteFileUrl(string $url)
    {
        $this->remoteFileUrl = $this->sanitize->sanitizeUrl($url);
    }

    /**
     * @param int $startByte
     * @return void
     */
    public function setStartByte(int $startByte)
    {
        $this->startByte = $this->sanitize->sanitizeInt($startByte);
    }

    /**
     * @return void
     */
    public function setLocalFilePath(string $localFilePath)
    {
        $this->localFilePath = $this->sanitize->sanitizePath($localFilePath);
    }

    /**
     * @param int $fileSize
     * @return void
     */
    public function setFileSize(int $fileSize)
    {
        $this->fileSize = $this->sanitize->sanitizeInt($fileSize);
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
     * @param string $fileName
     * @return void
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @see self::handleResponse()
     * @param bool $isSuccess
     * @return void
     */
    public function setIsSuccess(bool $isSuccess)
    {
        $this->isSuccess = $isSuccess;
    }

    /**
     * @see self::handleResponse()
     * @param bool $isProcessCompleted
     * @return void
     */
    public function setIsProcessCompleted(bool $isProcessCompleted)
    {
        $this->isProcessCompleted = $isProcessCompleted;
    }

    /**
     * @see self::handleResponse() {'message'}
     * @param string $response
     * @return void
     */
    public function setResponse(string $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return bool
     */
    public function getProcessStatus(): bool
    {
        return $this->isProcessCompleted;
    }

    /**
     * Make a WordPress remote POST request.
     *
     * @param array $arguments
     * @return array|\WP_Error
     */
    protected function makeWpRemotePost(array $arguments)
    {
        $arguments['user-agent'] = 'Mozilla/5.0 (compatible; wp-staging/' . WPStaging::getVersion() . '; +https://wp-staging.com)';
        $response                = wp_remote_post($this->remoteFileUrl, $arguments);
        $this->response          = wp_remote_retrieve_response_message($response);
        return $response;
    }

    /**
     * Download a chunk of the remote file.
     *
     * @return void
     */
    public function downloadFileChunk()
    {
        $this->endByte = $this->startByte + $this->chunkSize - 1;

        $arguments = [
            'method'    => 'GET',
            'timeout'   => self::TIMEOUT,
            'sslverify' => false,
            'headers'   => [
                'Range' => "bytes={$this->startByte}-{$this->endByte}",
            ],
        ];

        $response = $this->makeWpRemotePost($arguments);
        if (is_wp_error($response)) {
            $this->response           = $response->get_error_message();
            $this->isSuccess          = false;
            $this->isProcessCompleted = true;
            return;
        }

        $fileContent = wp_remote_retrieve_body($response);
        if (!empty($fileContent)) {
            $this->isSuccess = $this->writeLocalFile($fileContent);
            $contentLength   = strlen($fileContent);
            $this->updateProcessStatus($contentLength);
        }
    }

    /**
     * @param string $fileContent
     * @return bool
     */
    private function writeLocalFile(string $fileContent): bool
    {
        try {
            $localFile = fopen($this->localFilePath . '.uploading', 'ab');
            if ($localFile === false) {
                $this->response = 'Failed to open local file for writing.';
                return false;
            }

            $bytesWritten = fwrite($localFile, $fileContent);
            if ($bytesWritten === false) {
                $this->response = 'Failed to write to the local file.';
                return false;
            }

            if (fclose($localFile) === false) {
                $this->response = 'Failed to close the local file.';
                return false;
            }

            $localFile = null;

            return true;
        } catch (Exception $e) {
            $this->response = 'Error in writing Local File:' . $e->getMessage();
            return false;
        }
    }

    /**
     * @return void
     */
    public function handleResponse()
    {
        wp_send_json([
            'success'   => $this->isSuccess,
            'message'   => esc_html($this->response),
            'complete'  => $this->isProcessCompleted,
            'startByte' => $this->startByte,
            'fileSize'  => $this->fileSize
        ]);
    }

    /**
     * @return int
     */
    public function getRemoteFileSize(): int
    {
        $arguments = [
            'method'    => 'HEAD',
            'timeout'   => self::TIMEOUT,
            'sslverify' => false,
        ];

        $response = $this->makeWpRemotePost($arguments);
        if (is_wp_error($response)) {
            $this->response = $response->get_error_message();
            return 0;
        }

        $contentLength = wp_remote_retrieve_header($response, 'content-length');
        if (empty($contentLength)) {
            return 0;
        }

        return intval($contentLength);
    }

    /**
     * @param int $contentLength
     * @return void
     */
    public function updateProcessStatus(int $contentLength)
    {
        if ($contentLength >= $this->chunkSize) {
            return;
        }

        $originalFilePath = $this->localFilePath;
        $counter          = 1;

        while (file_exists($this->localFilePath)) {
            $info                = pathinfo($originalFilePath);
            $extension           = isset($info['extension']) ? '.' . $info['extension'] : '.wpstg';
            $this->localFilePath = $info['dirname'] . '/' . $info['filename'] . '-' . $counter . $extension;
            $counter++;
        }

        rename($originalFilePath . '.uploading', $this->localFilePath);

        if (file_exists($this->localFilePath) && filesize($this->localFilePath) < $this->fileSize) {
            // Check if the remote file still exists
            if (!$this->remoteFileExists()) {
                return;
            }

            $this->response           = __('File upload incomplete', 'wp-staging');
            $this->isSuccess          = false;
            $this->isProcessCompleted = true;
            $this->remoteFileExists();
            return;
        }


        $this->response           = __('File uploaded successfully', 'wp-staging');
        $this->isProcessCompleted = true;
    }

    /**
     * @return void
     */
    public function updateStartByte()
    {
        $this->startByte += $this->chunkSize;
    }

    /**
     * @return bool
     */
    public function remoteFileExists(): bool
    {
        $arguments = [
            'method'    => 'HEAD',
            'timeout'   => self::TIMEOUT,
            'sslverify' => false,
            'headers'   => [
                'Cache-Control' => 'no-cache',
            ]
        ];

        $response     = $this->makeWpRemotePost($arguments);
        $responseCode = wp_remote_retrieve_response_code($response);

        if (is_array($response) && !is_wp_error($response) && (int)$responseCode === 200) {
            return true;
        }

        $this->response           = __('File not available on remote server', 'wp-staging');
        $this->isSuccess          = false;
        $this->isProcessCompleted = true;
        return false;
    }

    /**
     * @return string
     */
    public function getRemoteFileContent(int $startByte, int $endByte): string
    {
        $arguments = [
            'method'    => 'GET',
            'timeout'   => self::TIMEOUT,
            'sslverify' => false,
            'headers'   => [
                'Cache-Control' => 'no-cache',
                'Range'         => "bytes=" . $startByte . "-" . $endByte,
            ]
        ];

        $response     = $this->makeWpRemotePost($arguments);
        $responseCode = wp_remote_retrieve_response_code($response);

        if (is_array($response) && !is_wp_error($response) && in_array((int)$responseCode, [200, 206])) {
            return wp_remote_retrieve_body($response);
        }

        return '';
    }
}
