<?php
/**
 * WP Staging | Restore.
 *
 * A standalone script to extract and restore backups.
 * This is a compressed, compiled script based on PHP, JS code, CSS and HTML.
 * If you are a developer who would like to get your hands on the sources of this file, please contact us at support@wp-staging.com.
 *
 * Version      : 1.0.3
 * Build Id     : 2804ef1fdd46
 * Build Date   : Feb 12, 2025 17:13:51 UTC
 * Support      : https://wp-staging.com/support/
 */
namespace { !getenv('wpstg-restorer-as-library') && exit; /**@wpstg-restorer-halt**/ }
namespace {
    if (version_compare(PHP_VERSION, '7.0', '<')) {
        exit("WP Staging Restore requires at least PHP version 7.0, current version " . PHP_VERSION . ".\n");
    }
    if (!getenv('wpstg-restorer-as-library') && (defined('ABSPATH') || defined('WPSTG_RESTORER'))) {
        exit("WP Staging Restore should run as a standalone.\n");
    }
    define('WPSTG_RESTORER', true);
    date_default_timezone_set('UTC');
    final class WPStagingRestorer
    {
        const MAX_MEMORY = 268435456;
        const MAX_TIMEOUT = 180;
        const MAX_TIMEOUT_EXTRACT = 60;
        const MAX_TIMEOUT_RESTORE = 60;
        const CHMOD_DIR = 0755;
        const CHMOD_FILE = 0644;
        const KB_IN_BYTES = 1024;
        const MB_IN_BYTES = 1048576;
        const GB_IN_BYTES = 1073741824;
        const EXTRACTION_THRESHOLD_PERCENTAGE_LIMIT = 85;
        private $appFile = 'wpstg-restore.php';
        private $buildId = '2804ef1fdd46';
        private $version = '1.0.3';
        private $backupVersion = '2.0.0';
        private $backupDir = 'wp-staging/backups';
        private $rootPath = null;
        private $uploadPath = null;
        private $backupPath = null;
        private $tmpPath = null;
        private $cachePath = null;
        private $logFile = null;
        private $dataServer = [];
        private $dataCookie = [];
        private $dataPost = [];
        private $dataGet = [];
        private $dataRequest = [];
        private $error = [];
        private $timerStart = null;
        private $maxProcessingTime = 10;
        private $wpCoreHandle = null;
        private $accessHandle = null;
        private $activateHandle = null;
        private $extractorHandle = null;
        private $restorerHandle = null;
        private $cacheHandle = null;
        private $fileHandle = null;
        private $pathIdentifier = null;
        private $viewHandle = null;
        private $backupListingHandle = null;
        private $classResolverHandle;
        public function __construct()
        {
            $this->timerStart = microtime(true);
            $this->rootPath   = realpath(__DIR__);
            $this->tmpPath    = $this->rootPath . '/wpstg-restore';
            $this->cachePath  = $this->tmpPath . '/cache';
            $this->uploadPath = $this->rootPath . '/wp-content/uploads';
            $this->backupPath = $this->uploadPath . '/' . $this->backupDir;
            $this->logFile    = $this->tmpPath . '/' . $this->setLogFilename();
            $this->captureFatalError();
            $this->setMaxResource();
            $this->classResolverHandle = new \WpstgRestorer\ClassResolver();
        }
        public function databaseImporterBindings()
        {
            $this->classResolverHandle->bindInstance(\WpstgRestorer\QueryInserter::class, $this->makeInstance(\WpstgRestorer\ExtendedInserterWithoutTransaction::class));
            $this->classResolverHandle->bindInstance(\WpstgRestorer\SubsiteManagerInterface::class, $this->makeInstance(\WpstgRestorer\SubsiteManager::class));
        }
        public function getPathIdentifier()
        {
            if ($this->pathIdentifier === null) {
                $this->pathIdentifier = $this->makeInstance(\WpstgRestorer\PathIdentifier::class);
            }
            return $this->pathIdentifier;
        }
        public function setMeta($key, $value)
        {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        public function getMeta()
        {
            return (object)[
                'timerStart'        => $this->timerStart,
                'appFile'           => $this->appFile,
                'buildId'           => $this->buildId,
                'version'           => $this->version,
                'backupVersion'     => $this->backupVersion,
                'backupDir'         => $this->backupDir,
                'rootPath'          => $this->rootPath,
                'uploadPath'        => $this->uploadPath,
                'backupPath'        => $this->backupPath,
                'tmpPath'           => $this->tmpPath,
                'cachePath'         => $this->cachePath,
                'maxProcessingTime' => $this->maxProcessingTime,
                'dataServer'        => $this->dataServer,
                'dataCookie'        => $this->dataCookie,
                'dataPost'          => $this->dataPost,
                'dataGet'           => $this->dataGet,
                'dataRequest'       => $this->dataRequest,
            ];
        }
        public function getHandle(string $caller, $useHandle = null)
        {
            $handles = [
                'access'        => $this->accessHandle,
                'activate'      => $this->activateHandle,
                'cache'         => $this->cacheHandle,
                'file'          => $this->fileHandle,
                'wpcore'        => $this->wpCoreHandle,
                'extractor'     => $this->extractorHandle,
                'restorer'      => $this->restorerHandle,
                'backupListing' => $this->backupListingHandle,
            ];
            $callerKey = strtolower(str_replace('WpstgRestorer\\', '', $caller));
            if (empty($useHandle) && array_key_exists($callerKey, $handles)) {
                unset($handles[$callerKey]);
            }
            $useHandle = array_fill_keys((array)$useHandle, 1);
            if (array_key_exists($callerKey, $handles) && array_key_exists($callerKey, $useHandle)) {
                throw new \LogicException(sprintf('Invalid caller: %s', $caller));
            }
            if ($handleKeys = array_intersect_key($handles, $useHandle)) {
                $handles = $handleKeys;
            }
            foreach ($handles as $name => $object) {
                if ($object === null) {
                    $classHandle    = new \ReflectionClass("WpstgRestorer\\" . $name);
                    $handles[$name] = $classHandle->newInstance($this);
                }
            }
            return (object)$handles;
        }
        public function makeInstance(string $id, bool $useCache = true)
        {
            return $this->classResolverHandle->resolve($id, $useCache);
        }
        public function bindInstance(string $id, $instance)
        {
            $this->classResolverHandle->bindInstance($id, $instance);
        }
        public function getBackupMetadata(string $filePath): \WpstgRestorer\BackupMetadata
        {
            $filePathCache  = $this->cacheHandle->getCacheFile($filePath, 'backupmeta');
            $backupMetadata = new \WpstgRestorer\BackupMetadata();
            if (($data = $this->cacheHandle->get($filePath, 'backupmeta', $filePathCache)) !== null) {
                $backupMetadata->hydrate($data);
                return $backupMetadata;
            }
            $backupMetadata->hydrateByFilePath($filePath);
            return $backupMetadata;
        }
        private function setLogFilename(): string
        {
            $host  = empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'];
            $stamp = date('Ymd');
            return 'wpstg-restore-' . substr(md5($stamp . $host . $stamp), 0, 12) . '-' . $stamp . '.log';
        }
        private function hasHttps(): bool
        {
            if (!empty($this->dataServer['HTTP_CF_VISITOR'])) {
                $cfVisitorObject = json_decode($this->dataServer['HTTP_CF_VISITOR']);
                if (isset($cfVisitorObject->schema) && $cfVisitorObject->schema === 'https') {
                    return true;
                }
            }
            if (!empty($this->dataServer['HTTP_X_FORWARDED_PROTO']) && $this->dataServer['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return true;
            }
            if (!empty($this->dataServer['HTTPS']) && in_array(strtolower($this->dataServer['HTTPS']), ['on', '1'])) {
                return true;
            }
            if (!empty($this->dataServer['SERVER_PORT']) && (int)$this->dataServer['SERVER_PORT'] === 443) {
                return true;
            }
            return false;
        }
        public function siteUrl(): string
        {
            if (empty($this->dataServer['HTTP_HOST']) || empty($this->dataServer['SCRIPT_FILENAME']) || empty($this->dataServer['PHP_SELF']) || empty($this->dataServer['REQUEST_URI'])) {
                return '';
            }
            $schema            = $this->hasHttps() ? 'https://' : 'http://';
            $url               = $schema . $this->dataServer['HTTP_HOST'];
            $scriptFilenameDir = dirname($this->dataServer['SCRIPT_FILENAME']);
            $path              = '';
            if ($this->rootPath === $scriptFilenameDir . '/') {
                $path = preg_replace('@/[^/]*$@i', '', $this->dataServer['PHP_SELF']);
                return rtrim($url . $path, '/');
            }
            if (strpos($this->rootPath, $scriptFilenameDir) !== false) {
                $subDirectory = substr($this->rootPath, strpos($this->rootPath, $scriptFilenameDir) + strlen($scriptFilenameDir));                $path         = preg_replace('@/[^/]*$@i', '', $this->dataServer['REQUEST_URI']) . $subDirectory;
            } else {
                $path = $this->dataServer['REQUEST_URI'];
            }
            return rtrim($url . $path, '/');
        }
        public function userAgent(): string
        {
            $url = $this->siteUrl();
            if (empty($url)) {
                $url = 'https://wp-staging.com/';
            }
            return 'Mozilla/5.0 (compatible; wpstg-restorer/' . $this->version . '; +' . $url . ')';
        }
        private function requirementCheck(): bool
        {
            if (!is_writable($this->rootPath)) {
                $this->error['rootpath-writable'] = 'Current working directory is not writable.';
            }
            if (!class_exists('ZipArchive')) {
                $this->error['zip-ext'] = 'PHP ZipArchive extension is not available.';
            }
            if (!extension_loaded('curl') || !function_exists('curl_init')) {
                $this->error['curl-ext'] = 'PHP cURL extension is not available.';
            }
            if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
                $this->error['mysqli-ext'] = 'PHP mysqli extension is not available.';
            }
            return empty($this->error);
        }
        public function getBootupError(): array
        {
            return $this->error;
        }
        public function addBootupError(string $key, string $text): array
        {
            $this->error[$key] = $text;
            return $this->error;
        }
        private function createWorkingDir(): bool
        {
            if (!empty($this->error)) {
                return false;
            }
            clearstatcache();
            if (is_dir($this->tmpPath) && is_dir($this->cachePath)) {
                return true;
            }
            if (!$this->mkdir($this->tmpPath)) {
                $this->error['tmp-dir'] = "Can't create working directory";
                return false;
            }
            if (!$this->mkdir($this->cachePath)) {
                $this->error['cache-dir'] = "Can't create cache directory";
                return false;
            }
            $this->fileHandle->preventAccessToDirectory($this->tmpPath);
            $this->fileHandle->preventAccessToDirectory($this->cachePath);
            return true;
        }
        public function setDateTime(\DateTime $dateTime): string
        {
            $defaultDateFormat = 'M j, Y';
            $defaultTimeFormat = 'H:i:s';
            if (!function_exists('get_date_from_gmt') || !function_exists('get_option')) {
                return $dateTime->format($defaultDateFormat . ' ' . $defaultTimeFormat) . ' UTC';
            }
            if (!($dateFormat = get_option('date_format'))) {
                $dateFormat = $defaultDateFormat;
            }
            $dateFormat = str_replace('F', 'M', $dateFormat);
            if (!($timeFormat = get_option('time_format'))) {
                $timeFormat = $defaultTimeFormat;
            }
            return get_date_from_gmt($dateTime->format('Y-m-d H:i:s'), $dateFormat . ' ' . $timeFormat);
        }
        private function isFunctionDisabled(string $name): bool
        {
            static $disableFunctions = [];
            if (empty($disableFunctions)) {
                $disableFunctions = array_map(function ($input) {
                    return trim($input);
                }, explode(',', ini_get('disable_functions')));
            }
            return in_array($name, $disableFunctions);
        }
        private function convertTobytes(string $value): int
        {
            $value = strtolower(trim($value));
            $bytes = (int) $value;
            if (false !== strpos($value, 'g')) {
                $bytes *= self::GB_IN_BYTES;
            } elseif (false !== strpos($value, 'm')) {
                $bytes *= self::MB_IN_BYTES;
            } elseif (false !== strpos($value, 'k')) {
                $bytes *= self::KB_IN_BYTES;
            }
            return min($bytes, PHP_INT_MAX);
        }
        public function maxMemoryLimit(int $bytes = 0): int
        {
            static $memoryLimit;
            if (isset($memoryLimit) && (int)$bytes === 0) {
                return (int)$memoryLimit;
            }
            $memoryLimit = $this->convertTobytes(ini_get('memory_limit'));
            $bytes       = (int)($bytes > 0 ? $bytes : self::MAX_MEMORY);
            if ($bytes > $memoryLimit) {
                if ($bytes < PHP_INT_MAX) {
                    $bytes += self::KB_IN_BYTES;                }
                ini_set('memory_limit', $bytes);
            }
            $memoryLimit = $this->convertTobytes(ini_get('memory_limit'));
            return $memoryLimit;
        }
        public function getMemoryLimit(): int
        {
            return $this->maxMemoryLimit();
        }
        public function maxExecutionTime(int $second = 0): int
        {
            static $maxExecutionTime;
            if (isset($maxExecutionTime) && (int)$second === 0) {
                return $maxExecutionTime;
            }
            $maxExecutionTime = (int)ini_get('max_execution_time');
            $second           = (int)( $second > 0 ? $second : self::MAX_TIMEOUT );
            if ($second > 0 && $maxExecutionTime > 0 && !$this->isFunctionDisabled('set_time_limit')) {
                $second += 1;
                set_time_limit($second);
                $maxExecutionTime = (int)ini_get('max_execution_time');
            }
            if ($maxExecutionTime > 10) {
                $maxExecutionTime -= 1;
            }
            return $maxExecutionTime;
        }
        public function isMaxExecutionTime(float $second = 0): bool
        {
            $second = (int) ( $second > 0 ? $second : $this->maxExecutionTime());
            if ($second > 0 && (microtime(true) - $this->timerStart) > $second) {
                return true;
            }
            return false;
        }
        public function isTimeExceed(float $second, float $secondBefore): bool
        {
            if ($second > 0 && (microtime(true) - $secondBefore) > $second) {
                return true;
            }
            return false;
        }
        public function isMaxMemory(): bool
        {
            return memory_get_usage(true) >= $this->maxMemoryLimit();
        }
        public function isMemoryExceeded(): bool
        {
            return memory_get_usage(true) >= ($this->maxMemoryLimit() - self::KB_IN_BYTES);
        }
        public function isThreshold(): bool
        {
            if (memory_get_usage(true) >= ($this->maxMemoryLimit() * self::EXTRACTION_THRESHOLD_PERCENTAGE_LIMIT / 100)) {
                return true;
            }
            if ($this->isMaxExecutionTime((int)($this->maxProcessingTime * self::EXTRACTION_THRESHOLD_PERCENTAGE_LIMIT / 100))) {
                return true;
            }
            return false;
        }
        private function setMaxResource()
        {
            $this->maxMemoryLimit();
            $this->maxExecutionTime();
            ini_set('default_socket_timeout', 180);
            ini_set('pcre.backtrack_limit', PHP_INT_MAX);
        }
        public function rtrimSlash(string $path): string
        {
            return rtrim($path, '\\/');
        }
        public function ltrimSlash(string $path): string
        {
            return ltrim($path, '\\/');
        }
        public function normalizePath(string $path): string
        {
            $streamWrapper = '';
            if (($schemeSeparator = strpos($path, '://')) !== false) {
                if (in_array(substr($path, 0, $schemeSeparator), stream_get_wrappers(), true)) {
                    list( $streamWrapper, $path ) = explode('://', $path, 2);
                    $streamWrapper .= '://';
                }
            }
            $path = str_replace('\\', '/', $path);
            $path = preg_replace('|(?<=.)/+|', '/', $path);
            if (substr($path, 1, 1) === ':') {
                $path = ucfirst($path);
            }
            $path = $streamWrapper . $path;
            if (substr($path, -3) !== '://') {
                $path = $this->rtrimSlash($path);
            }
            $path = !empty($path) ? $path : '/';
            return $path;
        }
        public function isStringBeginsWith(string $haystack, string $needle): bool
        {
            return strpos($haystack, $needle) === 0;
        }
        public function isSerialized(string $data, bool $strict = true): bool
        {
            if (!is_string($data)) {
                return false;
            }
            $data = trim($data);
            if ($data === 'N;') {
                return true;
            }
            if (strlen($data) < 4) {
                return false;
            }
            if ($data[1] !== ':') {
                return false;
            }
            if ($strict) {
                $lastc = substr($data, -1);
                if ($lastc !== ';' && $lastc !== '}') {
                    return false;
                }
            } else {
                $semicolon = strpos($data, ';');
                $brace     = strpos($data, '}');
                if ($semicolon === false && $brace === false) {
                    return false;
                }
                if ($semicolon !== false && $semicolon < 3) {
                    return false;
                }
                if ($brace !== false && $brace < 4) {
                    return false;
                }
            }
            $token = $data[0];
            switch ($token) {
                case 's':
                    if ($strict) {
                        if ('"' !== substr($data, -2, 1)) {
                            return false;
                        }
                    } elseif (function_exists('str_contains') && !str_contains($data, '"') || strpos($data, '"') === false) {
                        return false;
                    }
                    break;
                case 'a':
                case 'O':
                case 'E':
                    return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
                case 'b':
                case 'i':
                case 'd':
                    $end = $strict ? '$' : '';
                    return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
            }
            return false;
        }
        public function sizeFormat(int $bytes, int $decimals = 2)
        {
            $quant = [
                'GB' => self::GB_IN_BYTES,
                'MB' => self::MB_IN_BYTES,
                'KB' => self::KB_IN_BYTES,
                'B'  => 1,
            ];
            if ($bytes === 0) {
                return number_format(0, $decimals) . ' B';
            }
            foreach ($quant as $unit => $mag) {
                if ((float) $bytes >= $mag) {
                    return number_format($bytes / $mag, $decimals) . ' ' . $unit;
                }
            }
            return false;
        }
        public function mkdir(string $dirPath, $fromLine = null): bool
        {
            if (is_dir($dirPath)) {
                return true;
            }
            $this->captureError(true, ['param' => ['dir' => $dirPath], 'line' => $fromLine, 'method' => __METHOD__]);
            $status = mkdir($dirPath, self::CHMOD_DIR, true);
            $this->captureError(false);
            return $status && is_dir($dirPath);
        }
        public function rmdir(string $dirPath, $fromLine = null): bool
        {
            if (!is_dir($dirPath)) {
                return true;
            }
            if (!$this->fileHandle->isDirEmpty($dirPath)) {
                return false;
            }
            $this->captureError(true, ['param' => ['dir' => $dirPath], 'line' => $fromLine, 'method' => __METHOD__]);
            $status = rmdir($dirPath);
            $this->captureError(false);
            return $status && !is_dir($dirPath);
        }
        public function chmod(string $filePath, $mode = false, $fromLine = null): bool
        {
            $mode = !$mode && is_dir($filePath) ? self::CHMOD_DIR : self::CHMOD_FILE;
            $this->captureError(true, ['param' => ['file' => $filePath, 'mode' => '0' . decoct($mode)], 'line' => $fromLine, 'method' => __METHOD__]);
            clearstatcache(true, $filePath);
            $status = chmod($filePath, $mode);
            $this->captureError(false);
            return $status;
        }
        public function copy(string $srcPath, string $dstPath, $fromLine = null): bool
        {
            if (!file_exists($srcPath)) {
                return false;
            }
            $this->captureError(true, ['param' => ['from' => $srcPath, 'to' => $dstPath], 'line' => $fromLine, 'method' => __METHOD__]);
            $status = copy($srcPath, $dstPath);
            if ($status) {
                chmod($dstPath, self::CHMOD_FILE);
            }
            $this->captureError(false);
            return $status && file_exists($dstPath);
        }
        public function unlink(string $filePath, $fromLine = null): bool
        {
            if (!file_exists($filePath)) {
                return true;
            }
            $this->captureError(true, ['param' => ['file' => $filePath], 'line' => $fromLine, 'method' => __METHOD__]);
            $status = unlink($filePath);
            $this->captureError(false);
            return $status && !file_exists($filePath);
        }
        public function escapeString(string $output, array $exclude = []): string
        {
            $content = filter_var($output, FILTER_SANITIZE_SPECIAL_CHARS);
            if (empty($exclude)) {
                return $content;
            }
            foreach ($exclude as $tag) {
                $tagSanitized = filter_var($tag, FILTER_SANITIZE_SPECIAL_CHARS);
                $content      = str_replace($tagSanitized, $tag, $content);
            }
            return $content;
        }
        public function stripRootPath(string $input): string
        {
            return $this->ltrimSlash(str_replace($this->rootPath, '', $input));
        }
        private function isAjaxRequest(): bool
        {
            if (empty($this->dataServer)) {
                $this->registerInput();
            }
            if (!empty($this->dataServer['HTTP_X_WPSTG_RESTORER']) && strtolower($this->dataServer['HTTP_X_WPSTG_RESTORER']) === 'ajaxrequest') {
                return true;
            }
            if (!empty($this->dataServer['HTTP_X_REQUESTED_WITH']) && strtolower($this->dataServer['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                return true;
            }
            if (!empty($this->dataServer['HTTP_ACCEPT']) && strpos($this->dataServer['HTTP_ACCEPT'], 'application/json') !== false) {
                return true;
            }
            return !empty($this->dataServer['CONTENT_TYPE']) && strpos($this->dataServer['CONTENT_TYPE'], 'application/json') !== false;
        }
        public function tokenIntersect($token): string
        {
            $tokens = ['c99fee0377b5' => [53,98,55,55,51,48,101,101,102,57,57,99], '98567b801284' => [110,111,105,116,99,97,95,100,100,101],'718779752b85' => [101,115,110,101,99,105,108],'572d4e421e5e' => [108,114,117],'9d0307ba8eb2' => [101,109,97,110,95,109,101,116,105],'9bad570433b0' => [101,115,110,101,99,105,108,95,107,99,101,104,99],'6bd68ce0cd6e' => [101,115,110,101,99,105,108,95,101,116,97,118,105,116,99,97],'7ae828cad3e6' => [79,82,80,32,71,78,73,71,65,84,83,32,80,87],'783a61caf5f9' => [109,111,99,46,103,110,105,103,97,116,115,45,112,119,47,47,58,115,112,116,116,104],'afd813e3d0a7' => [101,115,110,101,99,105,76,32,108,97,110,111,115,114,101,80],'d7dcb88e6154' => [101,115,110,101,99,105,76,32,121,99,110,101,103,65],'beb07f0d144b' => [101,115,110,101,99,105,76,32,115,115,101,110,105,115,117,66],'2a9c26508842' => [101,115,110,101,99,105,76,32,114,101,112,111,108,101,118,101,68],'337d315fa590' => [101,108,98,97,108,105,97,118,97,32,116,111,110,32,121,101,107,32,101,115,110,101,99,105,76],'c66c00ae9f18' => [114,101,114,101,102,101,114]];
            if (!is_array($tokens) || empty($tokens[$token])) {
                return $token;
            }
            return implode('', array_map(function ($integer) {
                if (!preg_match('@^\d+$@', $integer)) {
                    return $integer;
                }
                $integer = (int)$integer;
                if ($integer < 0 || $integer > 255) {
                    return $integer;
                }
                return chr($integer);
            }, array_reverse($tokens[$token])));
        }
        public function log($data, $method = null, bool $isFlush = false): bool
        {
            if ($isFlush && file_exists($this->logFile)) {
                unlink($this->logFile);
            }
            if (empty($data)) {
                return false;
            }
            if (is_string($data)) {
                $data = ['message' => $data];
            }
            if ($data instanceof \Throwable) {
                $error = [
                    'code'    => $data->getCode(),
                    'message' => $data->getMessage(),
                    'file'    => $data->getFile(),
                    'line'    => $data->getLine(),
                    'trace'   => "\n" . trim($data->getTraceAsString())
                ];
                $data = $error;
            }
            if (is_array($data) || is_object($data)) {
                $data = (array)$data;
                if (!empty($method)) {
                    $data = array_merge(['method' => $method], $data);
                }
                $data = substr_replace(print_r($data, true), '', 0, 5);
                $data = preg_replace('@=\>\s+\((.*?)\)\n+\)@s', "=> ($1)\n)", str_replace("=> Array\n", '=>', $data));
            }
            $log = "[" . date('M j H:i:s') . "] " . trim($data) . "\n";
            error_log($log, 3, $this->logFile);
            return true;
        }
        private function getErrorTypeString($errorNo = null)
        {
            $errorTypes = [
                E_ERROR             => 'ERROR',
                E_PARSE             => 'PARSE',
                E_USER_ERROR        => 'USER_ERROR',
                E_COMPILE_ERROR     => 'COMPILE_ERROR',
                E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
                E_WARNING           => 'WARNING',
                E_NOTICE            => 'NOTICE',
                E_CORE_ERROR        => 'CORE_ERROR',
                E_CORE_WARNING      => 'CORE_WARNING',
                E_COMPILE_WARNING   => 'COMPILE_WARNING',
                E_USER_WARNING      => 'USER_WARNING',
                E_USER_NOTICE       => 'USER_NOTICE',
                E_STRICT            => 'STRICT',
                E_DEPRECATED        => 'DEPRECATED',
                E_USER_DEPRECATED   => 'USER_DEPRECATED',
                E_ALL               => 'ALL',
            ];
            if ($errorNo !== null) {
                if (!empty($errorTypes[$errorNo])) {
                    return $errorTypes[$errorNo];
                }
                return $errorNo;
            }
            return $errorTypes;
        }
        public function captureError(bool $start = false, array $extra = []): bool
        {
            if ($start === false) {
                return restore_error_handler();
            }
            set_error_handler(function ($type, $message, $file, $line) use ($extra) {
                $error = [
                    'type'    => $this->getErrorTypeString($type),
                    'message' => $message,
                    'file'    => $file,
                    'line'    => $line
                ];
                if (!empty($extra)) {
                    $error = array_merge($error, $extra);
                }
                $this->log($error);
            });
            return true;
        }
        public function suppressError(bool $start = true)
        {
            if ($start === false) {
                return restore_error_handler();
            }
            set_error_handler(function () {});
            return true;
        }
        private function captureFatalError()
        {
            error_reporting(E_ALL);
            ini_set('html_errors', 0);
            ini_set('display_errors', 0);
            $method = __METHOD__;
            register_shutdown_function(
                function () use ($method) {
                    $error = error_get_last();
                    if (empty($error) || !is_array($error) || $this->appFile !== basename($error['file'])) {
                        return;
                    }
                    $errorNo       = $error['type'];
                    $error['type'] = $this->getErrorTypeString($errorNo);
                    $this->log($error, $method);
                    if (in_array($errorNo, [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_ERROR], true)) {
                        $errorMessage = $this->stripRootPath($error['message']);
                        if ($this->isAjaxRequest()) {
                            if (empty($this->dataRequest['wpstg-restorer-action'])) {
                                $this->response('<div id="wpstg-restorer-console" class="show">' . $this->escapeString($errorMessage) . '</div>');
                            }
                            $this->response(['success' => false, 'data' => $errorMessage]);
                        }
                        $error = '<html><head><title>500 - Internal Server Error</title></head><body><pre>' . $this->escapeString($errorMessage) . '</pre></body></html>';
                        $this->response($error, 500, 'text/html; charset=UTF-8');
                    }
                }
            );
        }
        private function sendHeader(string $header, bool $replace = true, int $responseCode = 0)
        {
            if (!headers_sent()) {
                header($header, $replace, $responseCode);
            }
        }
        private function noCacheHeader()
        {
            $this->sendHeader('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0, proxy-revalidate');
        }
        private function response($data, int $statusCode = 200, $contentType = 'text/plain')
        {
            if (!is_object($this->viewHandle)) {
                exit("Can't load View class");
            }
            if (in_array($data, ['print-js', 'print-css', 'print-logo', 'print-loader', 'print-favicon-ico', 'print-favicon-png32'])) {
                switch ($data) {
                    case 'print-js':
                        $contentType = 'text/javascript';
                        break;
                    case 'print-css':
                        $contentType = 'text/css';
                        break;
                    case 'print-favicon-ico':
                        $contentType = 'image/x-icon';
                        break;
                    case 'print-favicon-png32':
                    case 'print-logo':
                        $contentType = 'image/png';
                        break;
                    case 'print-loader':
                        $contentType = 'image/gif';
                        break;
                }
                $this->sendHeader('Cache-Control: max-age=14400, immutable, stale-while-revalidate=86400, stale-if-error=86400');
                $this->sendHeader(sprintf('Content-Type: %s', $contentType), true, $statusCode);
                $this->viewHandle->render($data);
                exit;
            }
            $this->noCacheHeader();
            if (is_array($data)) {
                if (!empty($data['saveLog'])) {
                    $log = ($data['saveLog'] instanceof \Throwable) ? $data['saveLog'] : ( is_string($data['saveLog']) ? $data['saveLog'] : $data['data']);
                    $this->log($log, !empty($data['saveLogId']) ? $data['saveLogId'] : null);
                    unset($data['saveLog'], $data['saveLogId']);
                }
                if (!empty($data['data']) && is_string($data['data'])) {
                    $data['data'] = $this->stripRootPath($data['data']);
                }
                $this->sendHeader('Content-Type: application/json; charset=UTF-8', true, $statusCode);
                exit(json_encode($data));
            }
            if (strpos($data, 'page-') === 0) {
                $this->sendHeader('Content-Type: text/html; charset=UTF-8', true, $statusCode);
                $this->viewHandle->render($data);
                exit;
            }
            $this->sendHeader(sprintf('Content-Type: %s', $contentType), true, $statusCode);
            exit($this->stripRootPath($data));        }
        private function registerHandle()
        {
            $this->fileHandle   = new WpstgRestorer\File($this);
            $this->cacheHandle  = new WpstgRestorer\Cache($this);
            $this->wpCoreHandle = new WpstgRestorer\WpCore($this);
            $this->classResolverHandle->bindInstance(\WpstgRestorer\DirectoryInterface::class, $this->wpCoreHandle->getDirectoryAdapter());
            $this->classResolverHandle->bindInstance(\WpstgRestorer\DatabaseInterface::class, $this->wpCoreHandle->getDatabaseAdapter());
            $this->backupListingHandle = new WpstgRestorer\BackupListing($this);
            $this->accessHandle        = new WpstgRestorer\Access($this);
            $this->extractorHandle     = new WpstgRestorer\Extractor($this);
            $this->restorerHandle      = new WpstgRestorer\Restorer($this);
            $this->activateHandle      = new WpstgRestorer\Activate($this);
            $this->viewHandle          = new WpstgRestorer\View($this);
        }
        private function registerInput()
        {
            if (!($this->dataServer = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_SPECIAL_CHARS))) {
                $this->dataServer = [];
            }
            if (!($this->dataCookie = filter_input_array(INPUT_COOKIE, FILTER_SANITIZE_SPECIAL_CHARS))) {
                $this->dataCookie = [];
            }
            if (!($this->dataPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS))) {
                $this->dataPost = [];
            }
            if (!($this->dataGet = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS))) {
                $this->dataGet = [];
            }
            if (!($this->dataRequest = array_merge($this->dataPost, $this->dataGet))) {
                $this->dataRequest = [];
            }
        }
        private function bootup()
        {
            $this->registerInput();
            $this->registerHandle();
            $this->requirementCheck();
            $this->createWorkingDir();
        }
        private function listen()
        {
            if (!empty($this->dataRequest['wpstg-restorer-action'])) {
                $action = $this->dataRequest['wpstg-restorer-action'];
                if (!$this->accessHandle->verifyToken()) {
                    $this->response(['success' => false, 'data' => 'Invalid token']);
                }
                if ($action !== 'verify-backup-filename' && !$this->accessHandle->hasSession()) {
                    $this->response(['success' => false, 'data' => 'Invalid session']);
                }
                switch ($action) {
                    case 'verify-backup-filename':
                        $this->response($this->accessHandle->verify());
                        break;
                    case 'request-activation':
                        $this->response($this->activateHandle->requestActivation());
                        break;
                    case 'access-terminate':
                        $this->response($this->accessHandle->revoke());
                        break;
                    case 'wpcore-install':
                        $status = $this->wpCoreHandle->runTask();
                        $text   = !$status ? 'Failed to run WpCore::runTask()' : 'Run WpCore::runTask() was successful';
                        if (!$status) {
                            $taskResponse = $this->wpCoreHandle->getTaskResponse();
                            if (!empty($taskResponse['data']['content'])) {
                                $text = $taskResponse['data']['content'];
                            }
                            $this->wpCoreHandle->resetTaskStatus();
                        }
                        $this->response(['success' => $status, 'data' => $text]);
                        break;
                    case 'wpcore-install-status':
                        $this->response($this->wpCoreHandle->getTaskResponse());
                        break;
                    case 'wpcore-setup-db':
                        $this->response($this->wpCoreHandle->saveDbConfig());
                        break;
                    case 'wpcore-reset-db':
                        $this->response(['success' => $this->wpCoreHandle->resetDbConfig(), 'data' => 'Executed WpCore::resetDbConfig()']);
                        break;
                    case 'wpcore-setup-site':
                        $this->response($this->wpCoreHandle->installSite());
                        break;
                    case 'wpcore-setup-complete':
                        $this->response($this->wpCoreHandle->installComplete());
                        break;
                    case 'extract-backup':
                        $this->response($this->extractorHandle->extractBackup());
                        break;
                    case 'extract-item':
                        $this->response($this->extractorHandle->extractItem());
                        break;
                    case 'restore-backup':
                        $this->response($this->restorerHandle->restoreBackup());
                        break;
                    case 'extract-stop':
                    case 'extract-item-stop':
                    case 'restore-stop':
                        $this->response($this->extractorHandle->processStop());
                        break;
                    case 'reload-backup-list':
                        $this->response(['success' => $this->backupListingHandle->resetBackupList(), 'data' => 'Executed BackupListing::resetBackupList()']);
                        break;
                    default:
                        $this->response(['success' => false, 'data' => 'Invalid request']);
                }
            }
            if (!empty($this->dataRequest['wpstg-restorer-page'])) {
                if (!$this->accessHandle->hasSession()) {
                    $this->response('Session expired');
                }
                $page = $this->dataRequest['wpstg-restorer-page'];
                if ($page !== 'page-logout' && !$this->activateHandle->isActive()) {
                    $this->response('Invalid access');
                }
                switch ($page) {
                    case 'page-backup-list':
                    case 'page-backup-extract':
                    case 'page-backup-content':
                    case 'page-backup-restore':
                    case 'page-logout':
                        $this->response($page);
                        break;
                    default:
                        $this->response('Not found', 404);
                }
            }
            if (!empty($this->dataRequest['wpstg-restorer-file'])) {
                $file = $this->dataRequest['wpstg-restorer-file'];
                switch ($file) {
                    case 'print-js':
                    case 'print-css':
                    case 'print-logo':
                    case 'print-loader':
                    case 'print-favicon-ico':
                    case 'print-favicon-png32':
                        $this->response($file);
                        break;
                    default:
                        $this->response('Not found', 404);
                }
            }
        }
        private function index()
        {
            if (getenv('wpstg-restorer-as-library')) {
                return;
            }
            if (PHP_SAPI === 'cli') {
                if (!empty($this->error)) {
                    foreach ($this->error as $type => $text) {
                        printf("%s%8s: %s\n", $type, ' ', $text);
                    }
                    exit(1);
                }
                printf("WP Staging Restore v%s\n", $this->version);
                exit(0);
            }
            $this->wpCoreHandle->enableMaintenance(false);
            $this->response('page-main');
        }
        public function run()
        {
            $this->bootup();
            $this->listen();
            $this->index();
        }
    }
}
namespace { if (!defined('KB_IN_BYTES')) { define('KB_IN_BYTES', \WPStagingRestorer::KB_IN_BYTES); } if (!defined('MB_IN_BYTES')) { define('MB_IN_BYTES', \WPStagingRestorer::MB_IN_BYTES); } if (!defined('GB_IN_BYTES')) { define('GB_IN_BYTES', \WPStagingRestorer::GB_IN_BYTES); } if (!function_exists('wpstgIsWindowsOs')) { function wpstgIsWindowsOs(): bool { return strncasecmp(PHP_OS, 'WIN', 3) === 0; } } }
namespace WpstgRestorer {
    interface DirectoryInterface { public function getBackupDirectory(): string; public function getTmpDirectory(): string; public function getPluginUploadsDirectory(bool $refresh = false): string; public function getUploadsDirectory(bool $refresh = false): string; public function getPluginsDirectory(): string; public function getMuPluginsDirectory(): string; public function getAllThemesDirectories(): array; public function getActiveThemeParentDirectory(): string; public function getLangsDirectory(): string; public function getAbsPath(): string; public function getWpContentDirectory(): string; }
    interface IndexLineInterface { public function getContentStartOffset(): int; public function getStartOffset(): int; public function getIdentifiablePath(): string; public function getUncompressedSize(): int; public function getCompressedSize(): int; public function getIsCompressed(): bool; public function isIndexLine(string $indexLine): bool; public function readIndexLine(string $indexLine): IndexLineInterface; public function validateFile(string $filePath, string $pathForErrorLogging = ''); }
    interface InterfaceDatabaseClient { public function query($query); public function realQuery($query, $isExecOnly = false); public function escape($input); public function errno(); public function error(); public function version(); public function fetchAll($result); public function fetchAssoc($result); public function fetchRow($result); public function fetchObject($result); public function numRows($result); public function freeResult($result); public function insertId(); public function foundRows(); public function getLink(); }
    interface DatabaseInterface { public function getClient(): InterfaceDatabaseClient; public function getPrefix(): string; public function getBasePrefix(): string; public function getSqlVersion(bool $compact = false, bool $refresh = false): string; }
    interface ArrayableInterface { public function toArray(); }
    interface SubsiteManagerInterface { public function initialize(DatabaseImporterDto $databaseImporterDto); public function updateSubsiteId(); public function isTableFromDifferentSubsite(string $query): bool; }
    interface DatabaseSearchReplacerInterface { public function getSearchAndReplace(string $homeURL, string $siteURL, string $absPath = '', $destinationSiteUploadURL = null): SearchReplace; }
    trait ApplyFiltersTrait { protected function applyFilters(string $filter, $value, ...$args) { if (class_exists('\WPStaging\Framework\Facades\Hooks')) { return \WPStaging\Framework\Facades\Hooks::applyFilters($filter, $value, ...$args); } return $value; } }
    trait DebugLogTrait { protected function debugLog(string $message, string $type = 'info', bool $addInErrorLog = false) { if (function_exists('\WPStaging\functions\debug_log')) { \WPStaging\functions\debug_log($message, $type, $addInErrorLog); } } }
    trait EndOfLinePlaceholderTrait { use WindowsOsTrait; public function replaceEOLsWithPlaceholders($subject) { if ($subject === null) { return $subject; } if ($this->isWindowsOs()) { return $subject; } return empty($subject) ? $subject : str_replace([PHP_EOL], ['{WPSTG_EOL}'], $subject); } public function replacePlaceholdersWithEOLs($subject) { if ($subject === null) { return $subject; } if (strpos($subject, '{WPSTG_EOL}') === false) { return $subject; } if ($this->isWindowsOs()) { if (!empty($this->logger)) { $this->logger->warning(sprintf('Filename %s contains EOL character, but Windows doesn\'t support EOL in file name, plugin/theme using that file might not work.', $subject)); } return $subject; } return empty($subject) ? $subject : str_replace(['{WPSTG_EOL}'], [PHP_EOL], $subject); } }
    trait FormatTrait { public function formatSize($size, int $decimals = 2): string { if ((int)$size < 1) { return ''; } $units = ['B', "KB", "MB", "GB", "TB"]; $size = (int)$size; $base = log($size) / log(1000); $pow = pow(1000, $base - floor($base)); return round($pow, $decimals) . ' ' . $units[(int)floor($base)]; } }
    trait HydrateTrait { protected $excludeHydrate = []; public function hydrate(array $data = []) { foreach ($data as $key => $value) { $propertiesToExclude = array_merge($this->excludeHydrate, ['excludeHydrate']); if (in_array($key, $propertiesToExclude, true)) { continue; } try { $this->hydrateByMethod('set' . ucfirst($key), $value); } catch (\TypeError $e) { $this->debugLog($e->getMessage()); } catch (\Exception $e) { $this->debugLog($e->getMessage()); } } return $this; } public function hydrateProperties(array $data = []) { foreach ($data as $key => $value) { if (!property_exists($this, $key)) { $this->debugLog("Trying to hydrate DTO with property that does not exist. {$key}"); continue; } $this->{$key} = $value; } return $this; } protected function debugLog(string $message) { if (!function_exists('\WPStaging\functions\debug_log')) { return; } if (class_exists('\WPStaging\Core\WPStaging') && \WPStaging\Core\WPStaging::areLogsSilenced()) { return; } \WPStaging\functions\debug_log($message); } private function hydrateByMethod(string $method, $value) { if (!method_exists($this, $method)) { if (!is_string($value)) { $value = wp_json_encode($value, JSON_UNESCAPED_SLASHES); } throw new \Exception(sprintf("Trying to hydrate DTO with value that does not exist. %s::%s(%s)", get_class($this), $method, $value)); } $method = new \ReflectionMethod($this, $method); $params = $method->getParameters(); if (!isset($params[0]) || count($params) > 1) { throw new \Exception(sprintf( 'Class %s setter method %s does not have a first parameter or has more than one parameter', static::class, $method )); } $param = $params[0]; if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) { $class = $param->getType() && !$param->getType()->isBuiltin() ? new \ReflectionClass($param->getType()->getName()) : null; } else { $class = $param->getClass(); } if (!$value || !$class) { $method->invoke($this, $value); return; } $method->invoke($this, $this->getClassAsValue($class, $value)); } private function getClassAsValue(\ReflectionClass $class, $value) { $className = $class->getName(); if (!$value instanceof \DateTime && $className === 'DateTime') { return (new DateTimeAdapter())->getDateTime($value); } $obj = new $className(); if (is_array($value) && method_exists($obj, 'hydrate')) { $obj->hydrate($value); } return $obj; } }
    trait WindowsOsTrait { public function isWindowsOs(): bool { return strncasecmp(PHP_OS, 'WIN', 3) === 0; } }
    trait DateCreatedTrait { private $dateCreated; private $dateCreatedTimezone; public function getDateCreated() { return (string)$this->dateCreated; } public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; } public function getDateCreatedTimezone() { return (string)$this->dateCreatedTimezone; } public function setDateCreatedTimezone($dateCreatedTimezone) { $this->dateCreatedTimezone = $dateCreatedTimezone; } }
    trait IsExportingTrait { private $isExportingPlugins = false; private $isExportingMuPlugins = false; private $isExportingThemes = false; private $isExportingUploads = false; private $isExportingOtherWpContentFiles = false; private $isExportingOtherWpRootFiles = false; private $backupExcludedDirectories = []; private $isExportingDatabase = false; public function getIsExportingPlugins() { return (bool)$this->isExportingPlugins; } public function setIsExportingPlugins($isExportingPlugins) { $this->isExportingPlugins = $isExportingPlugins === true || $isExportingPlugins === 'true'; } public function getIsExportingMuPlugins() { return (bool)$this->isExportingMuPlugins; } public function setIsExportingMuPlugins($isExportingMuPlugins) { $this->isExportingMuPlugins = $isExportingMuPlugins === true || $isExportingMuPlugins === 'true'; } public function getIsExportingThemes() { return (bool)$this->isExportingThemes; } public function setIsExportingThemes($isExportingThemes) { $this->isExportingThemes = $isExportingThemes === true || $isExportingThemes === 'true'; } public function getIsExportingUploads() { return (bool)$this->isExportingUploads; } public function setIsExportingUploads($isExportingUploads) { $this->isExportingUploads = $isExportingUploads === true || $isExportingUploads === 'true'; } public function getIsExportingOtherWpContentFiles() { return (bool)$this->isExportingOtherWpContentFiles; } public function setIsExportingOtherWpContentFiles($isExportingOtherWpContentFiles) { $this->isExportingOtherWpContentFiles = $isExportingOtherWpContentFiles === true || $isExportingOtherWpContentFiles === 'true'; } public function getIsExportingOtherWpRootFiles(): bool { return (bool)$this->isExportingOtherWpRootFiles; } public function setIsExportingOtherWpRootFiles(bool $isExportingOtherWpRootFiles) { $this->isExportingOtherWpRootFiles = $isExportingOtherWpRootFiles === true || $isExportingOtherWpRootFiles === 'true'; } public function getBackupExcludedDirectories(): array { return $this->backupExcludedDirectories; } public function setBackupExcludedDirectories(array $backupExcludedDirectories) { $this->backupExcludedDirectories = $backupExcludedDirectories; } public function getIsExportingDatabase() { return (bool)$this->isExportingDatabase; } public function setIsExportingDatabase($isExportingDatabase) { $this->isExportingDatabase = $isExportingDatabase === true || $isExportingDatabase === 'true'; } }
    trait WithPluginsThemesMuPluginsTrait { private $plugins = []; private $themes = []; private $muPlugins = []; public function getPlugins() { return $this->plugins; } public function setPlugins(array $plugins) { $this->plugins = $plugins; } public function getThemes() { return $this->themes; } public function setThemes(array $themes) { $this->themes = $themes; } public function getMuPlugins() { return $this->muPlugins; } public function setMuPlugins(array $muPlugins) { $this->muPlugins = $muPlugins; } }
    trait WithBackupIdentifier { protected $listedMultipartBackups = []; public function checkPartByIdentifier(string $identifier, string $input) { return preg_match("#{$identifier}(.[0-9]+)?.wpstg$#", $input); } public function isBackupPart(string $name) { $dbExtension = DatabaseImporter::FILE_FORMAT; $dbIdentifier = PartIdentifier::DATABASE_PART_IDENTIFIER; if (preg_match("#{$dbIdentifier}(.[0-9]+)?.{$dbExtension}$#", $name)) { return true; } $pluginIdentifier = PartIdentifier::PLUGIN_PART_IDENTIFIER; $mupluginIdentifier = PartIdentifier::MU_PLUGIN_PART_IDENTIFIER; $themeIdentifier = PartIdentifier::THEME_PART_IDENTIFIER; $uploadIdentifier = PartIdentifier::UPLOAD_PART_IDENTIFIER; $otherIdentifier = PartIdentifier::OTHER_WP_CONTENT_PART_IDENTIFIER; $otherWpRootIdentifier = PartIdentifier::OTHER_WP_ROOT_PART_IDENTIFIER; $identifiers = "({$dbIdentifier}|{$pluginIdentifier}|{$mupluginIdentifier}|{$themeIdentifier}|{$uploadIdentifier}|{$otherIdentifier}|{$otherWpRootIdentifier})"; if ($this->checkPartByIdentifier($identifiers, $name)) { return true; } return false; } public function clearListedMultipartBackups() { $this->listedMultipartBackups = []; } public function isListedMultipartBackup(string $filename, bool $shouldAddBackup = true) { $id = $this->extractBackupIdFromFilename($filename); if (in_array($id, $this->listedMultipartBackups)) { return true; } if ($shouldAddBackup) { $this->listedMultipartBackups[] = $id; } return false; } public function extractBackupIdFromFilename(string $filename) { if (strpos($filename, '.' . PartIdentifier::DATABASE_PART_IDENTIFIER . '.' . DatabaseImporter::FILE_FORMAT) !== false) { return $this->extractBackupIdFromDatabaseBackupFilename($filename); } $fileInfos = explode('_', $filename); $fileInfos = $fileInfos[count($fileInfos) - 1]; return explode('.', $fileInfos)[0]; } protected function extractBackupIdFromDatabaseBackupFilename(string $filename) { $filename = str_replace('.' . PartIdentifier::DATABASE_PART_IDENTIFIER . '.' . DatabaseImporter::FILE_FORMAT, '', $filename); $lastDotPosition = strrpos($filename, '.'); $filename = substr($filename, 0, $lastDotPosition); $fileInfos = explode('_', $filename); return $fileInfos[count($fileInfos) - 1]; } }
    trait I18nTrait { protected function translate(string $message, string $domain) { if (function_exists('__')) { return __($message, $domain); } return $message; } protected function escapeHtmlAndTranslate(string $message, string $domain) { if (function_exists('esc_html__')) { return esc_html__($message, $domain); } return $message; } }
    trait SlashTrait { protected function untrailingslashit(string $string): string { return rtrim($string, '/'); } protected function trailingslashit(string $string): string { return $this->untrailingslashit($string) . '/'; } }
    trait SerializeTrait { protected function isSerialized(string $data, bool $strict = true): bool { if (!is_string($data)) { return false; } $data = trim($data); if ($data === 'N;') { return true; } if (strlen($data) < 4) { return false; } if ($data[1] !== ':') { return false; } if ($strict) { $lastc = substr($data, -1); if ($lastc !== ';' && $lastc !== '}') { return false; } } else { $semicolon = strpos($data, ';'); $brace = strpos($data, '}'); if ($semicolon === false && $brace === false) { return false; } if ($semicolon !== false && $semicolon < 3) { return false; } if ($brace !== false && $brace < 4) { return false; } } $token = $data[0]; switch ($token) { case 's': if ($strict) { if ('"' !== substr($data, -2, 1)) { return false; } } elseif (function_exists('str_contains') && !str_contains($data, '"') || strpos($data, '"') === false) { return false; } case 'a': case 'O': case 'E': return (bool) preg_match("/^{$token}:[0-9]+:/s", $data); case 'b': case 'i': case 'd': $end = $strict ? '$' : ''; return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data); } return false; } }
    trait UrlTrait { public function getUrlWithoutScheme(string $string): string { return (string)preg_replace('#^https?://#', '', rtrim($string, '/')); } public function base64Decode(string $input): string { $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="; $i = 0; $output = ""; $input = preg_replace("[^A-Za-z0-9\+\/\=]", "", $input); do { $enc1 = strpos($keyStr, substr($input, $i++, 1)); $enc2 = strpos($keyStr, substr($input, $i++, 1)); $enc3 = strpos($keyStr, substr($input, $i++, 1)); $enc4 = strpos($keyStr, substr($input, $i++, 1)); $chr1 = ($enc1 << 2) | ($enc2 >> 4); $chr2 = (($enc2 & 15) << 4) | ($enc3 >> 2); $chr3 = (($enc3 & 3) << 6) | $enc4; $output = $output . chr((int)$chr1); if ($enc3 != 64) { $output = $output . chr((int)$chr2); } if ($enc4 != 64) { $output = $output . chr((int)$chr3); } } while ($i < strlen($input)); return urldecode($output); } }
    trait ArrayableTrait { public function toArray() { $reflection = new \ReflectionClass($this); $props = $reflection->getProperties( \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE ); $data = []; foreach ($props as $prop) { $propName = $prop->getName(); if ($propName === 'excludeHydrate') { continue; } $prop->setAccessible(true); $value = $prop->getValue($this); if ($value instanceof \DateTime) { $value = $value->format('U'); } if (is_object($value) && method_exists($value, 'toArray')) { $value = $value->toArray(); } $data[$propName] = $value; } return $data; } }
    trait NetworkConstantTrait { protected $prefix = ''; protected $client; public function setDatabaseClient(InterfaceDatabaseClient $client) { $this->client = $client; } public function setPrefix(string $prefix) { $this->prefix = $prefix; } protected function getCurrentNetworkPath(): string { if (defined('PATH_CURRENT_SITE')) { return constant('PATH_CURRENT_SITE'); } return $this->getFromSiteTable('path'); } protected function getCurrentNetworkDomain(): string { if (defined('DOMAIN_CURRENT_SITE')) { return constant('DOMAIN_CURRENT_SITE'); } return $this->getFromSiteTable('domain'); } private function getFromSiteTable(string $field): string { $siteTable = $this->prefix . 'site'; $result = $this->client->query("SELECT {$field} FROM {$siteTable}"); $value = $this->client->fetchAssoc($result)[0][$field]; $this->client->freeResult($result); return $value; } }
    abstract class QueryInserter { use I18nTrait; use ApplyFiltersTrait; protected $client; protected $databaseImporterDto; protected $limitedMaxAllowedPacket; protected $realMaxAllowedPacket; protected $maxInnoDbLogSize; protected $currentDbVersion; protected $backupDbVersion; protected $warnings = []; public function setDbVersions(string $currentDbVersion, string $backupDbVersion) { $this->currentDbVersion = $currentDbVersion; $this->backupDbVersion = $backupDbVersion; } protected $error = false; public function initialize(InterfaceDatabaseClient $client, DatabaseImporterDto $databaseImporterDto) { $this->client = $client; $this->databaseImporterDto = $databaseImporterDto; $this->setMaxAllowedPackage(); $this->setInnoDbLogFileSize(); $this->warnings = []; } public function getWarnings(): array { return $this->warnings; } abstract public function processQuery(&$insertQuery); abstract public function commit(); protected function exec(&$query) { $result = $this->client->query($query); return $result !== false; } protected function setMaxAllowedPackage() { try { if (isset($this->client->isSQLite) && $this->client->isSQLite) { $realMaxAllowedPacket = 16777216; } else { $result = $this->client->query("SHOW VARIABLES LIKE 'max_allowed_packet'"); $row = $this->client->fetchAssoc($result); $this->client->freeResult($result); $realMaxAllowedPacket = $this->getNumberFromResult($row); } $limitedMaxAllowedPacket = max(16 * KB_IN_BYTES, 0.9 * $realMaxAllowedPacket); $limitedMaxAllowedPacket = min(2 * MB_IN_BYTES, $limitedMaxAllowedPacket); } catch (\Exception $e) { $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9; } catch (\Error $ex) { $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9; } $limitedMaxAllowedPacket = $this->applyFilters('wpstg.restore.database.maxAllowedPacket', $limitedMaxAllowedPacket); $this->limitedMaxAllowedPacket = (int)$limitedMaxAllowedPacket; $this->realMaxAllowedPacket = (int)$realMaxAllowedPacket; } protected function setInnoDbLogFileSize() { try { $innoDbLogFileSize = $this->client->query("SHOW VARIABLES LIKE 'innodb_log_file_size';"); $innoDbLogFileSizeResult = $this->client->fetchAssoc($innoDbLogFileSize); $innoDbLogFileSize = $this->getNumberFromResult($innoDbLogFileSizeResult); $innoDbLogFileGroups = $this->client->query("SHOW VARIABLES LIKE 'innodb_log_files_in_group';"); $innoDbLogFileGroupsResult = $this->client->fetchAssoc($innoDbLogFileGroups); $innoDbLogFileGroups = $this->getNumberFromResult($innoDbLogFileGroupsResult); $innoDbLogSize = $innoDbLogFileSize * $innoDbLogFileGroups; $innoDbLogSize = max(1 * MB_IN_BYTES, $innoDbLogSize * 0.9); $innoDbLogSize = min(64 * MB_IN_BYTES, $innoDbLogSize); } catch (\Exception $e) { $innoDbLogSize = 9 * MB_IN_BYTES; } catch (\Error $ex) { $innoDbLogSize = 9 * MB_IN_BYTES; } $innoDbLogSize = $this->applyFilters('wpstg.restore.database.innoDbLogSize', $innoDbLogSize); $this->maxInnoDbLogSize = (int)$innoDbLogSize; } private function getNumberFromResult($result) { if ( is_array($result) && array_key_exists('Value', $result) && is_numeric($result['Value']) && (int)$result['Value'] > 0 ) { return (int)$result['Value']; } else { throw new \UnexpectedValueException(); } } public function getLastError() { return $this->error; } protected function doQueryExceedsMaxAllowedPacket($query) { $this->error = false; if (strlen($query) >= $this->realMaxAllowedPacket) { $this->error = sprintf( 'Query: "%s" was skipped because it exceeded the mySQL maximum allowed packet size. Query size: %s | max_allowed_packet: %s. Follow this link: %s for details ', substr($query, 0, 1000) . '...', size_format(strlen($query)), size_format($this->limitedMaxAllowedPacket), 'https://wp-staging.com/docs/increase-max_allowed_packet-size-in-mysql/' ); return true; } return false; } protected function addWarning(string $message) { $this->warnings[] = $message; } }
    class SubsiteDto implements ArrayableInterface
    {
        use ArrayableTrait;
        protected $siteId;
        protected $blogId;
        protected $domain;
        protected $path;
        protected $siteUrl;
        protected $homeUrl;
        public static function createFromSiteData(array $siteData): SubsiteDto
        {
            $subsiteDto = new self();
            $subsiteDto->hydrate($siteData);
            return $subsiteDto;
        }
        public function hydrate(array $data)
        {
            $this->setSiteId($data['site_id'] ?? $data['siteId']);
            $this->setBlogId($data['blog_id'] ?? $data['blogId']);
            $this->setDomain($data['domain']);
            $this->setPath($data['path']);
            $this->setSiteUrl($data['site_url'] ?? $data['siteUrl']);
            $this->setHomeUrl($data['home_url'] ?? $data['homeUrl']);
        }
        public function getSiteId(): int
        {
            return $this->siteId;
        }
        public function setSiteId(int $siteId)
        {
            $this->siteId = $siteId;
        }
        public function getBlogId(): int
        {
            return $this->blogId;
        }
        public function setBlogId(int $blogId)
        {
            $this->blogId = $blogId;
        }
        public function getDomain(): string
        {
            return $this->domain;
        }
        public function setDomain(string $domain)
        {
            $this->domain = $domain;
        }
        public function getPath(): string
        {
            return $this->path;
        }
        public function setPath(string $path)
        {
            $this->path = $path;
        }
        public function getSiteUrl(): string
        {
            return $this->siteUrl;
        }
        public function setSiteUrl(string $siteUrl)
        {
            $this->siteUrl = $siteUrl;
        }
        public function getHomeUrl(): string
        {
            return $this->homeUrl;
        }
        public function setHomeUrl(string $homeUrl)
        {
            $this->homeUrl = $homeUrl;
        }
    }
    abstract class AbstractSearchReplacer implements DatabaseSearchReplacerInterface { use SlashTrait; use ApplyFiltersTrait; const FILTER_CURRENT_SCHEME_SAME_SITE = 'wpstg.backup.restore.use_current_scheme_on_same_site'; protected $search = []; protected $replace = []; protected $sourceSiteUrl = ''; protected $sourceHomeUrl = ''; protected $sourceSiteHostname = ''; protected $sourceHomeHostname = ''; protected $sourceSiteUploadURL = ''; protected $destinationSiteUrl = ''; protected $destinationHomeUrl = ''; protected $destinationSiteHostname = ''; protected $destinationHomeHostname = ''; protected $destinationSiteUploadURL = ''; protected $matchingScheme = false; protected $sourceAbsPath = ''; protected $plugins = []; protected $requireCslashEscaping = null; protected $isWpBakeryActive = false; protected $isMultisite = false; protected $isSubsiteSearchReplace = false; protected $subsitesSearchReplacer; public function __construct(SubsitesSearchReplacer $subsitesSearchReplacer) { $this->subsitesSearchReplacer = $subsitesSearchReplacer; } public function setIsWpBakeryActive(bool $isWpBakeryActive) { $this->isWpBakeryActive = $isWpBakeryActive; } public function setSourceAbsPath(string $sourceAbsPath) { $this->sourceAbsPath = $sourceAbsPath; } public function setSourcePlugins(array $plugins) { $this->plugins = $plugins; } public function setSourceUrls(string $sourceSiteUrl, string $sourceHomeUrl, string $sourceSiteUploadURL) { $this->sourceSiteUrl = $this->untrailingslashit($sourceSiteUrl); $this->sourceHomeUrl = $this->untrailingslashit($sourceHomeUrl); $this->sourceSiteUploadURL = $this->untrailingslashit($sourceSiteUploadURL); } public function setupSubsitesSearchReplacer(BackupMetadata $backupMetadata, int $currentSubsiteId) { $this->subsitesSearchReplacer->setupSubsitesAdjuster($backupMetadata, $currentSubsiteId); $this->isMultisite = true; } public function getSearchAndReplace(string $destinationSiteUrl, string $destinationHomeUrl, string $absPath = '', $destinationSiteUploadURL = null): SearchReplace { if (empty($absPath) && defined('ABSPATH')) { $absPath = ABSPATH; } $this->setupSearchReplaceUrls($destinationSiteUrl, $destinationHomeUrl, $destinationSiteUploadURL); if ($this->isMultisite) { $this->replaceSubsitesUrls($destinationSiteUrl, $destinationHomeUrl); } $this->replaceAbsPath($absPath); foreach ($this->search as $k => $searchItem) { if ($this->replace[$k] === $searchItem) { unset($this->search[$k]); unset($this->replace[$k]); } } $this->search = array_values($this->search); $this->replace = array_values($this->replace); $searchReplaceToSort = array_combine($this->search, $this->replace); $searchReplaceToSort = $this->applyFilters('wpstg.backup.restore.searchreplace', $searchReplaceToSort, $absPath, $this->sourceSiteUrl, $this->sourceHomeUrl, $this->destinationSiteUrl, $this->destinationHomeUrl); uksort($searchReplaceToSort, function ($item1, $item2) { if (strlen($item1) == strlen($item2)) { return 0; } return (strlen($item1) > strlen($item2)) ? -1 : 1; }); $orderedSearch = array_keys($searchReplaceToSort); $orderedReplace = array_values($searchReplaceToSort); return (new SearchReplace()) ->setSearch($orderedSearch) ->setReplace($orderedReplace) ->setWpBakeryActive($this->isWpBakeryActive); } public function buildHostname(string $url): string { $parsedUrl = parse_url($url); if (!is_array($parsedUrl) || !array_key_exists('host', $parsedUrl)) { throw new \UnexpectedValueException("Bad URL format, cannot proceed."); } $hostname = $parsedUrl['host']; if (array_key_exists('port', $parsedUrl)) { $hostname = $hostname . ':' . $parsedUrl['port']; } if (array_key_exists('path', $parsedUrl)) { $hostname = $this->trailingslashit($hostname) . trim($parsedUrl['path'], '/'); } return $hostname; } protected function setupSearchReplaceUrls(string $destinationSiteUrl, string $destinationHomeUrl, $destinationSiteUploadURL = null) { $this->sourceSiteHostname = $this->untrailingslashit($this->buildHostname($this->sourceSiteUrl)); $this->sourceHomeHostname = $this->untrailingslashit($this->buildHostname($this->sourceHomeUrl)); $this->destinationSiteUrl = $this->untrailingslashit($destinationSiteUrl); $this->destinationHomeUrl = $this->untrailingslashit($destinationHomeUrl); $this->destinationSiteHostname = $this->untrailingslashit($this->buildHostname($this->destinationSiteUrl)); $this->destinationHomeHostname = $this->untrailingslashit($this->buildHostname($this->destinationHomeUrl)); if (!$this->isSubsiteSearchReplace) { $this->destinationSiteUploadURL = $destinationSiteUploadURL; $this->prepareUploadURLs(); } $this->matchingScheme = parse_url($this->sourceSiteUrl, PHP_URL_SCHEME) === parse_url($this->destinationSiteUrl, PHP_URL_SCHEME); if (!$this->matchingScheme) { $this->replaceMultipleSchemes(); return; } $this->replaceGenericScheme(); } protected function replaceSubsitesUrls(string $destinationSiteUrl, string $destinationHomeUrl) { $subsites = $this->subsitesSearchReplacer->getSubsitesToReplace($destinationSiteUrl, $destinationHomeUrl); $this->isSubsiteSearchReplace = true; foreach ($subsites as $subsite) { $this->sourceHomeUrl = $subsite['homeUrl']; $this->sourceSiteUrl = $subsite['siteUrl']; $this->setupSearchReplaceUrls($subsite['adjustedSiteUrl'], $subsite['adjustedHomeUrl']); } } protected function replaceAbsPath(string $absPath) { if ($this->sourceAbsPath === $absPath) { return; } $this->search[] = $this->sourceAbsPath; $this->search[] = addcslashes($this->sourceAbsPath, '/'); $this->search[] = urlencode($this->sourceAbsPath); $this->replace[] = $absPath; $this->replace[] = addcslashes($absPath, '/'); $this->replace[] = urlencode($absPath); if (urlencode($this->sourceAbsPath) !== rawurlencode($this->sourceAbsPath)) { $this->search[] = rawurlencode($this->sourceAbsPath); $this->replace[] = rawurlencode($absPath); } if ($this->normalizePath($this->sourceAbsPath) !== $this->sourceAbsPath) { $this->search[] = $this->normalizePath($this->sourceAbsPath); $this->search[] = $this->normalizePath(addcslashes($this->sourceAbsPath, '/')); $this->search[] = $this->normalizePath(urlencode($this->sourceAbsPath)); $this->replace[] = $this->normalizePath($absPath); $this->replace[] = $this->normalizePath(addcslashes($absPath, '/')); $this->replace[] = $this->normalizePath(urlencode($absPath)); if ($this->normalizePath(urlencode($this->sourceAbsPath)) !== $this->normalizePath(rawurlencode($this->sourceAbsPath))) { $this->search[] = $this->normalizePath(rawurlencode($this->sourceAbsPath)); $this->replace[] = $this->normalizePath(rawurlencode($absPath)); } } } protected function replaceGenericScheme() { if ($this->isIdenticalSiteHostname()) { $this->replaceGenericHomeScheme(); return; } $this->replaceURLs($this->sourceSiteHostname, $this->destinationSiteHostname); $this->replaceUploadURLs(); $this->replaceGenericHomeScheme(); } protected function replaceGenericHomeScheme() { if (!$this->isCrossDomain()) { return; } if ($this->isIdenticalHomeHostname()) { return; } $this->replaceURLs($this->sourceHomeHostname, $this->destinationHomeHostname); } protected function replaceUploadURLs() { if ($this->isIdenticalUploadURL()) { return; } $sourceUploadURLWithoutScheme = $this->trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL; $destinationUploadURLWithoutScheme = $this->trailingslashit($this->destinationSiteHostname) . $this->destinationSiteUploadURL; $this->replaceURLs($sourceUploadURLWithoutScheme, $destinationUploadURLWithoutScheme); } protected function replaceURLs(string $sourceURL, string $destinationURL, bool $doubleSlashPrefix = true) { $prefix = $doubleSlashPrefix ? '//' : ''; $sourceGenericProtocol = $prefix . $sourceURL; $destinationGenericProtocol = $prefix . $destinationURL; $sourceGenericProtocolJsonEscaped = addcslashes($sourceGenericProtocol, '/'); $destinationGenericProtocolJsonEscaped = addcslashes($destinationGenericProtocol, '/'); $this->search[] = $sourceGenericProtocol; $this->search[] = $sourceGenericProtocolJsonEscaped; $this->search[] = urlencode($sourceGenericProtocol); $this->replace[] = $destinationGenericProtocol; $this->replace[] = $destinationGenericProtocolJsonEscaped; $this->replace[] = urlencode($destinationGenericProtocol); if ($this->isExtraCslashEscapingRequired()) { $this->search[] = addcslashes($sourceGenericProtocolJsonEscaped, '/'); $this->replace[] = addcslashes($destinationGenericProtocolJsonEscaped, '/'); } if (strpos($sourceURL, 'www.') === 0) { $this->search[] = $prefix . substr($sourceURL, 4); $this->replace[] = $destinationGenericProtocol; } } protected function replaceMultipleSchemes() { if ($this->isIdenticalSiteHostname() && !$this->isUseCurrentSchemeOnSameSite()) { $this->replaceMultipleHomeSchemes(); $this->replaceMultipleSchemesUploadURL(); return; } $sourceSiteHostnameJsonEscapedHttps = addcslashes('https://' . $this->sourceSiteHostname, '/'); $sourceSiteHostnameJsonEscapedHttp = addcslashes('http://' . $this->sourceSiteHostname, '/'); $this->search[] = 'https://' . $this->sourceSiteHostname; $this->search[] = 'http://' . $this->sourceSiteHostname; $this->search[] = $sourceSiteHostnameJsonEscapedHttps; $this->search[] = $sourceSiteHostnameJsonEscapedHttp; $this->search[] = urlencode('https://' . $this->sourceSiteHostname); $this->search[] = urlencode('http://' . $this->sourceSiteHostname); $this->replace[] = $this->destinationSiteUrl; $this->replace[] = $this->destinationSiteUrl; $this->replace[] = addcslashes($this->destinationSiteUrl, '/'); $this->replace[] = addcslashes($this->destinationSiteUrl, '/'); $this->replace[] = urlencode($this->destinationSiteUrl); $this->replace[] = urlencode($this->destinationSiteUrl); if (strpos($this->sourceSiteHostname, 'www.') === 0) { $sourceSiteWithoutWWW = substr($this->sourceSiteHostname, 4); $this->search[] = 'https://' . $sourceSiteWithoutWWW; $this->replace[] = $this->destinationSiteUrl; $this->search[] = 'http://' . $sourceSiteWithoutWWW; $this->replace[] = $this->destinationSiteUrl; } if ($this->isExtraCslashEscapingRequired()) { $this->search[] = addcslashes($sourceSiteHostnameJsonEscapedHttps, '/'); $this->search[] = addcslashes($sourceSiteHostnameJsonEscapedHttp, '/'); $this->replace[] = addcslashes($this->destinationSiteUrl, '/'); $this->replace[] = addcslashes($this->destinationSiteUrl, '/'); } $this->replaceMultipleHomeSchemes(); } protected function replaceMultipleHomeSchemes() { if (!$this->isCrossDomain()) { return; } if ($this->isIdenticalHomeHostname() && !$this->isUseCurrentSchemeOnSameSite()) { return; } $sourceHomeHostnameJsonEscapedHttps = addcslashes('https://' . $this->sourceHomeHostname, '/'); $sourceHomeHostnameJsonEscapedHttp = addcslashes('http://' . $this->sourceHomeHostname, '/'); $this->search[] = 'https://' . $this->sourceHomeHostname; $this->search[] = 'http://' . $this->sourceHomeHostname; $this->search[] = $sourceHomeHostnameJsonEscapedHttps; $this->search[] = $sourceHomeHostnameJsonEscapedHttp; $this->search[] = urlencode('https://' . $this->sourceHomeHostname); $this->search[] = urlencode('http://' . $this->sourceHomeHostname); $this->replace[] = $this->destinationHomeUrl; $this->replace[] = $this->destinationHomeUrl; $this->replace[] = addcslashes($this->destinationHomeUrl, '/'); $this->replace[] = addcslashes($this->destinationHomeUrl, '/'); $this->replace[] = urlencode($this->destinationHomeUrl); $this->replace[] = urlencode($this->destinationHomeUrl); if ($this->isExtraCslashEscapingRequired()) { $this->search[] = addcslashes($sourceHomeHostnameJsonEscapedHttps, '/'); $this->search[] = addcslashes($sourceHomeHostnameJsonEscapedHttp, '/'); $this->replace[] = addcslashes($this->destinationHomeUrl, '/'); $this->replace[] = addcslashes($this->destinationHomeUrl, '/'); } } protected function replaceMultipleSchemesUploadURL() { if ($this->isIdenticalUploadURL()) { return; } $sourceUploadURLWithHttpsScheme = 'https://' . $this->trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL; $destinationUploadURLWithScheme = $this->trailingslashit($this->destinationSiteUrl) . $this->destinationSiteUploadURL; $this->replaceURLs($sourceUploadURLWithHttpsScheme, $destinationUploadURLWithScheme, $doubleSlashPrefix = false); $sourceUploadURLWithHttpScheme = 'http://' . $this->trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL; $this->replaceURLs($sourceUploadURLWithHttpScheme, $destinationUploadURLWithScheme, $doubleSlashPrefix = false); } protected function isExtraCslashEscapingRequired(): bool { if ($this->requireCslashEscaping !== null) { return $this->requireCslashEscaping; } $requireCslashEscaping = false; foreach ($this->plugins as $plugin) { if (in_array($plugin, $this->getPluginsWhichRequireCslashEscaping())) { $requireCslashEscaping = true; break; } } $this->requireCslashEscaping = $this->applyFilters('wpstg.backup.restore.extended-cslash-search-replace', $requireCslashEscaping) === true; return $this->requireCslashEscaping; } protected function getPluginsWhichRequireCslashEscaping(): array { return [ 'revslider/revslider.php', 'elementor/elementor.php', 'breakdance/plugin.php' ]; } protected function isCrossDomain(): bool { return $this->sourceSiteHostname !== $this->sourceHomeHostname; } protected function isIdenticalSiteHostname(): bool { return $this->sourceSiteHostname === $this->destinationSiteHostname; } protected function isIdenticalHomeHostname(): bool { return $this->sourceHomeHostname === $this->destinationHomeHostname; } protected function isIdenticalUploadURL(): bool { return $this->sourceSiteUploadURL === $this->destinationSiteUploadURL; } protected function isUseCurrentSchemeOnSameSite(): bool { return (bool)$this->applyFilters(self::FILTER_CURRENT_SCHEME_SAME_SITE, false); } protected function prepareUploadURLs() { if (empty($this->destinationSiteUploadURL)) { $this->destinationSiteUploadURL = $this->getUploadUrl(); } $this->destinationSiteUploadURL = $this->untrailingslashit($this->destinationSiteUploadURL); $this->sourceSiteUploadURL = str_replace($this->trailingslashit($this->sourceSiteUrl), '', $this->sourceSiteUploadURL); $this->destinationSiteUploadURL = str_replace($this->trailingslashit($this->destinationSiteUrl), '', $this->destinationSiteUploadURL); } abstract protected function normalizePath(string $path): string; abstract protected function getUploadUrl(): string; }
    final class FileObject extends \SplFileObject
    {
        const MODE_READ            = 'rb';        const MODE_WRITE           = 'wb';        const MODE_APPEND          = 'ab';        const MODE_APPEND_AND_READ = 'ab+';        const MODE_WRITE_SAFE      = 'xb';        const MODE_WRITE_UNSAFE    = 'cb';        protected $totalLines = null;
        protected $fgetsUsedOnKey0 = false;
        protected $fseekUsed = false;
        public function __construct(string $fullPath, string $openMode = self::MODE_READ)
        {
            try {
                parent::__construct($fullPath, $openMode);
            } catch (\Throwable $e) {
                throw $e;
            }
        }
        public function totalLines(bool $useParent = false): int
        {
            if ($this->totalLines !== null) {
                return $this->totalLines;
            }
            if ($useParent) {
                $currentKey = $this->keyUseParent();
                $this->seekUseParent(PHP_INT_MAX);
                $this->totalLines = $this->keyUseParent();
                if ($currentKey < 0) {
                    $currentKey = 0;
                }
                $this->seekUseParent($currentKey);
            } else {
                $currentKey = $this->key();
                if ($currentKey < 0) {
                    $currentKey = 0;
                }
                $this->seek(PHP_INT_MAX);
                $this->totalLines = $this->key();
                $this->seek($currentKey);
            }
            if ($this->totalLines > 0) {
                if (PHP_VERSION === '8.2.0RC3' || version_compare(PHP_VERSION, '8.2.0', '>=')) {
                    $this->totalLines += 1;
                }
                if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.11', '<=')) {
                    $this->totalLines += 1;
                }
            }
            return $this->totalLines;
        }
        #[\ReturnTypeWillChange]
        public function seek($offset)
        {
            if ($offset < 0) {
                throw new \Exception("Can't seek file: " . $this->getPathname() . " to negative offset: $offset");
            }
            $this->fseekUsed       = false;
            $this->fgetsUsedOnKey0 = false;
            if ($offset === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
                parent::seek($offset);
                return;
            }
            $offset -= 1;
            if ($this->totalLines !== null && $offset >= $this->totalLines) {
                $offset += 1;
            }
            $originalFlags = $this->getFlags();
            $newFlags      = $originalFlags & ~self::READ_AHEAD;
            $this->setFlags($newFlags);
            parent::seek($offset);
            if ($this->eof()) {
                $this->current();
                $this->totalLines = $this->key();
                return;
            }
            $this->current();
            $this->next();
            $this->current();
            $this->setFlags($originalFlags);
        }
        public function fgets(): string
        {
            if ($this->key() === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
                $this->fgetsUsedOnKey0 = true;
                return parent::fgets();
            }
            $originalFlags = $this->getFlags();
            $newFlags      = $originalFlags & ~self::READ_AHEAD;
            $this->setFlags($newFlags);
            $line = $this->current();
            $this->next();
            if (version_compare(PHP_VERSION, '8.0.19', '<')) {
                $line = $this->current();
            }
            if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
                $line = $this->current();
            }
            if (!$this->fseekUsed) {
                $line = $this->current();
            }
            $this->setFlags($originalFlags);
            return $line;
        }
        #[\ReturnTypeWillChange]
        public function key(): int
        {
            if (!$this->fgetsUsedOnKey0 || version_compare(PHP_VERSION, '8.0.19', '<')) {
                return parent::key();
            }
            if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
                return parent::key();
            }
            return parent::key() - 1;
        }
        #[\ReturnTypeWillChange]
        public function fseek($offset, $whence = SEEK_SET): int
        {
            if (version_compare(PHP_VERSION, '8.0.19', '<')) {
                return parent::fseek($offset, $whence);
            }
            if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
                return parent::fseek($offset, $whence);
            }
            for ($i = 0; $i < 3; $i++) {
                parent::fseek(0);
                $this->fgets();
            }
            $this->fseekUsed = true;
            return parent::fseek((int)$offset, $whence);
        }
        public function readAndMoveNext(bool $useFgets = false): string
        {
            if ($useFgets && version_compare(PHP_VERSION, '8.0.1', '<')) {
                return parent::fgets();
            }
            $originalFlags = $this->getFlags();
            $newFlags      = $originalFlags & ~self::READ_AHEAD;
            $this->setFlags($newFlags);
            $line = $this->current();
            $this->next();
            $this->setFlags($originalFlags);
            return $line;
        }
        public function isSqlFile(): bool
        {
            return $this->getExtension() === 'sql';
        }
        public function fgetsUseParent(): string
        {
            return parent::fgets();
        }
        public function keyUseParent(): int
        {
            return parent::key();
        }
        public function seekUseParent(int $offset)
        {
            parent::seek($offset);
        }
        #[\ReturnTypeWillChange]
        public function flock($operation, &$wouldBlock = null): bool
        {
            if ($this->isWindowsOs()) {
                return true;
            }
            $parentMethodFlock = 'parent::flock';
            if (version_compare(PHP_VERSION, '8.2', '>=')) {
                $parentMethodFlock = \SplFileObject::class . '::flock';
            }
            if (!is_callable($parentMethodFlock)) {
                return false;
            }
            return parent::flock($operation, $wouldBlock);
        }
        protected function isWindowsOs(): bool
        {
            if (function_exists('wpstgIsWindowsOs')) {
                return wpstgIsWindowsOs();
            }
            return false;
        }
    }
    final class PartIdentifier { const DATABASE_PART_IDENTIFIER = 'wpstgdb'; const MU_PLUGIN_PART_IDENTIFIER = 'muplugins'; const PLUGIN_PART_IDENTIFIER = 'plugins'; const THEME_PART_IDENTIFIER = 'themes'; const UPLOAD_PART_IDENTIFIER = 'uploads'; const LANGUAGE_PART_IDENTIFIER = 'lang'; const DROPIN_PART_IDENTIFIER = 'dropins'; const OTHER_WP_CONTENT_PART_IDENTIFIER = 'otherfiles'; const WP_CONTENT_PART_IDENTIFIER = 'wpcontent'; const OTHER_WP_ROOT_PART_IDENTIFIER = 'rootfiles'; const WP_ROOT_PART_IDENTIFIER = 'wproot'; const DATABASE_PART_SIZE_IDENTIFIER = 'sqlSize'; const MU_PLUGIN_PART_SIZE_IDENTIFIER = 'mupluginsSize'; const PLUGIN_PART_SIZE_IDENTIFIER = 'pluginsSize'; const THEME_PART_SIZE_IDENTIFIER = 'themesSize'; const UPLOAD_PART_SIZE_IDENTIFIER = 'uploadsSize'; const LANGUAGE_PART_SIZE_IDENTIFIER = 'langSize'; const DROPIN_PART_SIZE_IDENTIFIER = 'dropinsSize'; const WP_CONTENT_PART_SIZE_IDENTIFIER = 'wpcontentSize'; const WP_ROOT_PART_SIZE_IDENTIFIER = 'wpRootSize'; const DROP_IN_FILES = [ 'object-cache.php', 'advanced-cache.php', 'db.php', 'db-error.php', 'install.php', 'maintenance.php', 'php-error.php', 'fatal-error-handler.php' ]; }
    final class PathIdentifier { const IDENTIFIER_ABSPATH = 'wpstg_a_'; const IDENTIFIER_WP_CONTENT = 'wpstg_c_'; const IDENTIFIER_PLUGINS = 'wpstg_p_'; const IDENTIFIER_THEMES = 'wpstg_t_'; const IDENTIFIER_MUPLUGINS = 'wpstg_m_'; const IDENTIFIER_UPLOADS = 'wpstg_u_'; const IDENTIFIER_LANG = 'wpstg_l_'; protected $lastIdentifier; protected $directory; public function __construct(DirectoryInterface $directory) { $this->directory = $directory; } public function getBackupDirectory() { return $this->directory->getBackupDirectory(); } public function transformPathToIdentifiable($path) { if (isset($this->lastIdentifier) && $this->lastIdentifier !== self::IDENTIFIER_WP_CONTENT) { $basePath = $this->getIdentifierPath($this->lastIdentifier); if (strpos($path, $basePath) === 0) { return $this->lastIdentifier . substr($path, strlen($basePath)); } } if (strpos($path, $this->directory->getUploadsDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_UPLOADS; return $this->lastIdentifier . substr($path, strlen($this->directory->getUploadsDirectory())); } if ($this->directory->getPluginUploadsDirectory() !== $this->directory->getUploadsDirectory()) { if (strpos($path, $this->directory->getPluginUploadsDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_UPLOADS; return $this->lastIdentifier . substr($path, strlen($this->directory->getPluginUploadsDirectory())); } } if (strpos($path, $this->directory->getPluginsDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_PLUGINS; return $this->lastIdentifier . substr($path, strlen($this->directory->getPluginsDirectory())); } foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) { if (strpos($path, $themesDirectory) === 0) { $this->lastIdentifier = self::IDENTIFIER_THEMES; return $this->lastIdentifier . substr($path, strlen($themesDirectory)); } } if (strpos($path, $this->directory->getMuPluginsDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_MUPLUGINS; return $this->lastIdentifier . substr($path, strlen($this->directory->getMuPluginsDirectory())); } if (strpos($path, $this->directory->getLangsDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_LANG; return $this->lastIdentifier . substr($path, strlen($this->directory->getLangsDirectory())); } if (strpos($path, $this->directory->getWpContentDirectory()) === 0) { $this->lastIdentifier = self::IDENTIFIER_WP_CONTENT; return $this->lastIdentifier . substr($path, strlen($this->directory->getWpContentDirectory())); } if (strpos($path, $this->directory->getAbspath()) === 0) { $this->lastIdentifier = self::IDENTIFIER_ABSPATH; return $this->lastIdentifier . substr($path, strlen($this->directory->getAbspath())); } throw new \RuntimeException("Unknown entity type for path: $path"); } public function transformIdentifiableToPath($path) { $identifier = $this->getIdentifierFromPath($path); $pathWithoutIdentifier = $this->getPathWithoutIdentifier($path); return $this->getIdentifierPath($identifier) . $pathWithoutIdentifier; } public function getPathWithoutIdentifier($path) { return substr($path, 8); } public function getIdentifierFromPath($path) { return substr($path, 0, 8); } public function transformIdentifiableToRelativePath(string $string): string { $key = substr($string, 0, 8); $path = $this->getRelativePath($key); if (!empty($path) && is_string($path)) { return substr_replace($string, $path, 0, 8); } return $string; } public function getRelativePath(string $identifier): string { static $cache = []; if (!empty($cache) && !empty($identifier) && isset($cache[$identifier])) { return $cache[$identifier]; } $path = [ self::IDENTIFIER_ABSPATH => '', self::IDENTIFIER_WP_CONTENT => 'wp-content/', self::IDENTIFIER_PLUGINS => 'wp-content/plugins/', self::IDENTIFIER_THEMES => 'wp-content/themes/', self::IDENTIFIER_MUPLUGINS => 'wp-content/mu-plugins/', self::IDENTIFIER_UPLOADS => 'wp-content/uploads/', self::IDENTIFIER_LANG => 'wp-content/languages/', ]; if (!empty($identifier) && isset($path[$identifier])) { $cache[$identifier] = $path[$identifier]; return $cache[$identifier]; } trigger_error(sprintf('[%s] Could not find a path for the placeholder: %s', __METHOD__, filter_var($identifier, FILTER_SANITIZE_SPECIAL_CHARS))); return $identifier; } public function getAbsolutePath(string $identifier): string { return $this->getIdentifierPath($identifier); } public function getIdentifierByPartName(string $key): string { static $cache = []; if (!empty($cache) && !empty($key) && !empty($cache[$key])) { return $cache[$key]; } $list = [ PartIdentifier::WP_CONTENT_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_WP_CONTENT, PartIdentifier::PLUGIN_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_PLUGINS, PartIdentifier::THEME_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_THEMES, PartIdentifier::MU_PLUGIN_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_MUPLUGINS, PartIdentifier::UPLOAD_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_UPLOADS, PartIdentifier::LANGUAGE_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_LANG, PartIdentifier::DATABASE_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_UPLOADS, PartIdentifier::WP_ROOT_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_ABSPATH, ]; if (!empty($key) && !empty($list[$key])) { $cache[$key] = $list[$key]; return $cache[$key]; } return ''; } protected function getIdentifierPath($identifier) { switch ($identifier) { case self::IDENTIFIER_ABSPATH: return $this->directory->getAbspath(); case self::IDENTIFIER_UPLOADS: return $this->directory->getUploadsDirectory(); case self::IDENTIFIER_PLUGINS: return $this->directory->getPluginsDirectory(); case self::IDENTIFIER_THEMES: return $this->directory->getActiveThemeParentDirectory(); case self::IDENTIFIER_MUPLUGINS: return $this->directory->getMuPluginsDirectory(); case self::IDENTIFIER_LANG: return $this->directory->getLangsDirectory(); case self::IDENTIFIER_WP_CONTENT: return $this->directory->getWpContentDirectory(); default: throw new \UnexpectedValueException(sprintf("[%s] Could not find a path for the placeholder: %s", __METHOD__, filter_var($identifier, FILTER_SANITIZE_SPECIAL_CHARS))); } } public function hasDropinsFile(string $identifiablePath): bool { if (!(strpos($identifiablePath, self::IDENTIFIER_WP_CONTENT) === 0)) { return false; } $dropinsFile = implode('|', PartIdentifier::DROP_IN_FILES); return preg_match('@^' . self::IDENTIFIER_WP_CONTENT . '(' . $dropinsFile . ')@', $identifiablePath) ? true : false; } }
    final class Permissions { use ApplyFiltersTrait; const FILTER_FOLDER_PERMISSION = 'wpstg_folder_permission'; const DEFAULT_FILE_PERMISSION = 0644; const DEFAULT_DIR_PERMISSION = 0755; public function getDirectoryOctal(): int { if (!defined('FS_CHMOD_DIR')) { return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, self::DEFAULT_DIR_PERMISSION); } if ($this->isValidPermission(FS_CHMOD_DIR)) { return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, FS_CHMOD_DIR); } return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, self::DEFAULT_DIR_PERMISSION); } public function getFilesOctal(): int { if (!defined('FS_CHMOD_FILE')) { return self::DEFAULT_FILE_PERMISSION; } if ($this->isValidPermission(FS_CHMOD_FILE)) { return FS_CHMOD_FILE; } return self::DEFAULT_FILE_PERMISSION; } private function isValidPermission(int $permission): bool { if (!preg_match('/^[0-7]+$/', ((string)$permission))) { return false; } if (decoct(octdec((string)$permission)) !== (string)$permission) { return false; } return $permission >= 0 && $permission <= 0777; } }
    final class DateTimeAdapter { const DEFAULT_TIME_FORMAT = 'H:i:s'; private $dateFormat; private $timeFormat; private $genericDateFormats = [ 'F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y', 'd-m-Y', 'm-d-Y', 'Y-m-d', 'Y/m/d', ]; public function __construct() { $this->dateFormat = get_option('date_format'); $this->timeFormat = get_option('time_format'); } public function getWPDateTimeFormat() { return $this->dateFormat . ' ' . $this->timeFormat; } public function getDateTimeFormat() { $dateFormat = $this->dateFormat; $timeFormat = self::DEFAULT_TIME_FORMAT; if (!$dateFormat) { $dateFormat = 'Y/m/d'; } $dateFormat = str_replace('F', 'M', $dateFormat); return $dateFormat . ' ' . $timeFormat; } public function transformToWpFormat(\DateTime $dateTime) { return get_date_from_gmt($dateTime->format('Y-m-d H:i:s'), $this->getDateTimeFormat()); } public function getDateTime($value) { $date = null; foreach ($this->generateDefaultDateFormats() as $format) { $date = \DateTime::createFromFormat($format, $value); if ($date) { break; } } return $date ?: null; } private function generateDefaultDateFormats() { $formats = [ 'U', $this->getDateTimeFormat(), $this->getWPDateTimeFormat(), ]; foreach ($this->genericDateFormats as $format) { $formats[] = $format . ' ' . self::DEFAULT_TIME_FORMAT; } return $formats; } }
    final class DataEncoder { const PACK_MODE_64BIT = 'P'; const PACK_MODE_32BIT = 'V'; protected $packMode; public function __construct() { $this->packMode = PHP_INT_SIZE === 8 ? self::PACK_MODE_64BIT : self::PACK_MODE_32BIT; } public function intArrayToHex(string $format, array $intArray): string { if (empty($format)) { throw new \InvalidArgumentException('Format cannot be empty'); } if (empty($intArray)) { throw new \InvalidArgumentException('Int array cannot be empty'); } $formats = str_split($format); if (count($formats) !== count($intArray)) { throw new \InvalidArgumentException('The number of characters in formats and integers in array must be equal'); } if (preg_match('/[^1-8]/', $format)) { throw new \InvalidArgumentException('Invalid format'); } $index = 0; $result = ''; foreach ($formats as $format) { try { $bytes = intval($format); if (!is_int($bytes)) { throw new \InvalidArgumentException('Invalid format'); } $result .= $this->intToHex($intArray[$index], $bytes); } catch (\InvalidArgumentException $ex) { throw new \InvalidArgumentException($ex->getMessage() . ' at index ' . $index); } catch (\Exception $ex) { throw new \InvalidArgumentException($ex->getMessage() . ' at index ' . $index); } $index++; } return $result; } public function intToHex(int $value, int $bytes = 8): string { if ($value < 0 && PHP_INT_SIZE === 8) { throw new \InvalidArgumentException('Invalid value'); } if ($bytes < 1 || $bytes > 8) { throw new \InvalidArgumentException('Invalid number of bytes'); } $maxInt = (2 ** ($bytes * 8)) - 1; if ($value > $maxInt) { throw new \InvalidArgumentException('Pack: Value is too large for the given number of bytes'); } $pack = pack($this->packMode, $value); if ($bytes <= PHP_INT_SIZE) { return bin2hex(substr($pack, 0, $bytes)); } $hex = bin2hex($pack); return $hex . str_repeat("00", $bytes - PHP_INT_SIZE); } public function hexToIntArray(string $format, string $hex): array { if (empty($format)) { throw new \InvalidArgumentException('Format cannot be empty'); } if (preg_match('/[^1-8]/', $format)) { throw new \InvalidArgumentException('Invalid format: ' . $format); } if (empty($hex)) { throw new \InvalidArgumentException('Hex string cannot be empty'); } if (strlen($hex) % 2 !== 0) { throw new \InvalidArgumentException('Invalid hex string: ' . $hex); } if (preg_match('/[^0-9a-fA-F]/', $hex)) { throw new \InvalidArgumentException('Invalid hex string: ' . $hex); } $formats = str_split($format); $index = 0; $intArray = []; foreach ($formats as $format) { $bytes = intval($format); $length = $bytes * 2; if ($index + $length > strlen($hex)) { throw new \InvalidArgumentException('Hex string is short according to format'); } $subHex = substr($hex, $index, $length); $intArray[] = $this->hexToInt($subHex, $bytes); $index += $length; } if ($index !== strlen($hex)) { throw new \InvalidArgumentException('Hex string is long according to format'); } return $intArray; } public function hexToInt(string $hex, int $bytes = 8): int { if ($bytes < 1 || $bytes > 8) { throw new \InvalidArgumentException('Invalid number of bytes'); } if (empty($hex)) { throw new \InvalidArgumentException('Hex string cannot be empty'); } if (strlen($hex) / 2 > $bytes) { throw new \InvalidArgumentException('Hex string is longer than the given number of bytes'); } if (strlen($hex) % 2 !== 0) { throw new \InvalidArgumentException('Invalid hex string: ' . $hex); } if (preg_match('/[^0-9a-fA-F]/', $hex)) { throw new \InvalidArgumentException('Invalid hex string: ' . $hex); } $binary = hex2bin($hex); if ($bytes < PHP_INT_SIZE) { $binary = str_pad($binary, PHP_INT_SIZE, "\x00", STR_PAD_RIGHT); } if ($bytes <= PHP_INT_SIZE) { return unpack($this->packMode, $binary)[1]; } $extraData = substr($binary, PHP_INT_SIZE); $extraZero = str_repeat("\x00", $bytes - PHP_INT_SIZE); if ($extraData !== $extraZero) { throw new \InvalidArgumentException('Unpack: Value is too large for the given number of bytes'); } $dataToUnpack = substr($binary, 0, PHP_INT_SIZE); return unpack($this->packMode, $dataToUnpack)[1]; } }
    final class Version { public function convertStringFormatToIntFormat(string $versionString): int { $versionParts = explode('.', $versionString); if (count($versionParts) !== 3) { throw new \InvalidArgumentException('Invalid version string format'); } foreach ($versionParts as $part) { if (!is_numeric($part)) { throw new \InvalidArgumentException('Version parts must be positive integers'); } } $versionParts = array_map('intval', $versionParts); if ($versionParts[0] < 0 || $versionParts[1] < 0 || $versionParts[2] < 0) { throw new \InvalidArgumentException('Version parts must be positive integers'); } if ($versionParts[0] === 0 && $versionParts[1] === 0 && $versionParts[2] === 0) { throw new \InvalidArgumentException('Invalid version string format'); } if ($versionParts[1] > 100 || $versionParts[2] > 100) { throw new \InvalidArgumentException('Version Minor and Patch parts must be less than 100'); } return $versionParts[0] * 10000 + $versionParts[1] * 100 + $versionParts[2]; } public function convertIntFormatToStringFormat(int $version): string { if ($version < 1) { throw new \InvalidArgumentException('Version must be a positive integer'); } $major = floor($version / 10000); $minor = floor(($version % 10000) / 100); $patch = $version % 100; return sprintf('%d.%d.%d', $major, $minor, $patch); } }
    final class FileValidationException extends \Exception { }
    final class BackupHeader { const WPSTG_SQL_BACKUP_DUMP_HEADER = "-- WP Staging SQL Backup Dump\n"; const HEADER_SIZE = 512; const HEADER_IN_USE_HEX_FORMAT = '48888'; const MAGIC = "wpstg"; const MAGIC_SIZE = 8; const MIN_BACKUP_VERSION = '2.0.0'; const BACKUP_VERSION = '2.0.0'; const COPYRIGHT_TEXT = '57502053746167696e672066696c6520666f726d61742062792052656e65204865726d656e617520262048617373616e20536861666971756520323032342f30'; const COPYRIGHT_TEXT_SIZE = 128; private $magic; private $backupVersion; private $filesIndexStartOffset = 0; private $filesIndexEndOffset = 0; private $metadataStartOffset = 0; private $metadataEndOffset = 0; private $copyrightText; private $encoder; private $versionUtil; public function __construct(DataEncoder $encoder, Version $versionUtil) { $this->encoder = $encoder; $this->versionUtil = $versionUtil; $this->backupVersion = $this->versionUtil->convertStringFormatToIntFormat(self::BACKUP_VERSION); } public function getBackupVersion(): int { return $this->backupVersion; } public function getFormattedBackupVersion(): string { return $this->versionUtil->convertIntFormatToStringFormat($this->backupVersion); } public function getMetadataStartOffset(): int { return $this->metadataStartOffset; } public function setMetadataStartOffset(int $metadataStartOffset): BackupHeader { $this->metadataStartOffset = $metadataStartOffset; return $this; } public function getMetadataEndOffset(): int { return $this->metadataEndOffset; } public function setMetadataEndOffset(int $metadataEndOffset): BackupHeader { $this->metadataEndOffset = $metadataEndOffset; return $this; } public function getFilesIndexStartOffset(): int { return $this->filesIndexStartOffset; } public function setFilesIndexStartOffset(int $filesIndexStartOffset): BackupHeader { $this->filesIndexStartOffset = $filesIndexStartOffset; return $this; } public function getFilesIndexEndOffset(): int { return $this->filesIndexEndOffset; } public function setFilesIndexEndOffset(int $filesIndexEndOffset): BackupHeader { $this->filesIndexEndOffset = $filesIndexEndOffset; return $this; } public function readFromPath(string $backupFilePath): BackupHeader { if (!file_exists($backupFilePath)) { throw new \RuntimeException('Backup file not found'); } $file = new FileObject($backupFilePath, FileObject::MODE_READ); return $this->readFromFileObject($file); } public function readFromFileObject(FileObject $file): BackupHeader { if ($file->getSize() < self::HEADER_SIZE) { throw new \RuntimeException('Invalid v2 format backup file'); } $file->seek(0); $rawHeader = $file->fread(self::HEADER_SIZE); return $this->setupBackupHeaderFromRaw($rawHeader); } public function setupBackupHeaderFromRaw(string $rawHeader): BackupHeader { $this->magic = rtrim(substr($rawHeader, 0, self::MAGIC_SIZE)); $this->copyrightText = substr($rawHeader, self::HEADER_SIZE - self::COPYRIGHT_TEXT_SIZE, self::COPYRIGHT_TEXT_SIZE); $dynamicHeader = substr($rawHeader, self::MAGIC_SIZE, $this->getHeaderInUseSize()); $headerIntData = $this->encoder->hexToIntArray(self::HEADER_IN_USE_HEX_FORMAT, $dynamicHeader); $this->backupVersion = $headerIntData[0]; $this->filesIndexStartOffset = $headerIntData[1]; $this->filesIndexEndOffset = $headerIntData[2]; $this->metadataStartOffset = $headerIntData[3]; $this->metadataEndOffset = $headerIntData[4]; return $this; } public function isValidBackupHeader(): bool { if ($this->magic !== self::MAGIC) { return false; } if ($this->copyrightText !== self::COPYRIGHT_TEXT) { return false; } return version_compare($this->getFormattedBackupVersion(), self::MIN_BACKUP_VERSION, '>='); } public function getHeader(): string { return sprintf( '%s%s%s%s', str_pad(self::MAGIC, self::MAGIC_SIZE, "\0", STR_PAD_RIGHT), $this->encoder->intArrayToHex( self::HEADER_IN_USE_HEX_FORMAT, [ $this->backupVersion, $this->filesIndexStartOffset, $this->filesIndexEndOffset, $this->metadataStartOffset, $this->metadataEndOffset ] ), bin2hex(str_pad("", $this->getUnusedBytesSize(), "\0", STR_PAD_RIGHT)), self::COPYRIGHT_TEXT ); } public function updateHeader(string $backupFilePath) { $header = $this->getHeader(); $file = new FileObject($backupFilePath, 'r+'); $file->seek(0); $file->fwrite($header); $file = null; } public function verifyV1FormatHeader(string $content): bool { if (empty($content)) { return false; } $wpstgBackupHeaderFileContent = self::WPSTG_SQL_BACKUP_DUMP_HEADER; $headerToVerifyLength = strlen($wpstgBackupHeaderFileContent); if (substr($wpstgBackupHeaderFileContent, 0, $headerToVerifyLength) === substr($content, 0, $headerToVerifyLength)) { return true; } $wpstgBackupHeaderFile = WPSTG_RESOURCES_DIR . 'wpstgBackupHeader.txt'; if (!file_exists($wpstgBackupHeaderFile)) { return true; } $wpstgBackupHeaderFileContent = file_get_contents($wpstgBackupHeaderFile); $headerToVerifyLength = self::HEADER_SIZE; if (!empty($wpstgBackupHeaderFileContent) && substr($wpstgBackupHeaderFileContent, 0, $headerToVerifyLength) === substr($content, 0, $headerToVerifyLength)) { return true; } return false; } public function getV1FormatHeader(): string { $wpstgBackupHeaderFile = WPSTG_RESOURCES_DIR . 'wpstgBackupHeader.txt'; if (!file_exists($wpstgBackupHeaderFile)) { return ""; } return file_get_contents($wpstgBackupHeaderFile); } private function getHeaderInUseSize(): int { $size = 0; for ($i = 0; $i < strlen(self::HEADER_IN_USE_HEX_FORMAT); $i++) { $size += intval(substr(self::HEADER_IN_USE_HEX_FORMAT, $i, 1)); } return $size * 2; } private function getUnusedBytesSize(): int { return (self::HEADER_SIZE - $this->getHeaderInUseSize() - self::MAGIC_SIZE - self::COPYRIGHT_TEXT_SIZE) / 2; } }
    final class BackupMetadataReader { private $existingMetadataPosition; private $fileObject; public function __construct(FileObject $fileObject) { $this->fileObject = $fileObject; } public function readBackupMetadata(): array { $maxBackupMetadataSize = $this->getExpectedMaxBackupMetadataSize(); $negativeOffset = min($maxBackupMetadataSize, 1 * MB_IN_BYTES); $negativeOffset = max($negativeOffset, 32 * KB_IN_BYTES); $this->fileObject->fseek(max($this->fileObject->getSize() - $negativeOffset, 0), SEEK_SET); $backupMetadata = null; do { $this->existingMetadataPosition = $this->fileObject->ftell(); $line = trim($this->fileObject->readAndMoveNext()); if ($this->isValidMetadata($line)) { $backupMetadata = $this->extractMetadata($line); } } while ($this->fileObject->valid() && !is_array($backupMetadata)); if (!is_array($backupMetadata)) { $error = sprintf('Could not find metadata in the backup file %s - This file could be corrupt.', $this->fileObject->getFilename()); throw new \RuntimeException($error); } return $backupMetadata; } public function extractMetadata(string $line): array { $json = []; if (!$this->fileObject->isSqlFile()) { $json = json_decode($line, true); } else { $json = json_decode(substr($line, 3), true); } return empty($json) ? [] : $json; } public function isValidMetadata(string $line): bool { if ($this->fileObject->isSqlFile() && substr($line, 3, 1) !== '{') { return false; } elseif (!$this->fileObject->isSqlFile() && substr($line, 0, 1) !== '{') { return false; } $maybeMetadata = $this->extractMetadata($line); if (!is_array($maybeMetadata) || !array_key_exists('networks', $maybeMetadata) || !is_array($maybeMetadata['networks'])) { return false; } $network = $maybeMetadata['networks']['1']; if (!is_array($network) || !array_key_exists('blogs', $network) || !is_array($network['blogs'])) { return false; } return true; } public function getExistingMetadataPosition(): int { if ($this->existingMetadataPosition === null) { $this->readBackupMetadata(); } return $this->existingMetadataPosition; } private function getExpectedMaxBackupMetadataSize(): int { $maxBackupMetadataSize = 128 * KB_IN_BYTES; if (!function_exists('apply_filters')) { return $maxBackupMetadataSize; } return apply_filters('wpstg_max_backup_metadata_size', $maxBackupMetadataSize); } }
    final class MultipartMetadata implements \JsonSerializable
    {
        use HydrateTrait {
            hydrate as traitHydrate;
        }
        private $totalFiles;
        private $partSize = '';
        private $pluginsParts = [];
        private $mupluginsParts = [];
        private $themesParts = [];
        private $uploadsParts = [];
        private $othersParts = [];
        private $otherWpRootParts = [];
        private $databaseParts = [];
        private $databaseFiles = [];
        #[\ReturnTypeWillChange]
        public function jsonSerialize()
        {
            return $this->toArray();
        }
        public function toArray()
        {
            $array = get_object_vars($this);
            return $array;
        }
        public function hydrate(array $data = [])
        {
            $this->traitHydrate($data);
            return $this;
        }
        public function getTotalFiles()
        {
            return $this->totalFiles;
        }
        public function setTotalFiles($totalFiles)
        {
            $this->totalFiles = $totalFiles;
        }
        public function getPartSize()
        {
            return (int)$this->partSize;
        }
        public function setPartSize($partSize)
        {
            $this->partSize = (int)$partSize;
        }
        public function getPluginsParts()
        {
            return $this->pluginsParts;
        }
        public function setPluginsParts($parts)
        {
            $this->pluginsParts = $parts;
        }
        public function getMuPluginsParts()
        {
            return $this->mupluginsParts;
        }
        public function setMuPluginsParts($parts)
        {
            $this->mupluginsParts = $parts;
        }
        public function getThemesParts()
        {
            return $this->themesParts;
        }
        public function setThemesParts($parts)
        {
            $this->themesParts = $parts;
        }
        public function getUploadsParts()
        {
            return $this->uploadsParts;
        }
        public function setUploadsParts($parts)
        {
            $this->uploadsParts = $parts;
        }
        public function getOthersParts()
        {
            return $this->othersParts;
        }
        public function setOthersParts($parts)
        {
            $this->othersParts = $parts;
        }
        public function getOtherWpRootParts(): array
        {
            return $this->otherWpRootParts;
        }
        public function setOtherWpRootParts(array $parts)
        {
            $this->otherWpRootParts = $parts;
        }
        public function getDatabaseParts()
        {
            return $this->databaseParts;
        }
        public function setDatabaseParts($parts)
        {
            $this->databaseParts = $parts;
        }
        public function getDatabaseFiles()
        {
            return $this->databaseFiles;
        }
        public function setDatabaseFiles($files)
        {
            $this->databaseFiles = $files;
        }
        public function pushBackupPart($part, $fileInfo)
        {
            $partName            = $part . 'Parts';
            $this->{$partName}[] = $fileInfo;
        }
        public function addDatabaseFile($databaseFile)
        {
            $this->databaseFiles[] = $databaseFile;
        }
        public function getBackupParts()
        {
            return array_merge($this->databaseParts, $this->othersParts, $this->themesParts, $this->uploadsParts, $this->pluginsParts, $this->mupluginsParts, $this->otherWpRootParts);
        }
        public function getFileParts()
        {
            return array_merge($this->othersParts, $this->themesParts, $this->pluginsParts, $this->mupluginsParts, $this->uploadsParts, $this->otherWpRootParts);
        }
    }
    final class BackupMetadata implements \JsonSerializable
    {
        use HydrateTrait {
            hydrate as traitHydrate;
        }
        use IsExportingTrait;
        use DateCreatedTrait;
        use WithPluginsThemesMuPluginsTrait;
        const FILTER_BACKUP_FORMAT_V1 = 'wpstg.backup.format_v1';
        const BACKUP_TYPE_SINGLE = 'single';
        const BACKUP_TYPE_MULTISITE = 'multi';
        const BACKUP_TYPE_NETWORK_SUBSITE = 'network-subsite';
        const BACKUP_TYPE_MAIN_SITE = 'main-network-site';
        private $id;
        private $headerStart;
        private $headerEnd;
        private $backupVersion = '';
        private $wpstgVersion = '';
        private $totalFiles;
        private $totalDirectories;
        private $siteUrl;
        private $homeUrl;
        private $absPath;
        private $prefix;
        private $backupType = '';
        private $name;
        private $note;
        private $isAutomatedBackup = false;
        private $databaseFile;
        private $uploadedOn;
        private $maxTableLength;
        private $databaseFileSize;
        private $phpVersion;
        private $wpVersion;
        private $wpDbVersion;
        private $dbCollate;
        private $dbCharset;
        private $sqlServerVersion;
        private $backupSize = '';
        private $blogId;
        private $networkId;
        private $networkAdmins;
        private $uploadsPath;
        private $uploadsUrl;
        private $phpShortOpenTags;
        private $wpBakeryActive;
        private $isJetpackActive;
        private $isCreatedOnWordPressCom;
        private $scheduleId;
        private $sites;
        private $subdomainInstall;
        private $createdOnPro;
        private $nonWpTables;
        private $logFile = '';
        private $multipartMetadata = null;
        private $indexPartSize = [];
        private $isZlibCompressed = false;
        private $totalChunks = 0;
        private $hostingType;
        private $isContaining2GBFile = false;
        private $phpArchitecture;
        private $osArchitecture;
        #[\ReturnTypeWillChange]
        public function jsonSerialize(): array
        {
            return $this->toArray();
        }
        public function toArray(): array
        {
            $array = get_object_vars($this);
            return [
                'networks' => [
                    $this->getNetworkId() => [
                        'blogs' => [
                            $this->getBlogId() => $array,
                        ],
                    ],
                ],
            ];
        }
        public function hydrate(array $data = []): BackupMetadata
        {
            if (key($data) === 'networks') {
                if (array_key_exists($this->networkId, $data['networks'])) {
                    $data = $data['networks'][$this->networkId];
                } else {
                    $data = array_shift($data['networks']);
                }
            }
            if (key($data) === 'blogs') {
                if (array_key_exists($this->blogId, $data['blogs'])) {
                    $data = $data['blogs'][$this->blogId];
                } else {
                    $data = array_shift($data['blogs']);
                }
            }
            $this->traitHydrate($data);
            return $this;        }
        public function hydrateByFile(FileObject $file): BackupMetadata
        {
            $reader = new BackupMetadataReader($file);
            $backupMetadataArray = $reader->readBackupMetadata();
            return (new static())->hydrate($backupMetadataArray);        }
        public function hydrateByFilePath($filePath): BackupMetadata
        {
            return $this->hydrateByFile(new FileObject($filePath));
        }
        public function getId(): string
        {
            return $this->id;
        }
        public function setId(string $id)
        {
            $this->id = $id;
        }
        public function getHeader(string $backupPath)
        {
            if (!isset($this->headerStart)) {
                return '';
            }
            $backupFile = new FileObject($backupPath);
            $backupFile->fseek($this->headerStart);
            return $backupFile->fread($this->headerEnd - $this->headerStart);
        }
        public function getHeaderStart()
        {
            return $this->headerStart;
        }
        public function setHeaderStart($headerStart)
        {
            $this->headerStart = $headerStart;
        }
        public function getHeaderEnd()
        {
            return $this->headerEnd;
        }
        public function setHeaderEnd($headerEnd)
        {
            $this->headerEnd = $headerEnd;
        }
        public function getWpstgVersion(): string
        {
            return $this->wpstgVersion;
        }
        public function setWpstgVersion(string $wpstgVersion)
        {
            $this->wpstgVersion = $wpstgVersion;
        }
        public function setVersion(string $version)
        {
            $this->setWpstgVersion($version);
        }
        public function getBackupVersion(): string
        {
            return $this->backupVersion;
        }
        public function setBackupVersion(string $backupVersion)
        {
            $this->backupVersion = $backupVersion;
        }
        public function getTotalFiles()
        {
            return $this->totalFiles;
        }
        public function setTotalFiles($totalFiles)
        {
            $this->totalFiles = $totalFiles;
        }
        public function getTotalDirectories()
        {
            return $this->totalDirectories;
        }
        public function setTotalDirectories($totalDirectories)
        {
            $this->totalDirectories = $totalDirectories;
        }
        public function getSiteUrl(): string
        {
            return $this->siteUrl;
        }
        public function setSiteUrl(string $siteUrl)
        {
            $siteUrl = rtrim($siteUrl, '/');
            if (!preg_match('#http(s?)://(.+)#i', $siteUrl)) {
                throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
            }
            if (!parse_url($siteUrl, PHP_URL_HOST)) {
                throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
            }
            $this->siteUrl = $siteUrl;
        }
        public function getHomeUrl(): string
        {
            return $this->homeUrl;
        }
        public function setHomeUrl(string $homeUrl)
        {
            $homeUrl = rtrim($homeUrl, '/');
            if (!preg_match('#http(s?)://(.+)#i', $homeUrl)) {
                throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
            }
            if (!parse_url($homeUrl, PHP_URL_HOST)) {
                throw new \RuntimeException('Please check the Home URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
            }
            $this->homeUrl = $homeUrl;
        }
        public function getPrefix()
        {
            return $this->prefix;
        }
        public function setPrefix($prefix)
        {
            $this->prefix = $prefix;
        }
        public function setSingleOrMulti(string $singleOrMulti)
        {
            $this->setBackupType($singleOrMulti);
        }
        public function getBackupType(): string
        {
            return $this->backupType;
        }
        public function setBackupType(string $backupType)
        {
            $this->backupType = $backupType;
        }
        public function getName(): string
        {
            return $this->name;
        }
        public function setName(string $name)
        {
            $this->name = $name;
        }
        public function getNote()
        {
            return $this->note;
        }
        public function setNote($note)
        {
            $this->note = $note;
        }
        public function getIsAutomatedBackup(): bool
        {
            return $this->isAutomatedBackup;
        }
        public function setIsAutomatedBackup(bool $isAutomatedBackup)
        {
            $this->isAutomatedBackup = $isAutomatedBackup;
        }
        public function getDatabaseFile()
        {
            return $this->databaseFile;
        }
        public function setDatabaseFile($databaseFile)
        {
            $this->databaseFile = $databaseFile;
        }
        public function getUploadedOn(): int
        {
            return $this->uploadedOn;
        }
        public function setUploadedOn($uploadedOn)
        {
            $this->uploadedOn = $uploadedOn;
        }
        public function getMaxTableLength()
        {
            return $this->maxTableLength;
        }
        public function setMaxTableLength($maxTableLength)
        {
            $this->maxTableLength = $maxTableLength;
        }
        public function getDatabaseFileSize()
        {
            return $this->databaseFileSize;
        }
        public function setDatabaseFileSize($databaseFileSize)
        {
            $this->databaseFileSize = $databaseFileSize;
        }
        public function getPhpVersion(): string
        {
            return (string)$this->phpVersion;
        }
        public function setPhpVersion(string $phpVersion)
        {
            $this->phpVersion = (string)$phpVersion;
        }
        public function getWpVersion(): string
        {
            return (string)$this->wpVersion;
        }
        public function setWpVersion(string $wpVersion)
        {
            $this->wpVersion = (string)$wpVersion;
        }
        public function getWpDbVersion(): string
        {
            return (string)$this->wpDbVersion;
        }
        public function setWpDbVersion(string $wpDbVersion)
        {
            $this->wpDbVersion = (string)$wpDbVersion;
        }
        public function getDbCollate(): string
        {
            return (string)$this->dbCollate;
        }
        public function setDbCollate(string $dbCollate)
        {
            $this->dbCollate = (string)$dbCollate;
        }
        public function getSqlServerVersion(): string
        {
            return (string)$this->sqlServerVersion;
        }
        public function setSqlServerVersion(string $sqlServerVersion)
        {
            $this->sqlServerVersion = (string)$sqlServerVersion;
        }
        public function getDbCharset(): string
        {
            return (string)$this->dbCharset;
        }
        public function setDbCharset(string $dbCharset)
        {
            $this->dbCharset = (string)$dbCharset;
        }
        public function getBackupSize(): int
        {
            return (int)$this->backupSize;
        }
        public function setBackupSize($backupSize)
        {
            $this->backupSize = (int)$backupSize;
        }
        public function getAbsPath(): string
        {
            return $this->absPath;
        }
        public function setAbsPath(string $absPath)
        {
            $this->absPath = $absPath;
        }
        public function getBlogId(): int
        {
            return $this->blogId;
        }
        public function setBlogId(int $blogId)
        {
            $this->blogId = $blogId;
        }
        public function getUploadsPath(): string
        {
            return $this->uploadsPath;
        }
        public function setUploadsPath(string $uploadsPath)
        {
            $this->uploadsPath = $uploadsPath;
        }
        public function getUploadsUrl(): string
        {
            return $this->uploadsUrl;
        }
        public function setUploadsUrl(string $uploadsUrl)
        {
            $this->uploadsUrl = $uploadsUrl;
        }
        public function getNetworkId(): int
        {
            return $this->networkId;
        }
        public function setNetworkId(int $networkId)
        {
            $this->networkId = $networkId;
        }
        public function getNetworkAdmins(): array
        {
            if (!is_array($this->networkAdmins)) {
                $this->networkAdmins = [];
            }
            return $this->networkAdmins;
        }
        public function setNetworkAdmins($networkAdmins)
        {
            $this->networkAdmins = $networkAdmins;
        }
        public function getPhpShortOpenTags(): bool
        {
            return $this->phpShortOpenTags;
        }
        public function setPhpShortOpenTags(bool $phpShortOpenTags)
        {
            $this->phpShortOpenTags = $phpShortOpenTags;
        }
        public function getWpBakeryActive(): bool
        {
            return $this->wpBakeryActive;
        }
        public function setWpBakeryActive(bool $wpBakeryActive)
        {
            $this->wpBakeryActive = $wpBakeryActive;
        }
        public function getIsJetpackActive(): bool
        {
            return $this->isJetpackActive ?? false;
        }
        public function setIsJetpackActive($isJetpackActive)
        {
            $this->isJetpackActive = $isJetpackActive;
        }
        public function getIsCreatedOnWordPressCom(): bool
        {
            return $this->isCreatedOnWordPressCom ?? false;
        }
        public function setIsCreatedOnWordPressCom($isCreatedOnWordPressCom)
        {
            $this->isCreatedOnWordPressCom = $isCreatedOnWordPressCom;
        }
        public function getScheduleId()
        {
            return $this->scheduleId;
        }
        public function setScheduleId($scheduleId)
        {
            $this->scheduleId = $scheduleId;
        }
        public function getSites()
        {
            return $this->sites;
        }
        public function setSites($sites)
        {
            $this->sites = $sites;
        }
        public function getSubdomainInstall(): bool
        {
            return $this->subdomainInstall;
        }
        public function setSubdomainInstall(bool $subdomainInstall)
        {
            $this->subdomainInstall = $subdomainInstall;
        }
        public function getCreatedOnPro(): bool
        {
            if (!isset($this->createdOnPro) || is_null($this->createdOnPro)) {
                $this->createdOnPro = true;
            }
            return $this->createdOnPro;
        }
        public function setCreatedOnPro($createdOnPro)
        {
            $this->createdOnPro = $createdOnPro;
        }
        public function getMultipartMetadata()
        {
            if (empty($this->multipartMetadata)) {
                return null;
            }
            if ($this->multipartMetadata instanceof MultipartMetadata) {
                return $this->multipartMetadata;
            }
            $metadata                = new MultipartMetadata();
            $this->multipartMetadata = $metadata->hydrate($this->multipartMetadata);
            return $this->multipartMetadata;
        }
        public function setMultipartMetadata($multipartMetadata)
        {
            $this->multipartMetadata = $multipartMetadata;
        }
        public function getIsMultipartBackup(): bool
        {
            return !empty($this->multipartMetadata);
        }
        public function getNonWpTables()
        {
            return $this->nonWpTables;
        }
        public function setNonWpTables($tables)
        {
            $this->nonWpTables = $tables;
        }
        public function setLogFile(string $fileName)
        {
            $this->logFile = $fileName;
        }
        public function setIndexPartSize(array $indexPartSize)
        {
            $this->indexPartSize = $indexPartSize;
        }
        public function getIndexPartSize(): array
        {
            return $this->indexPartSize;
        }
        public function getIsZlibCompressed()
        {
            return $this->isZlibCompressed;
        }
        public function setIsZlibCompressed($isZlibCompressed)
        {
            $this->isZlibCompressed = $isZlibCompressed;
        }
        public function getTotalChunks(): int
        {
            return $this->totalChunks;
        }
        public function setTotalChunks(int $totalChunks)
        {
            $this->totalChunks = $totalChunks;
        }
        public function getHostingType(): string
        {
            if (empty($this->hostingType)) {
                $this->hostingType = 'other';
            }
            return $this->hostingType;
        }
        public function setHostingType(string $hostingType)
        {
            $this->hostingType = $hostingType;
        }
        public function getIsContaining2GBFile(): bool
        {
            return $this->isContaining2GBFile;
        }
        public function setIsContaining2GBFile($isContaining2GBFile)
        {
            $this->isContaining2GBFile = (bool)$isContaining2GBFile;
        }
        public function getPhpArchitecture(): string
        {
            return $this->phpArchitecture;
        }
        public function setPhpArchitecture(string $phpArchitecture)
        {
            $this->phpArchitecture = $phpArchitecture;
        }
        public function getOsArchitecture(): string
        {
            return $this->osArchitecture;
        }
        public function setOsArchitecture(string $osArchitecture)
        {
            $this->osArchitecture = $osArchitecture;
        }
        public function getIsBackupFormatV1(): bool
        {
            return version_compare($this->getBackupVersion(), BackupHeader::MIN_BACKUP_VERSION, '<');
        }
        public function getIsMultisiteBackup(): bool
        {
            return $this->backupType !== self::BACKUP_TYPE_SINGLE;
        }
    }
    final class FileBeingExtracted { private $identifiablePath; private $relativePath; private $start; private $totalBytes; private $writtenBytes = 0; protected $extractFolder; protected $pathIdentifier; protected $isCompressed; public function __construct(string $identifiablePath, string $extractFolder, PathIdentifier $pathIdentifier, IndexLineInterface $backupFileIndex) { $this->identifiablePath = $identifiablePath; $this->extractFolder = rtrim($extractFolder, '/') . '/'; $this->start = $backupFileIndex->getContentStartOffset(); $this->totalBytes = $backupFileIndex->getCompressedSize(); $this->pathIdentifier = $pathIdentifier; $this->isCompressed = (int)$backupFileIndex->getIsCompressed(); $this->relativePath = $this->pathIdentifier->getPathWithoutIdentifier($this->identifiablePath); } public function getBackupPath() { return $this->extractFolder . $this->relativePath; } public function findReadTo() { $maxLengthToWrite = 512 * KB_IN_BYTES; $remainingBytesToWrite = $this->totalBytes - $this->writtenBytes; return max(0, min($remainingBytesToWrite, $maxLengthToWrite)); } public function getPath() { return $this->identifiablePath; } public function getRelativePath() { return $this->relativePath; } public function getStart() { return $this->start; } public function getTotalBytes() { return $this->totalBytes; } public function getWrittenBytes() { return $this->writtenBytes; } public function setWrittenBytes($writtenBytes) { $this->writtenBytes = $writtenBytes; } public function addWrittenBytes($writtenBytes) { $this->writtenBytes += $writtenBytes; } public function isFinished() { return $this->writtenBytes >= $this->totalBytes; } public function getIsCompressed() { return $this->isCompressed; } public function getCurrentOffset(): int { return $this->start + $this->writtenBytes; } }
    final class ExtractorDto { protected $indexStartOffset; protected $currentIndexOffset; protected $totalFilesExtracted; protected $totalFilesSkipped; protected $totalChunks; protected $extractorFileWrittenBytes; public function __construct() { $this->indexStartOffset = 0; $this->currentIndexOffset = 0; $this->totalFilesExtracted = 0; $this->totalFilesSkipped = 0; $this->totalChunks = 0; $this->extractorFileWrittenBytes = 0; } public function getIndexStartOffset(): int { return $this->indexStartOffset; } public function setIndexStartOffset(int $indexStartOffset) { $this->indexStartOffset = $indexStartOffset; } public function getCurrentIndexOffset(): int { return $this->currentIndexOffset; } public function setCurrentIndexOffset(int $currentOffset) { $this->currentIndexOffset = $currentOffset; } public function getTotalFilesExtracted(): int { return $this->totalFilesExtracted; } public function setTotalFilesExtracted(int $filesExtracted) { $this->totalFilesExtracted = $filesExtracted; } public function getTotalFilesSkipped(): int { return $this->totalFilesSkipped; } public function setTotalFilesSkipped(int $filesSkipped) { $this->totalFilesSkipped = $filesSkipped; } public function getTotalChunks(): int { return $this->totalChunks; } public function setTotalChunks(int $totalChunks) { $this->totalChunks = $totalChunks; } public function getExtractorFileWrittenBytes(): int { return $this->extractorFileWrittenBytes; } public function setExtractorFileWrittenBytes(int $extractorFileWrittenBytes) { $this->extractorFileWrittenBytes = $extractorFileWrittenBytes; } public function incrementTotalFilesExtracted() { $this->totalFilesExtracted++; } public function incrementTotalFilesSkipped() { $this->totalFilesSkipped++; } }
    final class BackupItemDto { private $offset; private $index; private $identifiablePath; private $path; private $size; private $isDatabase; public function __construct() { $this->offset = 0; $this->index = 0; $this->path = ''; $this->size = ''; $this->isDatabase = false; } public static function fromIndexLineDto(IndexLineInterface $indexLineDto): BackupItemDto { $backupFile = new BackupItemDto(); $backupFile->setIdentifiablePath($indexLineDto->getIdentifiablePath()); $backupFile->setSize($indexLineDto->getUncompressedSize()); $backupFile->setIsDatabase(false); return $backupFile; } public function setOffset(int $offset) { $this->offset = $offset; } public function setIndex(int $index) { $this->index = $index; } public function setIdentifiablePath(string $identifiablePath) { $this->identifiablePath = $identifiablePath; } public function setPath(string $path) { $this->path = $path; } public function setSize(string $size) { $this->size = $size; } public function setIsDatabase(bool $isDatabase) { $this->isDatabase = $isDatabase; } public function getOffset(): int { return $this->offset; } public function getIndex(): int { return $this->index; } public function getIdentifiablePath(): string { return $this->identifiablePath; } public function getPath(): string { return $this->path; } public function getSize(): string { return $this->size; } public function isDatabase(): bool { return $this->isDatabase; } public function toArray(): array { return [ 0 => $this->index, 1 => $this->path, 2 => $this->offset, 3 => $this->size, 4 => $this->isDatabase, 'offset' => $this->offset, 'index' => $this->index, 'path' => $this->path, 'size' => $this->size, 'isDatabase' => $this->isDatabase, ]; } }
    final class BackupFileIndex implements IndexLineInterface { use FormatTrait; public $bytesStart; public $bytesEnd; public $identifiablePath; public $isCompressed; public function __construct() { $this->bytesStart = 0; $this->bytesEnd = 0; $this->identifiablePath = ''; $this->isCompressed = 0; } public function readIndex(string $index): BackupFileIndex { list($identifiablePath, $entryMetadata) = explode('|', trim($index)); $entryMetadata = explode(':', trim($entryMetadata)); if (count($entryMetadata) < 2) { throw new \UnexpectedValueException('Invalid backup file index.'); } $offsetStart = (int)$entryMetadata[0]; $writtenPreviously = (int)$entryMetadata[1]; if (count($entryMetadata) >= 3) { $isCompressed = (int)$entryMetadata[2]; } else { $isCompressed = 0; } $backupFileIndex = new BackupFileIndex(); $backupFileIndex->identifiablePath = str_replace(['{WPSTG_PIPE}', '{WPSTG_COLON}'], ['|', ':'], $identifiablePath); $backupFileIndex->bytesStart = $offsetStart; $backupFileIndex->bytesEnd = $writtenPreviously; $backupFileIndex->isCompressed = $isCompressed; return $backupFileIndex; } public function readIndexLine(string $indexLine): IndexLineInterface { return $this->readIndex($indexLine); } public function createIndex(string $identifiablePath, int $bytesStart, int $bytesEnd, int $isCompressed): BackupFileIndex { $backupFileIndex = new BackupFileIndex(); $backupFileIndex->identifiablePath = str_replace(['|', ':'], ['{WPSTG_PIPE}', '{WPSTG_COLON}'], $identifiablePath); $backupFileIndex->bytesStart = $bytesStart; $backupFileIndex->bytesEnd = $bytesEnd; $backupFileIndex->isCompressed = $isCompressed; return $backupFileIndex; } public function getIndex(): string { return "$this->identifiablePath|$this->bytesStart:$this->bytesEnd:$this->isCompressed"; } public function isIndexLine(string $item): bool { return !empty($item) && strpos($item, ':') !== false && strpos($item, '|') !== false; } public function getContentStartOffset(): int { return $this->bytesStart; } public function getStartOffset(): int { return $this->bytesStart; } public function getIdentifiablePath(): string { return $this->identifiablePath; } public function getUncompressedSize(): int { return $this->bytesEnd; } public function getCompressedSize(): int { return $this->bytesEnd; } public function getIsCompressed(): bool { return $this->isCompressed === 1; } public function validateFile(string $filePath, string $pathForErrorLogging = '') { if (empty($pathForErrorLogging)) { $pathForErrorLogging = $filePath; } if (!file_exists($filePath)) { throw new FileValidationException(sprintf('File doesn\'t exist: %s.', $pathForErrorLogging)); } if ($this->getIsCompressed()) { return; } $fileSize = filesize($filePath); if ($this->getUncompressedSize() !== $fileSize) { throw new FileValidationException(sprintf('Filesize validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->formatSize($this->getUncompressedSize(), 2), $this->formatSize($fileSize, 2))); } } }
    final class FileHeaderAttribute { const COMPRESSED = 0b0000000000000001; const REQUIRE_PREVIOUS_PART = 0b0000000000000010; const REQUIRE_NEXT_PART = 0b0000000000000100; }
    final class FileHeader implements IndexLineInterface { use EndOfLinePlaceholderTrait; use FormatTrait; const START_SIGNATURE = '47f6600b0200'; const FILE_HEADER_FIXED_SIZE = 72; const INDEX_HEADER_FIXED_SIZE = 72; const FILE_HEADER_FORMAT = '44552424'; const INDEX_HEADER_FORMAT = '644552424'; const CRC32_CHECKSUM_ALGO = 'crc32b'; private $startSignature; private $modifiedTime; private $crc32Checksum; private $crc32; private $compressedSize; private $uncompressedSize; private $attributes; private $extraFieldLength; private $fileNameLength; private $filePathLength; private $startOffset; private $filePath; private $fileName; private $extraField; private $encoder; private $pathIdentifier; public function __construct(DataEncoder $encoder, PathIdentifier $pathIdentifier) { $this->encoder = $encoder; $this->pathIdentifier = $pathIdentifier; $this->resetHeader(); } public function readFile(string $filePath, string $identifiablePath) { $fileInfo = new \SplFileInfo($filePath); $this->setFileName($fileInfo->getFilename()); $convertedPath = $this->pathIdentifier->transformIdentifiableToPath($identifiablePath); $convertedPathName = basename($convertedPath); $path = substr($identifiablePath, 0, -strlen($convertedPathName)); $this->setFilePath($path); $this->setExtraField(""); $this->setUncompressedSize($fileInfo->getSize()); $this->setCompressedSize($fileInfo->getSize()); $this->setModifiedTime($fileInfo->getMTime()); $this->setAttributes(0); $this->setCrc32Checksum(hash_file(self::CRC32_CHECKSUM_ALGO, $filePath)); } public function decodeFileHeader(string $index) { $index = rtrim($index); $fixedHeader = substr($index, 0, self::FILE_HEADER_FIXED_SIZE); $dynamicHeader = substr($index, self::FILE_HEADER_FIXED_SIZE); if (strpos($fixedHeader, self::START_SIGNATURE) !== 0) { throw new \UnexpectedValueException('Invalid file header'); } $header = $this->encoder->hexToIntArray(self::FILE_HEADER_FORMAT, substr($fixedHeader, 12, self::FILE_HEADER_FIXED_SIZE - 12)); $this->setModifiedTime($header[0]); $this->setCrc32($header[1]); $this->setCompressedSize($header[2]); $this->setUncompressedSize($header[3]); $this->setAttributes($header[4]); $this->filePathLength = $header[5]; $this->fileNameLength = $header[6]; $this->extraFieldLength = $header[7]; $this->setFilePath(substr($dynamicHeader, 0, $this->filePathLength)); $this->setFileName(substr($dynamicHeader, $this->filePathLength, $this->fileNameLength)); $this->setExtraField(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength)); } public function decodeIndexHeader(string $index) { $index = rtrim($index); $fixedHeader = substr($index, 0, self::INDEX_HEADER_FIXED_SIZE); $dynamicHeader = substr($index, self::INDEX_HEADER_FIXED_SIZE); $header = $this->encoder->hexToIntArray(self::INDEX_HEADER_FORMAT, $fixedHeader); $this->setStartOffset($header[0]); $this->setModifiedTime($header[1]); $this->setCrc32($header[2]); $this->setCompressedSize($header[3]); $this->setUncompressedSize($header[4]); $this->setAttributes($header[5]); $this->filePathLength = $header[6]; $this->fileNameLength = $header[7]; $this->extraFieldLength = $header[8]; $this->setFilePath(substr($dynamicHeader, 0, $this->filePathLength)); $this->setFileName(substr($dynamicHeader, $this->filePathLength, $this->fileNameLength)); $this->setExtraField(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength)); } public function readIndexLine(string $indexLine): IndexLineInterface { $this->decodeIndexHeader($indexLine); return $this; } public function isIndexLine(string $indexLine): bool { if (strlen($indexLine) <= self::INDEX_HEADER_FIXED_SIZE) { return false; } return true; } public function getFileHeader(): string { $fixedHeader = $this->encoder->intArrayToHex(self::FILE_HEADER_FORMAT, [ $this->modifiedTime, $this->crc32, $this->compressedSize, $this->uncompressedSize, $this->attributes, $this->filePathLength, $this->fileNameLength, $this->extraFieldLength ]); $fileHeader = self::START_SIGNATURE . $fixedHeader . $this->filePath . $this->fileName . $this->extraField; $fileHeader = $this->replaceEOLsWithPlaceholders($fileHeader); return $fileHeader; } public function getIndexHeader(): string { $fixedHeader = $this->encoder->intArrayToHex(self::INDEX_HEADER_FORMAT, [ $this->startOffset, $this->modifiedTime, $this->crc32, $this->compressedSize, $this->uncompressedSize, $this->attributes, $this->filePathLength, $this->fileNameLength, $this->extraFieldLength ]); $fixedHeader = $fixedHeader . $this->filePath . $this->fileName . $this->extraField; $fixedHeader = $this->replaceEOLsWithPlaceholders($fixedHeader); return $fixedHeader; } public function resetHeader() { $this->startSignature = ''; $this->modifiedTime = 0; $this->crc32 = 0; $this->crc32Checksum = ''; $this->compressedSize = 0; $this->uncompressedSize = 0; $this->setAttributes(0); $this->extraFieldLength = 0; $this->fileNameLength = 0; $this->filePathLength = 0; $this->startOffset = 0; $this->filePath = ''; $this->fileName = ''; $this->extraField = ''; } public function getStartSignature(): string { return $this->startSignature; } public function setStartSignature(string $startSignature) { $this->startSignature = $startSignature; } public function getModifiedTime(): int { return $this->modifiedTime; } public function setModifiedTime(int $modifiedTime) { $this->modifiedTime = $modifiedTime; } public function getCrc32(): int { return $this->crc32; } public function setCrc32(int $crc32) { $this->crc32 = $crc32; $this->crc32Checksum = bin2hex(pack('N', $crc32)); } public function getCrc32Checksum(): string { return $this->crc32Checksum; } public function setCrc32Checksum(string $crc32Checksum) { $this->crc32Checksum = $crc32Checksum; $this->crc32 = unpack('N', pack('H*', $this->crc32Checksum))[1]; } public function getCompressedSize(): int { return $this->compressedSize; } public function setCompressedSize(int $compressedSize) { $this->compressedSize = $compressedSize; } public function getUncompressedSize(): int { return $this->uncompressedSize; } public function setUncompressedSize(int $uncompressedSize) { $this->uncompressedSize = $uncompressedSize; } public function getAttributes(): int { return $this->attributes; } public function setAttributes(int $attributes) { $this->attributes = $attributes; } public function getIsCompressed(): bool { if ($this->attributes & FileHeaderAttribute::COMPRESSED) { return true; } return false; } public function setIsCompressed(bool $isCompressed) { $isCompressed ? $this->attributes |= FileHeaderAttribute::COMPRESSED : $this->attributes &= ~FileHeaderAttribute::COMPRESSED; } public function getIsPreviousPartRequired(): bool { if ($this->attributes & FileHeaderAttribute::REQUIRE_PREVIOUS_PART) { return true; } return false; } public function setIsPreviousPartRequired(bool $isPreviousPartRequired) { $isPreviousPartRequired ? $this->attributes |= FileHeaderAttribute::REQUIRE_PREVIOUS_PART : $this->attributes &= ~FileHeaderAttribute::REQUIRE_PREVIOUS_PART; } public function getIsNextPartRequired(): bool { if ($this->attributes & FileHeaderAttribute::REQUIRE_NEXT_PART) { return true; } return false; } public function setIsNextPartRequired(bool $isNextPartRequired) { $isNextPartRequired ? $this->attributes |= FileHeaderAttribute::REQUIRE_NEXT_PART : $this->attributes &= ~FileHeaderAttribute::REQUIRE_NEXT_PART; } public function getStartOffset(): int { return $this->startOffset; } public function setStartOffset(int $startOffset) { $this->startOffset = $startOffset; } public function getFilePath(): string { return $this->filePath; } public function setFilePath(string $filePath) { $this->filePath = $filePath; $filePathRenamed = $this->replaceEOLsWithPlaceholders($filePath); $this->filePathLength = strlen($filePathRenamed); } public function getFileName(): string { return $this->fileName; } public function setFileName(string $fileName) { $this->fileName = $fileName; $renamedFile = $this->replaceEOLsWithPlaceholders($fileName); $this->fileNameLength = strlen($renamedFile); } public function getExtraField(): string { return $this->extraField; } public function setExtraField(string $extraField) { $this->extraField = $extraField; $this->extraFieldLength = strlen($extraField); } public function getIdentifiablePath(): string { return $this->filePath . $this->fileName; } public function getDynamicHeaderLength(): int { return $this->filePathLength + $this->fileNameLength + $this->extraFieldLength; } public function getContentStartOffset(): int { return $this->startOffset + self::FILE_HEADER_FIXED_SIZE + $this->getDynamicHeaderLength() + 1; } public function validateFile(string $filePath, string $pathForErrorLogging = '') { if (empty($pathForErrorLogging)) { $pathForErrorLogging = $filePath; } if (!file_exists($filePath)) { throw new FileValidationException(sprintf('File doesn\'t exist: %s.', $pathForErrorLogging)); } $fileSize = filesize($filePath); if ($this->getUncompressedSize() !== $fileSize) { throw new FileValidationException(sprintf('Filesize validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->formatSize($this->getUncompressedSize(), 2), $this->formatSize($fileSize, 2))); } $crc32Checksum = hash_file(self::CRC32_CHECKSUM_ALGO, $filePath); if ($this->crc32Checksum !== $crc32Checksum) { throw new FileValidationException(sprintf('CRC32 Checksum validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->getCrc32Checksum(), $crc32Checksum)); } } }
    final class ExtractorService { use FormatTrait; const VALIDATE_DIRECTORY = 'validate'; const ITEM_SKIP_EXCEPTION_CODE = 4001; const FINISHED_QUEUE_EXCEPTION_CODE = 4002; const FILE_FILTERED_EXCEPTION_CODE = 4003; protected $extractingFile; protected $wpstgFile; protected $dirRestore; protected $wpstgIndexOffsetForCurrentFile; protected $wpstgIndexOffsetForNextFile; protected $extractorDto; protected $bytesWrittenThisRequest = 0; protected $isBackupFormatV1 = false; protected $pathIdentifier; protected $directory; protected $backupHeader; protected $indexLineDto; protected $backupMetadata; protected $extractIdentifier = ''; protected $isValidateOnly = false; protected $excludedIdentifier = []; protected $databaseBackupFile; protected $defaultDirectoryOctal = 0755; protected $currentIdentifier; protected $throwExceptionOnValidationFailure = false; public function __construct( PathIdentifier $pathIdentifier, DirectoryInterface $directory, BackupHeader $backupHeader, Permissions $permissions ) { $this->pathIdentifier = $pathIdentifier; $this->directory = $directory; $this->backupHeader = $backupHeader; $this->defaultDirectoryOctal = $permissions->getDirectoryOctal(); $this->excludedIdentifier = []; } public function setExcludedIdentifiers(array $excludedIdentifier) { $this->excludedIdentifier = $excludedIdentifier; } public function setExtractOnlyPart(string $partToExtract) { $this->excludedIdentifier = []; if (empty($partToExtract)) { return; } $parts = [ PartIdentifier::DATABASE_PART_IDENTIFIER, PartIdentifier::MU_PLUGIN_PART_IDENTIFIER, PartIdentifier::PLUGIN_PART_IDENTIFIER, PartIdentifier::THEME_PART_IDENTIFIER, PartIdentifier::UPLOAD_PART_IDENTIFIER, PartIdentifier::LANGUAGE_PART_IDENTIFIER, PartIdentifier::WP_CONTENT_PART_IDENTIFIER, PartIdentifier::WP_ROOT_PART_IDENTIFIER, ]; foreach ($parts as $part) { if ($part === $partToExtract) { continue; } if ($part === PartIdentifier::DROPIN_PART_IDENTIFIER) { $this->excludedIdentifier[] = PartIdentifier::DROPIN_PART_IDENTIFIER; continue; } if ($part === PartIdentifier::DATABASE_PART_IDENTIFIER) { $this->excludedIdentifier[] = PartIdentifier::DATABASE_PART_IDENTIFIER; continue; } $this->excludedIdentifier[] = $this->pathIdentifier->getIdentifierByPartName($part); } } public function setIndexLineDto(IndexLineInterface $indexLineDto) { $this->indexLineDto = $indexLineDto; } public function setIsBackupFormatV1(bool $isBackupFormatV1) { $this->isBackupFormatV1 = $isBackupFormatV1; } public function setThrowExceptionOnValidationFailure(bool $throwExceptionOnValidationFailure) { $this->throwExceptionOnValidationFailure = $throwExceptionOnValidationFailure; } public function getBytesWrittenInThisRequest(): int { return $this->bytesWrittenThisRequest; } public function getExtractorDto(): ExtractorDto { return $this->extractorDto; } public function setup(ExtractorDto $extractorDto, string $backupFilePath, string $tmpPath = '') { $this->dirRestore = $tmpPath; $this->extractorDto = $extractorDto; $this->setFileToExtract($backupFilePath); if (empty($this->dirRestore)) { $this->dirRestore = $this->directory->getTmpDirectory(); } $this->dirRestore = rtrim($this->dirRestore, '/') . '/'; } public function setFileToExtract(string $filePath) { try { $this->wpstgFile = new FileObject($filePath); $this->backupMetadata = new BackupMetadata(); $this->backupMetadata = $this->backupMetadata->hydrateByFile($this->wpstgFile); $this->databaseBackupFile = $this->backupMetadata->getDatabaseFile(); $this->extractorDto->setIndexStartOffset($this->backupMetadata->getHeaderStart()); $this->extractorDto->setTotalChunks($this->backupMetadata->getTotalChunks()); } catch (\Exception $ex) { $this->throwMissingFileException($ex, $filePath); } } public function findFileToExtract(int $fileToExtractOffset = 0) { if ($fileToExtractOffset > 0) { $this->extractorDto->setCurrentIndexOffset($fileToExtractOffset); } if ($this->extractorDto->getCurrentIndexOffset() === 0) { $this->extractorDto->setCurrentIndexOffset($this->extractorDto->getIndexStartOffset()); } $this->wpstgFile->fseek($this->extractorDto->getCurrentIndexOffset()); $this->wpstgIndexOffsetForCurrentFile = $this->wpstgFile->ftell(); $rawIndexFile = $this->wpstgFile->readAndMoveNext(); $this->wpstgIndexOffsetForNextFile = $this->wpstgFile->ftell(); if (!$this->indexLineDto->isIndexLine($rawIndexFile)) { throw new \Exception("", self::FINISHED_QUEUE_EXCEPTION_CODE); } $backupFileIndex = $this->indexLineDto->readIndexLine($rawIndexFile); $identifiablePath = $backupFileIndex->getIdentifiablePath(); $identifier = $this->pathIdentifier->getIdentifierFromPath($identifiablePath); $this->currentIdentifier = $identifier; if ($this->isFileSkipped($identifiablePath, $identifier)) { $this->extractorDto->incrementTotalFilesSkipped(); $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile); throw new \Exception('Skipping file: ' . $identifiablePath, self::ITEM_SKIP_EXCEPTION_CODE); } $extractFolder = $this->getExtractFolder($identifier); if (!$this->createDirectory($extractFolder)) { throw new \RuntimeException("Could not create folder to extract backup file: $extractFolder"); } $this->extractingFile = new FileBeingExtracted($backupFileIndex->getIdentifiablePath(), $extractFolder, $this->pathIdentifier, $backupFileIndex); $this->extractingFile->setWrittenBytes($this->extractorDto->getExtractorFileWrittenBytes()); if ($this->isFileExtracted($backupFileIndex, $this->extractingFile->getBackupPath())) { $this->extractorDto->incrementTotalFilesSkipped(); $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile); throw new \Exception('File already extracted: ' . $identifiablePath, self::ITEM_SKIP_EXCEPTION_CODE); } $this->cleanExistingFile($identifier); $this->wpstgFile->fseek($this->extractingFile->getCurrentOffset()); $this->indexLineDto = $backupFileIndex; } public function createEmptyFile(string $filePath): bool { if (file_exists($filePath)) { return true; } return $this->filePutContents($filePath, '') !== false; } public function isExtractingFileExtracted(callable $logInfo): bool { $this->bytesWrittenThisRequest += $this->extractingFile->getWrittenBytes(); if ($this->extractingFile->isFinished()) { return true; } if ($this->extractingFile->getWrittenBytes() > 0 && $this->isBigFile()) { $percentProcessed = ceil(($this->extractingFile->getWrittenBytes() / $this->extractingFile->getTotalBytes()) * 100); $logInfo(sprintf('Extracting big file: %s - %s/%s (%s%%)', $this->extractingFile->getRelativePath(), $this->formatSize($this->extractingFile->getWrittenBytes(), 2), $this->formatSize($this->extractingFile->getTotalBytes(), 2), $percentProcessed)); } $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForCurrentFile); $this->extractorDto->setExtractorFileWrittenBytes($this->extractingFile->getWrittenBytes()); return false; } public function validateExtractedFileAndMoveNext() { $destinationFilePath = $this->extractingFile->getBackupPath(); $pathForErrorLogging = $this->pathIdentifier->transformIdentifiableToPath($this->indexLineDto->getIdentifiablePath()); if (file_exists($destinationFilePath) && filesize($destinationFilePath) === 0 && $this->extractingFile->getTotalBytes() !== 0) { throw new \RuntimeException(sprintf('File %s is empty', $pathForErrorLogging)); } if ($this->isBackupFormatV1) { $this->maybeRemoveLastAccidentalCharFromLastExtractedFile(); } $isValidated = true; $exception = null; clearstatcache(); try { $this->indexLineDto->validateFile($destinationFilePath, $pathForErrorLogging); } catch (FileValidationException $e) { $isValidated = false; $exception = $e; } $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile); $this->extractorDto->incrementTotalFilesExtracted(); $this->extractorDto->setExtractorFileWrittenBytes(0); $this->deleteValidationFile($destinationFilePath); if (!$isValidated) { throw $exception; } } public function finishExtractingFile() { $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes()); } public function getExtractingFile(): FileBeingExtracted { return $this->extractingFile; } public function getBackupFileOffset(): int { return $this->wpstgFile->ftell(); } public function readBackup(int $dataLengthToRead): string { return $this->wpstgFile->fread($dataLengthToRead); } protected function isBigFile(): bool { return $this->extractingFile->getTotalBytes() > 10 * MB_IN_BYTES; } protected function maybeRemoveLastAccidentalCharFromLastExtractedFile() { if ($this->isValidateOnly) { return; } if ($this->backupMetadata->getTotalFiles() !== $this->extractorDto->getTotalFilesExtracted()) { return; } $this->removeLastCharInExtractedFile(); } protected function throwMissingFileException(\Exception $ex, string $filePath) { throw new \Exception(sprintf("Following backup part missing: %s", $filePath), 0, $ex); } protected function removeLastCharInExtractedFile() { $destinationFilePath = $this->extractingFile->getBackupPath(); $fileContent = file_get_contents($destinationFilePath); if (empty($fileContent)) { return; } if (substr($fileContent, -1) !== 'w') { return; } $fileContent = substr($fileContent, 0, -1); file_put_contents($destinationFilePath, $fileContent); } protected function getExtractFolder(string $identifier): string { return $this->dirRestore . $this->pathIdentifier->getRelativePath($identifier); } protected function cleanExistingFile(string $identifier) { if ($this->isValidateOnly) { return; } if ($this->extractingFile->getWrittenBytes() > 0) { return; } if (file_exists($this->extractingFile->getBackupPath())) { if (!unlink($this->extractingFile->getBackupPath())) { throw new \RuntimeException(sprintf(__('Could not delete original file %s. Skipping restore of it...', 'wp-staging'), $this->extractingFile->getRelativePath())); } } } protected function deleteValidationFile(string $filePath) { if (!$this->isValidateOnly) { return; } if (file_exists($filePath)) { @unlink($filePath); } } protected function isFileSkipped(string $identifiablePath, string $identifier): bool { if ($identifiablePath === $this->databaseBackupFile) { return in_array(PartIdentifier::DATABASE_PART_IDENTIFIER, $this->excludedIdentifier); } if ($identifier === PathIdentifier::IDENTIFIER_WP_CONTENT && $this->pathIdentifier->hasDropinsFile($identifiablePath)) { return in_array(PartIdentifier::DROPIN_PART_IDENTIFIER, $this->excludedIdentifier); } return in_array($identifier, $this->excludedIdentifier); } protected function isFileExtracted(IndexLineInterface $backupFileIndex, string $extractPath): bool { if (!file_exists($extractPath)) { return false; } return $backupFileIndex->getUncompressedSize() === filesize($extractPath); } private function filePutContents(string $filePath, string $content): bool { if ($fp = fopen($filePath, 'wb')) { $bytes = fwrite($fp, $content); fclose($fp); $fp = null; return $bytes; } return false; } private function createDirectory(string $directory): bool { if (file_exists($directory)) { return @is_dir($directory); } if (!is_dir($directory) && !mkdir($directory, $this->defaultDirectoryOctal, true)) { return false; } return true; } }
    final class BackupsFinder { use WithBackupIdentifier; use DebugLogTrait; const MAX_BACKUP_FILE_TO_SCAN = 1000; protected $backupsDirectory; protected $backupsCount; public function resetBackupsCount() { $this->backupsCount = 0; } public function setBackupsDirectory(string $backupsDirectory) { $this->backupsDirectory = $backupsDirectory; } public function getBackupsDirectory(bool $refresh = false): string { return $this->backupsDirectory; } public function findBackups(): array { try { $it = new \DirectoryIterator($this->getBackupsDirectory(true)); } catch (\Exception $e) { $this->debugLog('WP STAGING: Could not find backup directory ' . $e->getMessage()); return []; } $backups = []; $this->clearListedMultipartBackups(); foreach ($it as $file) { if (($file->getExtension() === 'wpstg' || $file->getExtension() === 'sql') && !$file->isLink()) { if ($this->backupsCount >= self::MAX_BACKUP_FILE_TO_SCAN) { break; } if ($this->isBackupPart($file->getFilename()) && $this->isListedMultipartBackup($file->getFilename())) { continue; } $backups[] = clone $file; $this->backupsCount++; } } return $backups; } public function findBackupByMd5Hash(string $md5): \SplFileInfo { $backup = array_filter($this->findBackups(), function ($splFileInfo) use ($md5) { return md5($splFileInfo->getBasename()) === $md5; }); if (empty($backup)) { throw new \UnexpectedValueException('WP STAGING: Could not find backup by hash ' . $md5); } return array_shift($backup); } }
    final class BackupContent { private $backupFile; private $totalFiles; private $filesFound; private $perPage; private $headerOffset = 0; private $indexPage = 0; private $currentOffset = 0; private $currentIndex = 0; private $indexLineDto; private $pathIdentifier; private $filters = [ 'filename' => '', 'sortby' => '', ]; private $databaseFiles = []; public function setBackup(string $backupFile, IndexLineInterface $indexLineDto, $backupMetadata = null) { if ($backupMetadata === null) { $backupMetadata = new BackupMetadata(); $backupMetadata = $backupMetadata->hydrateByFilePath($backupFile); } $this->backupFile = $backupFile; $this->indexLineDto = $indexLineDto; $this->totalFiles = $backupMetadata->getTotalFiles(); $this->headerOffset = $backupMetadata->getHeaderStart(); } public function setPerPage(int $perPage) { $this->perPage = $perPage; } public function setPathIdentifier(PathIdentifier $pathIdentifier) { $this->pathIdentifier = $pathIdentifier; } public function setDatabaseFiles(array $databaseFiles) { $this->databaseFiles = $databaseFiles; } public function setFilters(array $filters) { $filters['filename'] = $filters['filename'] ?? ''; $filters['sortby'] = $filters['sortby'] ?? ''; $this->filters = $filters; } public function getFiles(int $page = 1) { if ($page < 1) { $page = 1; } $this->indexPage = $page; $offset = ($page - 1) * $this->perPage; $wpstgFile = new FileObject($this->backupFile, 'rb'); $wpstgFile->fseek($this->headerOffset); $count = 0; $this->filesFound = 0; while ($wpstgFile->valid()) { $this->currentOffset = $wpstgFile->ftell(); $this->currentIndex = $wpstgFile->key(); $rawIndexFile = $wpstgFile->readAndMoveNext(); if (!$this->indexLineDto->isIndexLine($rawIndexFile)) { break; } $indexLineDto = $this->indexLineDto->readIndexLine($rawIndexFile); $backupFile = BackupItemDto::fromIndexLineDto($indexLineDto); $backupFile->setPath($this->pathIdentifier->transformIdentifiableToRelativePath($backupFile->getIdentifiablePath())); $backupFile->setOffset($this->currentOffset); $backupFile->setIndex($this->currentIndex); if ($this->isFiltered($backupFile)) { continue; } $this->filesFound++; if ($this->filesFound < $offset || $count === $this->perPage) { continue; } yield $backupFile; $count++; } } public function getPagingData(): array { return [ 'totalIndex' => $this->filesFound, 'totalPage' => ceil($this->filesFound / $this->perPage), 'indexPage' => $this->indexPage, 'indexFilter' => $this->filters['filename'], 'indexSortby' => $this->filters['sortby'], ]; } private function isFiltered(BackupItemDto $backupFile): bool { if ($this->filterByName($backupFile)) { return true; } return $this->filterBySortBy($backupFile); } private function filterByName(BackupItemDto $backupFile): bool { if (empty($this->filters['filename'])) { return false; } return strpos($backupFile->getPath(), $this->filters['filename']) === false; } private function filterBySortBy(BackupItemDto $backupFile): bool { if (empty($this->filters['sortby'])) { return false; } if ($this->filters['sortby'] === PartIdentifier::DATABASE_PART_IDENTIFIER) { return !in_array($backupFile->getIdentifiablePath(), $this->databaseFiles); } if ($this->filters['sortby'] === PartIdentifier::UPLOAD_PART_IDENTIFIER && in_array($backupFile->getIdentifiablePath(), $this->databaseFiles)) { return true; } if ($this->filters['sortby'] === PartIdentifier::DROPIN_PART_IDENTIFIER) { return !$this->pathIdentifier->hasDropinsFile($backupFile->getIdentifiablePath()); } $identifier = $this->pathIdentifier->getIdentifierByPartName($this->filters['sortby']); return $identifier !== $this->pathIdentifier->getIdentifierFromPath($backupFile->getIdentifiablePath()); } }
    final class SearchReplace
    {
        use DebugLogTrait;
        use SerializeTrait;
        use UrlTrait;
        private $search;
        private $replace;
        private $exclude;
        private $caseSensitive;
        private $currentSearch;
        private $currentReplace;
        private $isWpBakeryActive;
        protected $smallerReplacement = PHP_INT_MAX;
        public function __construct(array $search = [], array $replace = [], $caseSensitive = true, array $exclude = [])
        {
            $this->search           = $search;
            $this->replace          = $replace;
            $this->caseSensitive    = $caseSensitive;
            $this->exclude          = $exclude;
            $this->isWpBakeryActive = false;
        }
        public function getSmallerSearchLength()
        {
            if ($this->smallerReplacement < PHP_INT_MAX) {
                return $this->smallerReplacement;
            }
            foreach ($this->search as $search) {
                if (strlen($search) < $this->smallerReplacement) {
                    $this->smallerReplacement = strlen($search);
                }
            }
            return $this->smallerReplacement;
        }
        public function replace($data)
        {
            if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
                return $data;
            }
            if (!$this->search || !$this->replace) {
                return $data;
            }
            $totalSearch  = count($this->search);
            $totalReplace = count($this->replace);
            if ($totalSearch !== $totalReplace) {
                throw new \RuntimeException(
                    sprintf(
                        'Can not search and replace. There are %d items to search and %d items to replace',
                        $totalSearch,
                        $totalReplace
                    )
                );
            }
            for ($i = 0; $i < $totalSearch; $i++) {
                $this->currentSearch  = (string)$this->search[$i];
                $this->currentReplace = (string)$this->replace[$i];
                $data                 = $this->walker($data);
            }
            return $data;
        }
        public function replaceExtended($data)
        {
            if ($this->isWpBakeryActive) {
                $data = preg_replace_callback('/\[vc_raw_html\](.+?)\[\/vc_raw_html\]/S', [$this, 'replaceWpBakeryValues'], $data);
            }
            return $this->replace($data);
        }
        public function replaceWpBakeryValues($matched)
        {
            $data = $this->base64Decode($matched[1]);
            $data = $this->replace($data);
            return '[vc_raw_html]' . base64_encode($data) . '[/vc_raw_html]';
        }
        public function setSearch(array $search)
        {
            $this->search = $search;
            return $this;
        }
        public function setReplace(array $replace)
        {
            $this->replace = $replace;
            return $this;
        }
        public function setCaseSensitive($caseSensitive)
        {
            $this->caseSensitive = $caseSensitive;
            return $this;
        }
        public function setExclude(array $exclude)
        {
            $this->exclude = $exclude;
            return $this;
        }
        public function setWpBakeryActive($isActive = true)
        {
            $this->isWpBakeryActive = $isActive;
            return $this;
        }
        private function walker($data)
        {
            switch (gettype($data)) {
                case "string":
                    return $this->replaceString($data);
                case "array":
                    return $this->replaceArray($data);
                case "object":
                    return $this->replaceObject($data);
            }
            return $data;
        }
        private function replaceString($data)
        {
            if (!$this->isSerialized($data)) {
                return $this->strReplace($data);
            }
            if (strpos($data, 'O:3:"PDO":0:') !== false) {
                return $data;
            }
            if (strpos($data, 'O:8:"DateTime":0:') !== false) {
                return $data;
            }
            if (strpos($data, 'O:') !== false && preg_match_all('@O:\d+:"([^"]+)"@', $data, $match) && !empty($match) && !empty($match[1])) {
                foreach ($match[1] as $value) {
                    if ($value !== 'stdClass') {
                        return $data;
                    }
                }
                unset($match);
            }
            $unserialized = false;
            try {
                $unserialized = @unserialize($data);
            } catch (\Throwable $e) {
                $this->debugLog('replaceString. Can not unserialize data. Error: ' . $e->getMessage() . ' Data: ' . $data);
            }
            if ($unserialized !== false) {
                return serialize($this->walker($unserialized));
            }
            return $data;
        }
        private function replaceArray(array $data)
        {
            foreach ($data as $key => $value) {
                $data[$key] = $this->walker($value);
            }
            return $data;
        }
        private function replaceObject($data)
        {
            $props = get_object_vars($data);
            if (!empty($props['__PHP_Incomplete_Class_Name'])) {
                return $data;
            }
            foreach ($props as $key => $value) {
                if ($key === '' || (isset($key[0]) && ord($key[0]) === 0)) {
                    continue;
                }
                $data->{$key} = $this->walker($value);
            }
            return $data;
        }
        private function strReplace($data = '')
        {
            $regexExclude = '';
            foreach ($this->exclude as $excludeString) {
                $regexExclude .= $excludeString . '(*SKIP)(*FAIL)|';
            }
            $pattern = '#' . $regexExclude . preg_quote($this->currentSearch, null) . '#';
            if (!$this->caseSensitive) {
                $pattern .= 'i';
            }
            return preg_replace($pattern, $this->currentReplace, $data);
        }
    }
    final class MysqliAdapter implements InterfaceDatabaseClient { public $link; public function __construct($link = null) { $this->link = $link; } public function query($query) { return mysqli_query($this->link, $query); } public function realQuery($query, $isExecOnly = false) { if ($isExecOnly) { return mysqli_real_query($this->link, $query); } if (!mysqli_real_query($this->link, $query)) { return false; } if (defined('MYSQLI_STORE_RESULT_COPY_DATA')) { return mysqli_store_result($this->link, MYSQLI_STORE_RESULT_COPY_DATA); } return mysqli_store_result($this->link); } public function escape($input) { return mysqli_real_escape_string($this->link, $input); } public function errno() { return mysqli_errno($this->link); } public function error() { return mysqli_error($this->link); } public function version() { return mysqli_get_server_info($this->link); } public function fetchAll($result) { $data = []; while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; } return $data; } public function fetchAssoc($result) { return mysqli_fetch_assoc($result); } public function fetchRow($result) { return mysqli_fetch_row($result); } public function fetchObject($result) { return mysqli_fetch_object($result); } public function numRows($result) { return mysqli_num_rows($result); } public function freeResult($result) { if ($result === null) { return null; } mysqli_free_result($result); return null; } public function insertId() { return mysqli_insert_id($this->link); } public function foundRows() { return mysqli_affected_rows($this->link); } public function getLink() { return $this->link; } }
    final class DatabaseImporterDto { private $currentIndex = 0; private $totalLines = 0; private $tableToRestore = ''; private $tmpPrefix = ''; private $shortTablesToRestore = []; private $shortTablesToDrop = []; private $backupType = BackupMetadata::BACKUP_TYPE_SINGLE; private $subsiteId = null; public function getCurrentIndex(): int { return $this->currentIndex; } public function setCurrentIndex(int $currentIndex) { $this->currentIndex = $currentIndex; } public function getTotalLines(): int { return $this->totalLines; } public function setTotalLines(int $totalLines) { $this->totalLines = $totalLines; } public function finish() { $this->currentIndex = $this->totalLines; } public function getTableToRestore(): string { return $this->tableToRestore; } public function setTableToRestore(string $tableToRestore) { $this->tableToRestore = $tableToRestore; } public function getTmpPrefix(): string { return $this->tmpPrefix; } public function setTmpPrefix(string $tmpPrefix) { $this->tmpPrefix = $tmpPrefix; } public function addShortNameTable(string $table, string $prefix): string { $shortName = uniqid($prefix) . str_pad(rand(0, 999999), 6, '0'); if ($prefix === $this->tmpPrefix) { $this->shortTablesToRestore[$shortName] = $table; } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) { $this->shortTablesToDrop[$shortName] = $table; } return $shortName; } public function getShortNameTable(string $table, string $prefix): string { $shortTables = []; if ($prefix === $this->tmpPrefix) { $shortTables = $this->shortTablesToRestore; } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) { $shortTables = $this->shortTablesToDrop; } return (string)array_search($table, $shortTables); } public function getFullNameTableFromShortName(string $table, string $prefix): string { $shortTables = []; if ($prefix === $this->tmpPrefix) { $shortTables = $this->shortTablesToRestore; } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) { $shortTables = $this->shortTablesToDrop; } if (!array_key_exists($table, $shortTables)) { return $table; } return $shortTables[$table]; } public function getShortTables(string $prefix): array { if ($prefix === $this->tmpPrefix) { return $this->shortTablesToRestore; } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) { return $this->shortTablesToDrop; } return []; } public function setShortTables(array $tables, string $prefix) { if ($prefix === $this->tmpPrefix) { $this->shortTablesToRestore = $tables; } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) { $this->shortTablesToDrop = $tables; } } public function getBackupType(): string { return $this->backupType; } public function setBackupType(string $backupType) { $this->backupType = $backupType; } public function getSubsiteId() { return $this->subsiteId; } public function setSubsiteId($subsiteId) { $this->subsiteId = $subsiteId; } }
    final class ExtendedInserterWithoutTransaction extends QueryInserter { protected $extendedQuery = ''; public function processQuery(&$queryToInsert) { if ($this->doQueryExceedsMaxAllowedPacket($queryToInsert)) { return null; } $this->extendInsert($queryToInsert); if (strlen($this->extendedQuery) >= $this->limitedMaxAllowedPacket) { return $this->execExtendedQuery(); } return null; } public function commit() { $this->execExtendedQuery(); } public function execExtendedQuery() { if (empty($this->extendedQuery)) { return null; } $this->extendedQuery .= ';'; $success = $this->exec($this->extendedQuery); if ($success) { $this->extendedQuery = ''; $this->databaseImporterDto->setTableToRestore(''); return true; } else { $this->showError(); $this->extendedQuery = ''; $this->databaseImporterDto->setTableToRestore(''); return false; } } protected function showError() { switch ($this->client->errno()) { case 1153: case 2006: $this->addWarning($this->translate('The error message means got a packet bigger than max_allowed_packet bytes.', 'wp-staging')); break; case 1030: $this->addWarning($this->translate('Engine changed to InnoDB, as it your MySQL server does not support MyISAM.', 'wp-staging')); break; case 1071: case 1709: $this->addWarning($this->translate('Row format changed to DYNAMIC, as it would exceed the maximum length according to your MySQL settings. To not see this message anymore, please upgrade your MySQL version or increase the row format.', 'wp-staging')); break; case 1214: $this->addWarning($this->translate('FULLTEXT removed from query, as your current MySQL version does not support it. To not see this message anymore, please upgrade your MySQL version.', 'wp-staging')); break; case 1226: if (stripos($this->client->error(), 'max_queries_per_hour') !== false) { $this->addWarning($this->translate('Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. Please increase MySQL max_queries_per_hour_limit. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging')); } elseif (stripos($this->client->error(), 'max_updates_per_hour') !== false) { $this->addWarning($this->translate('Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. Please increase MySQL max_updates_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging')); } elseif (stripos($this->client->error(), 'max_connections_per_hour') !== false) { $this->addWarning($this->translate('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_connections_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging')); } elseif (stripos($this->client->error(), 'max_user_connections') !== false) { $this->addWarning($this->translate('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_user_connections. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging')); } break; case 1813: $this->addWarning($this->translate('Could not restore the database. MySQL returned the error code 1813, which is related to a tablespace error that WP STAGING can\'t handle. Please contact your hosting company.', 'wp-staging')); } if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) { $this->addWarning(sprintf($this->translate('ExtendedInserterWithoutTransaction Failed Query: %s', 'wp-staging'), substr($this->extendedQuery, 0, 1000))); } if ($this->backupDbVersion !== $this->currentDbVersion) { $additionalInfo = sprintf($this->translate(' Your current MySQL version is %s. If this issue persists, try using the same MySQL version used to create this Backup (%s).', 'wp-staging'), $this->currentDbVersion, $this->backupDbVersion); } $this->addWarning(sprintf($this->translate('Could not restore the query. MySQL has returned the error code %d, with message "%s".', 'wp-staging'), $this->client->errno(), $this->client->error()) . $additionalInfo); } protected function extendInsert(&$insertQuery) { preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $insertQuery, $matches); if (count($matches) !== 3) { throw new \Exception("Skipping INSERT query: $insertQuery"); } $insertingIntoTableName = $matches[1]; $extendedQueryMaxLength = $this->limitedMaxAllowedPacket; if (isset($this->client->isSQLite) && $this->client->isSQLite && method_exists($this->client, 'getSQLitePageSize')) { $extendedQueryMaxLength = $this->client->getSQLitePageSize(); $extendedQueryMaxLength = empty($extendedQueryMaxLength) ? 2048 : $extendedQueryMaxLength; } $insertingIntoHeader = "INSERT INTO `$insertingIntoTableName` VALUES "; $isFirstValue = false; if (empty($this->databaseImporterDto->getTableToRestore())) { if (!empty($this->extendedQuery)) { throw new \UnexpectedValueException('Query is not empty, cannot proceed.'); } $this->databaseImporterDto->setTableToRestore($insertingIntoTableName); $this->extendedQuery .= $insertingIntoHeader; $isFirstValue = true; } elseif ($insertingIntoTableName !== $this->databaseImporterDto->getTableToRestore()) { $this->execExtendedQuery(); if (!empty($this->extendedQuery)) { throw new \UnexpectedValueException('Query is not empty, cannot proceed.'); } $this->databaseImporterDto->setTableToRestore($insertingIntoTableName); $this->extendedQuery .= $insertingIntoHeader; $isFirstValue = true; } if (!$isFirstValue && strlen($this->extendedQuery . ",$matches[2]") >= $extendedQueryMaxLength) { $this->execExtendedQuery(); if (!empty($this->extendedQuery)) { throw new \UnexpectedValueException('Query is not empty, cannot proceed.'); } $this->databaseImporterDto->setTableToRestore($insertingIntoTableName); $this->extendedQuery .= $insertingIntoHeader; $isFirstValue = true; } if ($isFirstValue) { $this->extendedQuery .= $matches[2]; } else { $this->extendedQuery .= ",$matches[2]"; } } }
    final class QueryCompatibility { public function removeDefiner(&$query) { if (!stripos($query, 'DEFINER')) { return; } $query = preg_replace('# DEFINER\s?=\s?(.+?(?= )) #i', ' ', $query); } public function removeSqlSecurity(&$query) { if (!stripos($query, 'SQL SECURITY')) { return; } $query = preg_replace('# SQL SECURITY \w+ #i', ' ', $query); } public function removeAlgorithm(&$query) { if (!stripos($query, 'ALGORITHM')) { return; } $query = preg_replace('# ALGORITHM\s?=\s?`?\w+`? #i', ' ', $query); } public function replaceTableEngineIfUnsupported(&$query) { $query = str_ireplace([ 'ENGINE=MyISAM', 'ENGINE=Aria', ], [ 'ENGINE=InnoDB', 'ENGINE=InnoDB', ], $query); } public function replaceTableRowFormat(&$query) { $query = str_ireplace([ 'ENGINE=InnoDB', 'ENGINE=MyISAM', ], [ 'ENGINE=InnoDB ROW_FORMAT=DYNAMIC', 'ENGINE=MyISAM ROW_FORMAT=DYNAMIC', ], $query); } public function removeFullTextIndexes(&$query) { $query = preg_replace('#,\s?FULLTEXT \w+\s?`?\w+`?\s?\([^)]+\)#i', '', $query); } public function convertUtf8Mb4toUtf8(&$query) { $query = str_ireplace('utf8mb4', 'utf8', $query); } public function shortenKeyIdentifiers(&$query) { $shortIdentifiers = []; $matches = []; preg_match_all("#KEY `(.*?)`#", $query, $matches); foreach ($matches[1] as $identifier) { if (strlen($identifier) < 64) { continue; } $shortIdentifier = uniqid(DatabaseImporter::TMP_DATABASE_PREFIX) . str_pad(rand(0, 999999), 6, '0'); $shortIdentifiers[$shortIdentifier] = $identifier; } $query = str_replace(array_values($shortIdentifiers), array_keys($shortIdentifiers), $query); return $shortIdentifiers; } public function pageCompressionMySQL(&$query, $errorMessage) { if (strpos($errorMessage, 'PAGE_COMPRESSED') === false) { return ''; } $query = str_replace([ "`PAGE_COMPRESSED`='ON'", "`PAGE_COMPRESSED`='OFF'", "`PAGE_COMPRESSED`='0'", "`PAGE_COMPRESSED`='1'", ], ['', '', '', ''], $query); preg_match('/create\s+table\s+\`?(\w+)`/i', $query, $matches); return $matches[1]; } }
    final class DatabaseImporter { use DebugLogTrait; use ApplyFiltersTrait; use SerializeTrait; const THRESHOLD_EXCEPTION_CODE = 2001; const FINISHED_QUEUE_EXCEPTION_CODE = 2002; const RETRY_EXCEPTION_CODE = 2003; const FILE_FORMAT = 'sql'; const TMP_DATABASE_PREFIX = 'wpstgtmp_'; const TMP_DATABASE_PREFIX_TO_DROP = 'wpstgbak_'; const NULL_FLAG = "{WPSTG_NULL}"; const BINARY_FLAG = "{WPSTG_BINARY}"; private $file; private $totalLines; private $client; private $databaseImporterDto; private $database; private $warningLogCallable; private $searchReplace; private $searchReplaceForPrefix; private $tmpDatabasePrefix; private $queryInserter; private $smallerSearchLength; private $binaryFlagLength; private $queryCompatibility; private $isSameSiteBackupRestore = false; private $tablesExcludedFromSearchReplace = []; private $subsiteManager; private $backupDbVersion; public function __construct( DatabaseInterface $database, QueryInserter $queryInserter, QueryCompatibility $queryCompatibility, SubsiteManagerInterface $subsiteManager ) { $this->client = $database->getClient(); $this->database = $database; $this->queryInserter = $queryInserter; $this->queryCompatibility = $queryCompatibility; $this->subsiteManager = $subsiteManager; $this->binaryFlagLength = strlen(self::BINARY_FLAG); } public function setFile($filePath) { $this->file = new FileObject($filePath); $this->totalLines = $this->file->totalLines(); return $this; } public function seekLine($line) { if (!$this->file) { throw new \RuntimeException('Restore file is not set'); } $this->file->seek($line); return $this; } public function init(string $tmpDatabasePrefix) { $this->tmpDatabasePrefix = $tmpDatabasePrefix; $this->databaseImporterDto->setTmpPrefix($this->tmpDatabasePrefix); $this->setupSearchReplaceForPrefix(); if (!$this->file) { throw new \RuntimeException('Restore file is not set'); } $this->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'"); if ($this->applyFilters('wpstg.backup.restore.innodbStrictModeOff', false) === true) { $this->exec("SET SESSION innodb_strict_mode=OFF"); } } public function retryQuery() { $this->databaseImporterDto->setCurrentIndex($this->file->key() - 1); $this->queryInserter->commit(); } public function updateIndex() { $this->databaseImporterDto->setCurrentIndex($this->file->key()); $this->queryInserter->commit(); } public function getCurrentOffset(): int { return (int)$this->file->ftell(); } public function finish() { $this->databaseImporterDto->finish(); $this->queryInserter->commit(); } public function getQueryCompatibility(): QueryCompatibility { return $this->queryCompatibility; } public function isSupportPageCompression(): bool { static $hasCompression; if ($hasCompression !== null) { return $hasCompression; } if (!$this->isMariaDB()) { return false; } $query = "SHOW GLOBAL STATUS WHERE Variable_name IN ('Innodb_have_lz4', 'Innodb_have_lzo', 'Innodb_have_lzma', 'Innodb_have_bzip2', 'Innodb_have_snappy');"; $result = $this->client->query($query); if (! ($result instanceof \mysqli_result)) { return false; } while ($row = $result->fetch_assoc()) { if ($row['Value'] === 'ON') { $hasCompression = true; return true; } } $hasCompression = false; return false; } public function isMariaDB(): bool { return stripos($this->serverInfo(), 'MariaDB') !== false; } public function removePageCompression(&$query): bool { if (!strpos($query, 'PAGE_COMPRESSED') || !(stripos($query, "CREATE TABLE") == 0)) { return false; } if ($this->isSupportPageCompression()) { return false; } $query = preg_replace("@`?PAGE_COMPRESSED`?='?(ON|OFF|0|1)'?@", '', $query); if (strpos($query, 'PAGE_COMPRESSION_LEVEL') !== false) { $query = preg_replace("@`?PAGE_COMPRESSION_LEVEL`?='?\d+'?@", '', $query); } return true; } public function setup(DatabaseImporterDto $databaseImporterDto, bool $isSameSiteBackupRestore, string $backupDbVersion) { $this->databaseImporterDto = $databaseImporterDto; $this->isSameSiteBackupRestore = $isSameSiteBackupRestore; $this->backupDbVersion = $backupDbVersion; $this->queryInserter->setDbVersions($this->serverVersion(), $this->backupDbVersion); $this->queryInserter->initialize($this->client, $this->databaseImporterDto); $this->subsiteManager->initialize($this->databaseImporterDto); } public function setupNonWpTables(array $nonWpTables) { $this->tablesExcludedFromSearchReplace = $nonWpTables; } public function setSearchReplace(SearchReplace $searchReplace) { $this->searchReplace = $searchReplace; $this->smallerSearchLength = min($searchReplace->getSmallerSearchLength(), $this->binaryFlagLength); return $this; } public function getTotalLines() { return $this->totalLines; } public function setWarningLogCallable(callable $callable) { $this->warningLogCallable = $callable; } public function execute() { $query = $this->findExecutableQuery(); if (!$query) { throw new \Exception("", self::FINISHED_QUEUE_EXCEPTION_CODE); } $query = $this->searchReplaceForPrefix->replace($query); $query = $this->maybeShorterTableNameForDropTableQuery($query); $query = $this->maybeShorterTableNameForCreateTableQuery($query); $query = $this->maybeFixReplaceTableConstraints($query); $this->replaceTableCollations($query); if (strpos($query, 'INSERT INTO') === 0) { if ($this->isExcludedInsertQuery($query)) { $this->debugLog('processQuery - This query has been skipped from inserting by using a custom filter: ' . $query); $this->logWarning(sprintf('The query has been skipped from inserting by using a custom filter: %s.', esc_html($query))); return false; } if ($this->subsiteManager->isTableFromDifferentSubsite($query)) { $this->subsiteManager->updateSubsiteId(); throw new \Exception("", self::RETRY_EXCEPTION_CODE); } if ( !$this->isSameSiteBackupRestore || (strpos($query, self::BINARY_FLAG) !== false) || (strpos($query, self::NULL_FLAG) !== false) ) { $this->searchReplaceInsertQuery($query); } try { $result = $this->queryInserter->processQuery($query); } catch (\Exception $e) { throw $e; } if ($result === null && $this->queryInserter->getLastError() !== false) { $this->logWarning($this->queryInserter->getLastError()); } } else { $this->queryInserter->commit(); $this->queryCompatibility->removeDefiner($query); $this->queryCompatibility->removeSqlSecurity($query); $this->queryCompatibility->removeAlgorithm($query); $result = $this->exec($query); } $errorNo = $this->client->errno(); $errorMsg = $this->client->error(); $currentDbVersion = $this->database->getSqlVersion($compact = true); $backupDbVersion = $this->backupDbVersion; if ($result === false) { switch ($this->client->errno()) { case 1030: $this->queryCompatibility->replaceTableEngineIfUnsupported($query); $result = $this->exec($query); if ($result) { $this->logWarning('Engine changed to InnoDB, as it your MySQL server does not support MyISAM.'); } break; case 1071: case 1709: $this->queryCompatibility->replaceTableRowFormat($query); $replaceUtf8Mb4 = ($errorNo === 1071 && version_compare($currentDbVersion, '5.7', '<')); if ($replaceUtf8Mb4) { $this->queryCompatibility->convertUtf8Mb4toUtf8($query); } $result = $this->exec($query); if ($result) { $this->logWarning('Row format changed to DYNAMIC, as it would exceed the maximum length according to your MySQL settings. To not see this message anymore, please upgrade your MySQL version or increase the row format.'); } if ($replaceUtf8Mb4 && $result) { $this->logWarning('Encoding changed to UTF8 from UTF8MB4, as your current MySQL version max key length support is 767 bytes'); } break; case 1214: $this->queryCompatibility->removeFullTextIndexes($query); $result = $this->exec($query); if ($result) { $this->logWarning('FULLTEXT removed from query, as your current MySQL version does not support it. To not see this message anymore, please upgrade your MySQL version.'); } break; case 1226: if (stripos($this->client->error(), 'max_queries_per_hour') !== false) { throw new \RuntimeException('Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. Please increase MySQL max_queries_per_hour_limit. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>'); } elseif (stripos($this->client->error(), 'max_updates_per_hour') !== false) { throw new \RuntimeException('Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. Please increase MySQL max_updates_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>'); } elseif (stripos($this->client->error(), 'max_connections_per_hour') !== false) { throw new \RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_connections_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>'); } elseif (stripos($this->client->error(), 'max_user_connections') !== false) { throw new \RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_user_connections. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>'); } break; case 1118: throw new \RuntimeException('Your server has reached the maximum row size of the table. Please refer to the documentation on how to fix it. <a href="https://wp-staging.com/docs/mysql-database-error-codes" target="_blank">Technical details</a>'); case 1059: $shortIdentifiers = $this->queryCompatibility->shortenKeyIdentifiers($query); $result = $this->exec($query); if ($result) { foreach ($shortIdentifiers as $shortIdentifier => $identifier) { $this->logWarning(sprintf('Key identifier `%s` exceeds the characters limits, it is now shortened to `%s` to continue restoring.', $identifier, $shortIdentifier)); } } break; case 1064: $tableName = $this->queryCompatibility->pageCompressionMySQL($query, $errorMsg); if (!empty($tableName)) { $result = $this->exec($query); } if (!empty($tableName) && $result) { $this->logWarning(sprintf('PAGE_COMPRESSED removed from Table: %s, as it is not a supported syntax in MySQL.', $tableName)); } break; case 1813: throw new \RuntimeException('Could not restore the database. MySQL returned the error code 1813, which is related to a tablespace error that WP STAGING can\'t handle. Please contact your hosting company.'); } if ($result) { return true; } if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) { $this->logWarning(sprintf('Database Restorer - Failed Query: %s', substr($query, 0, 1000))); $this->debugLog(sprintf('Database Restorer Failed Query: %s', substr($query, 0, 1000))); if (isset($this->client->isSQLite) && $this->client->isSQLite) { $this->debugLog($errorMsg); } } $errorNo = $this->client->errno(); $errorMsg = $this->client->error(); $additionalInfo = ''; if ($backupDbVersion !== $currentDbVersion) { $additionalInfo = sprintf(' Your current MySQL version is %s. If this issue persists, try using the same MySQL version used to create this Backup (%s).', $currentDbVersion, $backupDbVersion); } throw new \RuntimeException(sprintf('Could not restore query. MySQL has returned the error code %d, with message "%s".', $errorNo, $errorMsg) . $additionalInfo); } return $result; } protected function maybeShorterTableNameForDropTableQuery(&$query) { if (strpos($query, "DROP TABLE IF EXISTS") !== 0) { return $query; } preg_match('#^DROP TABLE IF EXISTS `(.+?(?=`))`;$#', $query, $dropTableExploded); $tableName = $dropTableExploded[1]; if (strlen($tableName) > 64) { $tableName = $this->databaseImporterDto->addShortNameTable($tableName, $this->tmpDatabasePrefix); } return "DROP TABLE IF EXISTS `$tableName`;"; } protected function maybeShorterTableNameForCreateTableQuery(&$query) { if (strpos($query, "CREATE TABLE") !== 0) { return $query; } preg_match('#^CREATE TABLE `(.+?(?=`))`#', $query, $createTableExploded); $tableName = $createTableExploded[1]; if (strlen($tableName) > 64) { $shortName = $this->databaseImporterDto->getShortNameTable($tableName, $this->tmpDatabasePrefix); return str_replace($tableName, $shortName, $query); } return $query; } protected function maybeFixReplaceTableConstraints(&$query) { if (strpos($query, "CREATE TABLE") !== 0) { return $query; } if (preg_match('@KEY\s+\`.*\`\s+?\(.*\)(,(\s+)?\`.*`\)\s+ON\s+(DELETE|UPDATE).*?)\)@i', $query, $matches)) { $query = str_replace($matches[1], '', $query); } $patterns = [ '/\s+CONSTRAINT(.+)REFERENCES(.+)(\s+)?,/i', '/,(\s+)?(KEY(.+))?CONSTRAINT(.+)REFERENCES(.+)\`\)(\s+)?\)/i', ]; $replace = ['', ')']; $query = preg_replace($patterns, $replace, $query); if ($this->isCorruptedCreateTableQuery($query)) { $query = $this->replaceLastMatch("`);", "`) );", $query); } return $query; } public function searchReplaceInsertQuery(&$query) { if (!$this->searchReplace) { throw new \RuntimeException('SearchReplace not set'); } $querySize = strlen($query); if ($querySize > ini_get('pcre.backtrack_limit')) { $this->logWarning( sprintf( 'Skipped search & replace on query: "%s" Increasing pcre.backtrack_limit can fix it! Query Size: %s. pcre.backtrack_limit: %s', substr($query, 0, 1000) . '...', $querySize, ini_get('pcre.backtrack_limit') ) ); return; } preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $query, $insertIntoExploded); if (count($insertIntoExploded) !== 3) { $this->debugLog($query); throw new \OutOfBoundsException('Skipping insert query. The query was logged....'); } $tableName = $insertIntoExploded[1]; if (strlen($tableName) > 64) { $tableName = $this->databaseImporterDto->getShortNameTable($tableName, $this->tmpDatabasePrefix); } $values = $insertIntoExploded[2]; preg_match_all("#'(?:[^'\\\]++|\\\.)*+'#s", $values, $valueMatches); if (count($valueMatches) !== 1) { throw new \RuntimeException('Value match in query does not match.'); } $valueMatches = $valueMatches[0]; $query = "INSERT INTO `$tableName` VALUES ("; foreach ($valueMatches as $value) { if (empty($value) || $value === "''") { $query .= "'', "; continue; } if ($value === "'" . self::NULL_FLAG . "'") { $query .= "NULL, "; continue; } if ($this->smallerSearchLength > strlen($value) - 2) { $query .= "{$value}, "; continue; } $value = substr($value, 1, -1); if (strpos($value, self::BINARY_FLAG) === 0) { $query .= "UNHEX('" . substr($value, strlen(self::BINARY_FLAG)) . "'), "; continue; } if ($this->isSameSiteBackupRestore || !$this->shouldSearchReplace($query)) { $query .= "'{$value}', "; continue; } if ($this->isSerialized($value)) { $value = $this->undoMySqlRealEscape($value); $value = $this->searchReplace->replaceExtended($value); $value = $this->mySqlRealEscape($value); } else { $value = $this->searchReplace->replaceExtended($value); } $query .= "'{$value}', "; } $query = rtrim($query, ', '); $query .= ');'; } protected function undoMySqlRealEscape(&$query) { $replacementMap = [ "\\0" => "\0", "\\n" => "\n", "\\r" => "\r", "\\t" => "\t", "\\Z" => chr(26), "\\b" => chr(8), '\"' => '"', "\'" => "'", '\\\\' => '\\', ]; return strtr($query, $replacementMap); } protected function mySqlRealEscape(&$query) { $replacementMap = [ "\0" => "\\0", "\n" => "\\n", "\r" => "\\r", "\t" => "\\t", chr(26) => "\\Z", chr(8) => "\\b", '"' => '\"', "'" => "\'", '\\' => '\\\\', ]; return strtr($query, $replacementMap); } protected function setupSearchReplaceForPrefix() { $this->searchReplaceForPrefix = new SearchReplace(['{WPSTG_TMP_PREFIX}', '{WPSTG_FINAL_PREFIX}'], [$this->tmpDatabasePrefix, $this->database->getPrefix()], true, []); } protected function shouldSearchReplace($query) { if (empty($this->tablesExcludedFromSearchReplace)) { return true; } preg_match('#^INSERT INTO `(.+?(?=`))` VALUES#', $query, $insertIntoExploded); $tableName = $insertIntoExploded[0]; return !in_array($tableName, $this->tablesExcludedFromSearchReplace); } private function findExecutableQuery() { while (!$this->file->eof()) { $line = $this->getLine(); if ($this->isExecutableQuery($line)) { return $line; } } return; } private function getLine() { if ($this->file->eof()) { return; } return trim($this->file->readAndMoveNext()); } public function isExecutableQuery($query = null) { if (!$query) { return false; } $first2Chars = substr($query, 0, 2); if ($first2Chars === '--' || strpos($query, '#') === 0) { return false; } if ($first2Chars === '/*') { return false; } if (stripos($query, 'start transaction;') === 0) { return false; } if (stripos($query, 'commit;') === 0) { return false; } if (substr($query, -strlen(1)) !== ';') { $this->logWarning('Skipping query because it does not end with a semi-colon... The query was logged.'); $this->debugLog($query); return false; } return true; } private function exec($query) { $result = $this->client->query($query); return $result !== false; } private function replaceTableCollations(string &$input) { static $search = []; static $replace = []; if (!empty($search) && !empty($replace)) { $input = str_replace($search, $replace, $input); return; } if ($this->hasCapabilities('utf8mb4_520')) { $search = ['utf8mb4_0900_ai_ci']; $replace = ['utf8mb4_unicode_520_ci']; $input = str_replace($search, $replace, $input); return; } if (!$this->hasCapabilities('utf8mb4')) { $search = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci', 'utf8mb4']; $replace = ['utf8_unicode_ci', 'utf8_unicode_ci', 'utf8']; } else { $search = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci']; $replace = ['utf8mb4_unicode_ci', 'utf8mb4_unicode_ci']; } $input = str_replace($search, $replace, $input); } private function isExcludedInsertQuery($query) { $excludedQueries = $this->applyFilters('wpstg.database.import.excludedQueries', []); if (empty($excludedQueries)) { return false; } foreach ($excludedQueries as $excludedQuery) { if (strpos($query, $excludedQuery) === 0) { return true; } } return false; } private function replaceLastMatch(string $needle, string $replace, string $haystack): string { $result = $haystack; $pos = strrpos($haystack, $needle); if ($pos !== false) { $result = substr_replace($haystack, $replace, $pos, strlen($needle)); } return $result; } protected function isCorruptedCreateTableQuery(string $query): bool { if (strpos($query, "ENGINE") !== false) { return false; } if (strpos($query, "CHARSET") !== false) { return false; } if (strpos($query, "COLLATE") !== false) { return false; } return true; } protected function logWarning(string $message) { $callable = $this->warningLogCallable; $callable($message); } private function hasCapabilities(string $capabilities): bool { $serverVersion = $this->serverVersion(); $serverInfo = $this->serverInfo(); if ($serverVersion === '5.5.5' && strpos($serverInfo, 'MariaDB') !== false && PHP_VERSION_ID < 80016) { $serverInfo = preg_replace('@^5\.5\.5-(.*)@', '$1', $serverInfo); $serverVersion = preg_replace('@[^0-9.].*@', '', $serverInfo); } switch (strtolower($capabilities)) { case 'collation': return version_compare($serverVersion, '4.1', '>='); case 'set_charset': return version_compare($serverVersion, '5.0.7', '>='); case 'utf8mb4': if (version_compare($serverVersion, '5.5.3', '<')) { return false; } $clienVersion = $this->clientInfo(); if (false !== strpos($clienVersion, 'mysqlnd')) { $clienVersion = preg_replace('@^\D+([\d.]+).*@', '$1', $clienVersion); return version_compare($clienVersion, '5.0.9', '>='); } else { return version_compare($clienVersion, '5.5.3', '>='); } case 'utf8mb4_520': return version_compare($serverVersion, '5.6', '>='); } return false; } private function clientInfo(): string { return !empty($this->client->getLink()->host_info) ? $this->client->getLink()->host_info : ''; } private function serverInfo(): string { return !empty($this->client->getLink()->server_info) ? $this->client->getLink()->server_info : ''; } private function serverVersion(): string { $serverInfo = $this->serverInfo(); if (stripos($serverInfo, 'MariaDB') !== false && preg_match('@^([0-9\.]+)\-([0-9\.]+)\-MariaDB@i', $serverInfo, $match)) { return $match[2]; } return preg_replace('@[^0-9\.].*@', '', $serverInfo); } }
    final class AdjustSubsitesMeta { use ApplyFiltersTrait; use DebugLogTrait; use SlashTrait; use UrlTrait; const FILTER_MULTISITE_SUBSITES = 'wpstg.backup.restore.multisites.subsites'; protected $sites; private $sourceSiteDomain; private $sourceSitePath; private $sourceSiteUrl; private $sourceHomeUrl; protected $isSourceSubdomainInstall; public function getSourceSiteDomain(): string { return $this->sourceSiteDomain; } public function getSourceSitePath(): string { return $this->sourceSitePath; } public function getSourceSiteUrl(): string { return $this->sourceSiteUrl; } public function getSourceHomeUrl(): string { return $this->sourceHomeUrl; } public function getIsSourceSubdomainInstall(): bool { return $this->isSourceSubdomainInstall; } public function setSourceSiteDomain(string $sourceSiteDomain) { $this->sourceSiteDomain = $sourceSiteDomain; } public function setSourceSitePath(string $sourceSitePath) { $this->sourceSitePath = $sourceSitePath; } public function setSourceSiteUrl(string $sourceSiteUrl) { $this->sourceSiteUrl = $sourceSiteUrl; } public function setSourceHomeUrl(string $sourceHomeUrl) { $this->sourceHomeUrl = $sourceHomeUrl; } public function setSourceSubdomainInstall(bool $isSubdomainInstall) { $this->isSourceSubdomainInstall = $isSubdomainInstall; } public function setSourceSites(array $sites) { $this->sites = []; foreach ($sites as $site) { $this->sites[] = SubsiteDto::createFromSiteData($site); } } public function getAdjustedSubsites(string $baseDomain, string $basePath, string $siteURL, string $homeURL, bool $isSubdomainInstall): array { $adjustedSites = []; foreach ($this->sites as $site) { $adjustedSite = $this->adjustSubsite($site, $baseDomain, $basePath, $siteURL, $homeURL, $isSubdomainInstall); $adjustedSites[] = $adjustedSite->toArray(); } $filteredAdjustedSites = $this->applyFilters(self::FILTER_MULTISITE_SUBSITES, $adjustedSites, $baseDomain, $basePath, $siteURL, $homeURL, $isSubdomainInstall); if (is_array($filteredAdjustedSites)) { return $filteredAdjustedSites; } $this->debugLog('Filter: wpstg.backup.restore.multisites.subsites does not return an array. Using default subsites.'); return $adjustedSites; } public function readBackupMetadata(BackupMetadata $backupMetadata) { $this->isSourceSubdomainInstall = $backupMetadata->getSubdomainInstall(); $this->sourceSiteUrl = $backupMetadata->getSiteUrl(); $this->sourceHomeUrl = $backupMetadata->getHomeUrl(); $sourceSiteURLWithoutWWW = str_ireplace('//www.', '//', $this->sourceSiteUrl); $parsedURL = parse_url($sourceSiteURLWithoutWWW); if (!is_array($parsedURL) || !array_key_exists('host', $parsedURL)) { throw new \UnexpectedValueException("Bad URL format, cannot proceed."); } $this->sourceSiteDomain = $parsedURL['host']; $this->sourceSitePath = '/'; if (array_key_exists('path', $parsedURL)) { $this->sourceSitePath = $parsedURL['path']; } $this->sites = []; foreach ($backupMetadata->getSites() as $site) { $this->sites[] = SubsiteDto::createFromSiteData($site); } } private function adjustSubsite(SubsiteDto $site, string $baseDomain, string $basePath, string $siteURL, string $homeURL, bool $isSubdomainInstall): AdjustedSubsiteDto { $sourceSiteDomain = strpos($this->sourceSiteDomain, 'www.') === 0 ? substr($this->sourceSiteDomain, 4) : $this->sourceSiteDomain; $subsiteDomain = strpos($site->getDomain(), 'www.') === 0 ? substr($site->getDomain(), 4) : $site->getDomain(); if ($sourceSiteDomain === $subsiteDomain && $this->sourceSitePath === $site->getPath()) { $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray()); $adjustedSite->setAdjustedDomain($baseDomain); $adjustedSite->setAdjustedPath($basePath); $adjustedSite->setAdjustedSiteUrl(rtrim($siteURL, '/')); $adjustedSite->setAdjustedHomeUrl(rtrim($homeURL, '/')); return $adjustedSite; } $sourceSiteUrlWithoutScheme = $this->getUrlWithoutScheme($this->sourceSiteUrl); $sourceHomeUrlWithoutScheme = $this->getUrlWithoutScheme($this->sourceHomeUrl); $destinationSiteUrlWithoutScheme = $this->getUrlWithoutScheme($siteURL); $destinationHomeUrlWithoutScheme = $this->getUrlWithoutScheme($homeURL); $subsiteSiteUrlWwwPrefix = ''; if (strpos($destinationSiteUrlWithoutScheme, 'www.') === 0) { $subsiteSiteUrlWwwPrefix = 'www.'; } $subsiteHomeUrlWwwPrefix = ''; if (strpos($destinationHomeUrlWithoutScheme, 'www.') === 0) { $subsiteHomeUrlWwwPrefix = 'www.'; } $sourceSiteUrlWithoutScheme = strpos($sourceSiteUrlWithoutScheme, 'www.') === 0 ? substr($sourceSiteUrlWithoutScheme, 4) : $sourceSiteUrlWithoutScheme; $sourceHomeUrlWithoutScheme = strpos($sourceHomeUrlWithoutScheme, 'www.') === 0 ? substr($sourceHomeUrlWithoutScheme, 4) : $sourceHomeUrlWithoutScheme; $destinationSiteUrlWithoutScheme = strpos($destinationSiteUrlWithoutScheme, 'www.') === 0 ? substr($destinationSiteUrlWithoutScheme, 4) : $destinationSiteUrlWithoutScheme; $destinationHomeUrlWithoutScheme = strpos($destinationHomeUrlWithoutScheme, 'www.') === 0 ? substr($destinationHomeUrlWithoutScheme, 4) : $destinationHomeUrlWithoutScheme; $subsiteDomain = str_replace($this->sourceSiteDomain, $baseDomain, $site->getDomain()); $subsitePath = str_replace($this->trailingslashit($this->sourceSitePath), $basePath, $site->getPath()); $subsiteSiteUrlWithoutScheme = str_replace($sourceSiteUrlWithoutScheme, $destinationSiteUrlWithoutScheme, $site->getSiteUrl()); $subsiteSiteUrlWithoutScheme = $this->getUrlWithoutScheme($subsiteSiteUrlWithoutScheme); $subsiteSiteUrlWithoutScheme = strpos($subsiteSiteUrlWithoutScheme, 'www.') === 0 ? substr($subsiteSiteUrlWithoutScheme, 4) : $subsiteSiteUrlWithoutScheme; $subsiteHomeUrlWithoutScheme = str_replace($sourceHomeUrlWithoutScheme, $destinationHomeUrlWithoutScheme, $site->getHomeUrl()); $subsiteHomeUrlWithoutScheme = $this->getUrlWithoutScheme($subsiteHomeUrlWithoutScheme); $subsiteHomeUrlWithoutScheme = strpos($subsiteHomeUrlWithoutScheme, 'www.') === 0 ? substr($subsiteHomeUrlWithoutScheme, 4) : $subsiteHomeUrlWithoutScheme; $subsiteSiteUrlSchemePrefix = parse_url($siteURL, PHP_URL_SCHEME) . '://'; $subsiteHomeUrlSchemePrefix = parse_url($homeURL, PHP_URL_SCHEME) . '://'; $baseSiteUrlWithoutScheme = $this->untrailingslashit($baseDomain . $basePath); $addWwwPrefix = strpos($baseDomain, 'www.') === 0 ? true : false; $subsiteDomain = rtrim($subsiteDomain, '/'); $subsiteDomain = strpos($subsiteDomain, 'www.') === 0 ? substr($subsiteDomain, 4) : $subsiteDomain; $subsiteDomain = $addWwwPrefix ? 'www.' . $subsiteDomain : $subsiteDomain; if ($this->isSourceSubdomainInstall === $isSubdomainInstall && $subsiteSiteUrlWithoutScheme === $baseSiteUrlWithoutScheme && $this->areBothHomeUrlSiteUrlInSameDomain($subsiteHomeUrlWithoutScheme, $subsiteSiteUrlWithoutScheme)) { $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray()); $adjustedSite->setAdjustedDomain($subsiteDomain); $adjustedSite->setAdjustedPath($subsitePath); $adjustedSite->setAdjustedSiteUrl($subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWwwPrefix . $subsiteSiteUrlWithoutScheme); $adjustedSite->setAdjustedHomeUrl($subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWwwPrefix . $subsiteHomeUrlWithoutScheme); return $adjustedSite; } $baseSiteUrlWithoutScheme = strpos($baseSiteUrlWithoutScheme, 'www.') === 0 ? substr($baseSiteUrlWithoutScheme, 4) : $baseSiteUrlWithoutScheme; if (strpos($subsiteSiteUrlWithoutScheme, $baseSiteUrlWithoutScheme) === false) { return $this->adjustDomainBasedSubsite($site, $baseDomain, $basePath, $subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWwwPrefix, $subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWwwPrefix, $isSubdomainInstall); } $adjustSiteUrl = $this->getAdjustedSubsiteInfo($baseDomain, $basePath, $baseSiteUrlWithoutScheme, $subsiteSiteUrlWithoutScheme, $subsiteSiteUrlWwwPrefix, $isSubdomainInstall); $subsiteDomain = $adjustSiteUrl['domain']; $subsitePath = $adjustSiteUrl['path']; $subsiteSiteUrlWithoutScheme = $adjustSiteUrl['url']; $adjustHomeUrl = $this->getAdjustedSubsiteInfo($baseDomain, $basePath, $baseSiteUrlWithoutScheme, $subsiteHomeUrlWithoutScheme, $subsiteHomeUrlWwwPrefix, $isSubdomainInstall); $subsiteHomeUrlWithoutScheme = $adjustHomeUrl['url']; $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray()); $adjustedSite->setAdjustedDomain(rtrim($subsiteDomain, '/')); $adjustedSite->setAdjustedPath($subsitePath); $adjustedSite->setAdjustedSiteUrl($subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWithoutScheme); $adjustedSite->setAdjustedHomeUrl($subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWithoutScheme); return $adjustedSite; } protected function adjustDomainBasedSubsite(SubsiteDto $site, string $baseDomain, string $basePath, string $siteUrlSchemaPrefix, string $homeUrlSchemaPrefix, bool $isSubdomainInstall): AdjustedSubsiteDto { $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray()); $baseDomain = rtrim($baseDomain, '/'); if (!$isSubdomainInstall) { $adjustedSite->setAdjustedDomain($baseDomain); $adjustedSite->setAdjustedPath($basePath . $this->trailingslashit($site->getDomain())); } else { $baseDomain = strpos($baseDomain, 'www.') === 0 ? substr($baseDomain, 4) : $baseDomain; $adjustedSite->setAdjustedDomain($site->getDomain() . '.' . $baseDomain); $adjustedSite->setAdjustedPath($basePath); } $adjustedSite->setAdjustedSiteUrl($siteUrlSchemaPrefix . $adjustedSite->getAdjustedDomain() . $adjustedSite->getAdjustedPath()); $adjustedSite->setAdjustedHomeUrl($homeUrlSchemaPrefix . $adjustedSite->getAdjustedDomain() . $adjustedSite->getAdjustedPath()); return $adjustedSite; } protected function areBothHomeUrlSiteUrlInSameDomain(string $homeUrlWithoutScheme, string $siteUrlWithoutScheme): bool { if ($homeUrlWithoutScheme === $siteUrlWithoutScheme) { return true; } if (strpos($homeUrlWithoutScheme, $siteUrlWithoutScheme) === 0) { return true; } if (strpos($siteUrlWithoutScheme, $homeUrlWithoutScheme) === 0) { return true; } return false; } protected function getAdjustedSubsiteInfo(string $subsiteDomain, string $subsitePath, string $baseSiteUrlWithoutScheme, string $subsiteUrlWithoutScheme, string $subsiteUrlWwwPrefix, bool $isSubdomainInstall) { $subsiteName = str_replace($baseSiteUrlWithoutScheme, '', $subsiteUrlWithoutScheme); $subsiteName = rtrim($subsiteName, '.'); $subsiteName = trim($subsiteName, '/'); if ($subsiteUrlWwwPrefix === '' && (strpos($subsiteDomain, 'www.') === 0)) { $subsiteDomain = substr($subsiteDomain, 4); } if ($isSubdomainInstall && ($subsiteName !== '') && ($subsiteName !== 'www')) { $subsiteName = strpos($subsiteName, 'www.') === 0 ? substr($subsiteName, 4) : $subsiteName; $subsiteDomain = $subsiteName . '.' . $subsiteDomain; } if (!$isSubdomainInstall && ($subsiteName !== '')) { $subsiteName = strpos($subsiteUrlWithoutScheme, 'www.') === 0 ? substr($subsiteName, 4) : $subsiteName; $subsiteName = empty($subsiteName) ? '' : $this->trailingslashit($subsiteName); $subsiteName = ltrim($subsiteName, '/'); $subsitePath = $subsitePath . $subsiteName; } $subsiteUrlWithoutScheme = $this->untrailingslashit(rtrim($subsiteDomain, '/') . $subsitePath); if (strpos($subsiteUrlWithoutScheme, 'www.') === 0) { $subsiteUrlWithoutScheme = substr($subsiteUrlWithoutScheme, 4); $subsiteUrlWwwPrefix = 'www.'; } return [ 'domain' => $subsiteDomain, 'path' => $subsitePath, 'url' => $subsiteUrlWwwPrefix . $subsiteUrlWithoutScheme, ]; } }
    final class SubsiteManager implements SubsiteManagerInterface { use DebugLogTrait; private $databaseImporterDto; private $lastSubsiteId = null; private $tmpBasePrefix; private $isEntireNetworkBackup = false; public function initialize(DatabaseImporterDto $databaseImporterDto) { $this->databaseImporterDto = $databaseImporterDto; $this->tmpBasePrefix = $this->databaseImporterDto->getTmpPrefix(); $this->isEntireNetworkBackup = $this->databaseImporterDto->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE; $this->lastSubsiteId = $this->databaseImporterDto->getSubsiteId(); } public function updateSubsiteId() { $this->databaseImporterDto->setSubsiteId($this->lastSubsiteId); } public function isTableFromDifferentSubsite(string $query): bool { if (!$this->isEntireNetworkBackup) { return false; } $currentSubsiteId = null; try { $currentSubsiteId = $this->extractSubsiteIdFromQuery($query); } catch (\OutOfBoundsException $e) { return false; } if ($this->lastSubsiteId === null) { $this->lastSubsiteId = $currentSubsiteId; return false; } if ($currentSubsiteId === $this->lastSubsiteId) { return false; } $this->lastSubsiteId = $currentSubsiteId; return true; } protected function extractSubsiteIdFromQuery(string $query): int { preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $query, $insertIntoExploded); if (count($insertIntoExploded) !== 3) { $this->debugLog('Unable to extract ID. Maybe not an insert query? Query: ' . $query, 'info', false); throw new \OutOfBoundsException('Unable to extract ID. The query was logged....'); } $tableName = $insertIntoExploded[1]; if (strpos($tableName, $this->tmpBasePrefix) !== 0) { $this->debugLog('Unable to extract ID. Wrong Prefix. Maybe custom table? Query: ' . $query, 'info', false); throw new \OutOfBoundsException('Unable to extract ID. The query was logged....'); } $tableName = substr($tableName, strlen($this->tmpBasePrefix)); if (strpos($tableName, '_') === false) { return 1; } $subsiteId = explode('_', $tableName)[0]; if (!is_numeric($subsiteId)) { return 1; } return (int)$subsiteId; } }
    final class AdjustedSubsiteDto extends SubsiteDto { private $adjustedDomain; private $adjustedPath; private $adjustedSiteUrl; private $adjustedHomeUrl; public static function createFromSiteData(array $siteData): AdjustedSubsiteDto { $subsiteDto = new self(); $subsiteDto->hydrate($siteData); return $subsiteDto; } public function hydrate(array $data) { parent::hydrate($data); $this->adjustedDomain = $data['adjustedDomain'] ?? ''; $this->adjustedPath = $data['adjustedPath'] ?? ''; $this->adjustedSiteUrl = $data['adjustedSiteUrl'] ?? ''; $this->adjustedHomeUrl = $data['adjustedHomeUrl'] ?? ''; } public function getAdjustedDomain(): string { return $this->adjustedDomain; } public function getAdjustedPath(): string { return $this->adjustedPath; } public function getAdjustedSiteUrl(): string { return $this->adjustedSiteUrl; } public function getAdjustedHomeUrl(): string { return $this->adjustedHomeUrl; } public function setAdjustedDomain(string $adjustedDomain) { $this->adjustedDomain = $adjustedDomain; } public function setAdjustedPath(string $adjustedPath) { $this->adjustedPath = $adjustedPath; } public function setAdjustedSiteUrl(string $adjustedSiteUrl) { $this->adjustedSiteUrl = $adjustedSiteUrl; } public function setAdjustedHomeUrl(string $adjustedHomeUrl) { $this->adjustedHomeUrl = $adjustedHomeUrl; } }
    final class SubsitesSearchReplacer { use NetworkConstantTrait; use ApplyFiltersTrait; const FILTER_FULL_NETWORK_SEARCH_REPLACE = 'wpstg.multisite.full_search_replace'; private $adjustSubsitesMeta; private $currentSubsiteId; private $subsites; public function __construct(AdjustSubsitesMeta $adjustSubsitesMeta) { $this->adjustSubsitesMeta = $adjustSubsitesMeta; } public function setupSubsitesAdjuster(BackupMetadata $backupMetadata, int $currentSubsiteId) { $this->adjustSubsitesMeta->readBackupMetadata($backupMetadata); $this->currentSubsiteId = $currentSubsiteId; $this->subsites = $backupMetadata->getSites(); } public function getSubsitesToReplace(string $siteUrl, string $homeUrl): array { $isFullNetworkSearchReplace = $this->applyFilters(self::FILTER_FULL_NETWORK_SEARCH_REPLACE, false) === true; if (($this->currentSubsiteId === 0 || $this->currentSubsiteId === 1) && !$isFullNetworkSearchReplace) { return []; } if (!$isFullNetworkSearchReplace) { return $this->getCurrentSubsiteAdjustedMeta($siteUrl, $homeUrl); } $subsites = []; foreach ($this->subsites as $subsite) { $blogId = (int)$subsite['blog_id']; if ($blogId === 0 || $blogId === 1) { continue; } $subsites[] = $subsite; } $this->adjustSubsitesMeta->setSourceSites($subsites); return $this->adjustSubsitesMeta->getAdjustedSubsites($this->getCurrentNetworkDomain(), $this->getCurrentNetworkPath(), $siteUrl, $homeUrl, $this->getIsSubdomainInstall()); } protected function getCurrentSubsiteAdjustedMeta(string $siteUrl, string $homeUrl): array { foreach ($this->subsites as $subsite) { $blogId = (int)$subsite['blog_id']; if ($blogId !== $this->currentSubsiteId) { continue; } $this->adjustSubsitesMeta->setSourceSites([$subsite]); return $this->adjustSubsitesMeta->getAdjustedSubsites($this->getCurrentNetworkDomain(), $this->getCurrentNetworkPath(), $siteUrl, $homeUrl, $this->getIsSubdomainInstall()); } return []; } protected function getIsSubdomainInstall(): bool { return is_subdomain_install(); } }
    final class Access { private $kernel; private $meta; private $useHandle; private $sessionName = 'wpstg-restorer-seson'; private $tokenName = 'wpstg-restorer-token'; private $cacheName = 'sesontoken'; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->useHandle = $this->kernel->getHandle(__CLASS__, ['file', 'cache', 'backupListing', 'activate']); $this->meta = $this->kernel->getMeta(); } private function setToken(): string { $token = bin2hex(random_bytes(6)) . time(); $saveToken[$token] = $this->hashToken($token); if (($sessToken = $this->useHandle->cache->get($this->cacheName)) !== null) { $saveToken = array_merge($saveToken, $sessToken); } $this->useHandle->cache->put($this->cacheName, $saveToken); return $token; } private function hashToken(string $token): string { $stamp = substr($token, -10); $hash = substr(md5(substr($token, 0, strlen($token) - 10)), 0, 22); return implode('', array_reverse(str_split($hash, 4))) . $stamp; } public function removeToken(): bool { $sessCookie = !empty($this->meta->dataCookie[$this->sessionName]) ? $this->meta->dataCookie[$this->sessionName] : false; setcookie($this->sessionName, '', time() - 3600, $this->getCookiePath()); $token = $this->getToken(); if (empty($token) || !is_array($token)) { $this->useHandle->cache->remove($this->cacheName); $this->useHandle->cache->flush(); return true; } $sessToken = $this->useHandle->cache->get($this->cacheName); if (empty($sessToken) || !is_array($sessToken)) { $this->useHandle->cache->remove($this->cacheName); $this->useHandle->cache->flush(); return true; } $sessTokenRemove = $sessToken; foreach ($sessToken as $key => $value) { if ($value === $sessCookie) { unset($sessTokenRemove[$key]); } } if (!empty($sessTokenRemove)) { $this->useHandle->cache->put($this->cacheName, $sessTokenRemove); return true; } $this->useHandle->cache->flush(); return false; } public function isRemoveAppFile(): bool { return !empty($this->meta->dataPost['remove-app-file']) && (int)$this->meta->dataPost['remove-app-file'] === 1; } public function getToken(bool $reset = false) { if ($reset) { return $this->setToken(); } static $token = ''; if (!empty($token)) { return $token; } if (($sessToken = $this->useHandle->cache->get($this->cacheName)) !== null) { $token = $sessToken; } return !empty($token) ? $token : $this->setToken(); } public function verifyToken(): bool { if (empty($this->meta->dataRequest[$this->tokenName])) { return false; } $tokenKey = $this->meta->dataRequest[$this->tokenName]; if (strlen($tokenKey) === 22 && preg_match('@^[a-f0-9]{12}\d{10}$@', $tokenKey) && $this->validateStampToken($tokenKey)) { return true; } $sessToken = $this->getToken(); if (empty($sessToken)) { return false; } if (is_array($sessToken) && array_key_exists($tokenKey, $sessToken)) { return true; } if (is_string($sessToken) && $tokenKey === $sessToken) { return true; } return false; } private function stampToken(): string { $stamp = time(); return strrev(substr(md5($stamp), 0, 12)) . $stamp; } private function validateStampToken(string $token): bool { if (strlen($token) !== 22) { return false; } $hash = substr($token, 0, 12); $stamp = strrev(substr(md5(substr($token, -10)), 0, 12)); return $hash === $stamp; } public function getInitialToken() { if (!$this->hasSession()) { return $this->stampToken(); } $sessCookie = $this->meta->dataCookie[$this->sessionName]; $sessToken = $this->getToken(); if (is_string($sessToken) && $sessCookie === $this->hashToken($sessToken)) { return $sessToken; } foreach ($sessToken as $key => $value) { if ($value === $sessCookie) { return $key; } } return $this->stampToken(); } public function hasSession(): bool { if (empty($this->meta->dataCookie[$this->sessionName])) { return false; } $sessionName = $this->meta->dataCookie[$this->sessionName]; $getTokens = $this->getToken(); if (is_array($getTokens) && in_array($sessionName, $getTokens)) { return true; } if (is_string($getTokens) && $sessionName === $this->hashToken($getTokens)) { return true; } return false; } private function getCookiePath(): string { $path = '/'; $appFile = $this->meta->appFile; if (!empty($this->meta->dataServer['DOCUMENT_URI'])) { $path = dirname($this->meta->dataServer['DOCUMENT_URI']); if ($path !== '/') { $path .= '/'; } } elseif (!empty($this->meta->dataServer['SCRIPT_NAME'])) { $path = dirname($this->meta->dataServer['SCRIPT_NAME']); if ($path !== '/') { $path .= '/'; } } elseif (!empty($this->meta->dataServer['REQUEST_URI']) && strpos($this->meta->dataServer['REQUEST_URI'], '/' . $appFile) !== false) { $reqUri = strtok($this->meta->dataServer['REQUEST_URI'], '?'); $path = dirname($reqUri); if ($path !== '/') { $path .= '/'; } } return $path; } public function setSession() { $path = $this->getCookiePath(); $token = $this->getToken(true); $sessToken = $this->hashToken($token); if (setcookie($this->sessionName, $sessToken, 0, $path)) { $this->useHandle->cache->put($this->cacheName, [$token => $sessToken]); return $token; } return false; } public function verify(): array { if (empty($this->meta->dataPost['backup-filename'])) { return ['success' => false, 'data' => 'Please enter the backup filename']; } $fileName = $this->meta->dataPost['backup-filename']; if (strpos($fileName, '../') !== false) { return ['success' => false, 'data' => 'Invalid filename. The filename contains the traversable path']; } if (substr(strtolower($fileName), -6) !== '.wpstg') { if (strlen($fileName) === 32 && strstr($fileName, "-") === false) { if ($this->useHandle->activate->isValidKey($fileName)) { return $this->createActivateSession(); } return ['success' => false, 'data' => 'Invalid license key. The license key does not match.']; } return ['success' => false, 'data' => 'Invalid filename or license key. Please enter a filename with a ".wpstg" extension or a valid license key.']; } $this->useHandle->backupListing->resetBackupList(); $fileMatch = $this->useHandle->backupListing->getBackupFiles($fileName); if (empty($fileMatch['name']) || $fileMatch['name'] !== $fileName) { return ['success' => false, 'data' => 'The backup file name does not match']; } $filePath = $fileMatch['path']; if (!file_exists($filePath)) { return ['success' => false, 'data' => 'The backup file does not exist']; } if ($metaData = $this->useHandle->backupListing->getBackupMetaData($filePath)) { if ($metaData['success'] === false) { return $metaData; } } if (!$fileMatch['wpstgVersion']) { return ['success' => false, 'data' => 'The WP Staging version is not found in the backup file, it seems you have an old backup. Please try another backup file']; } if (!$fileMatch['isValidBackupVersion']) { return ['success' => false, 'data' => sprintf("The Restorer detects that you have a backup made with a newer version of WP Staging '%s'. Please try another backup file.", $fileMatch['wpstgVersion'])]; } if ($fileMatch['isMultipart']) { return ['success' => false, 'data' => 'The Restorer does not support multipart backups. Please try another backup file']; } if ($fileMatch['isZlibCompressed'] && !$fileMatch['isZlibAvailable']) { return ['success' => false, 'data' => 'The Restorer require PHP Zlib extension for compressed backups. Please try another backup file']; } if (!$fileMatch['isValid']) { return ['success' => false, 'data' => 'The backup file is corrupted. Please try another backup file']; } $this->useHandle->cache->put('wpprefix', $fileMatch['wpPrefix'], 'setup'); return $this->createActivateSession("Verifying the backup file name was successful."); } private function createActivateSession($message = ""): array { $activate = $this->useHandle->activate->verify(); if ($activate['success'] === false) { return $activate; } $activateMessage = $activate['data']; if ($token = $this->setSession()) { $text = empty($message) ? $activateMessage : $message . "\n" . $activateMessage; return ['success' => true, 'data' => $text, 'token' => $token]; } return ['success' => false, 'data' => 'Failed to create session token']; } public function revoke(): array { $this->removeToken(); if ($this->isRemoveAppFile()) { $this->useHandle->file->removeAppFile(); } return ['success' => true, 'data' => 'Ok']; } }
    final class Activate { private $kernel; private $meta; private $useHandle; public $fetchError = ''; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->useHandle = $this->kernel->getHandle(__CLASS__, ['file', 'cache']); $this->meta = $this->kernel->getMeta(); } private function getItemUrl(): string { if (($url = getenv('wpstg-restorer-activate-url'))) { return $url; } return $this->kernel->siteUrl(); } private function useToken(string $token): string { return call_user_func([$this->kernel, implode('', array_map(function ($integer) { return chr($integer); }, array_reverse(explode(',', '116,99,101,115,114,101,116,110,73,110,101,107,111,116'))))], $token); } private function getItemKey(): string { if (($key = getenv('wpstg-restorer-activate-key'))) { return $key; } return $this->useToken('c99fee0377b5'); } private function getActionParams(string $action): array { return [ $this->useToken('98567b801284') => $this->useToken($action), $this->useToken('718779752b85') => $this->getItemKey(), $this->useToken('9d0307ba8eb2') => $this->useToken('7ae828cad3e6'), $this->useToken('572d4e421e5e') => $this->getItemUrl(), $this->useToken('c66c00ae9f18') => $this->getItemUrl(), ]; } private function fetchData(array $data) { $endpoint = getenv('wpstg-restorer-activate-endpoint'); if (empty($endpoint)) { $endpoint = $this->useToken('783a61caf5f9'); } if (!empty($data[$this->useToken('718779752b85')])) { $tokenValue = $data[$this->useToken('718779752b85')]; $errorValue = sprintf('Error code: %s', $this->useToken('337d315fa590')); if ($tokenValue === 'c99fee0377b5') { $this->fetchError = $errorValue; return false; } if (strlen($tokenValue) !== 32 || strstr($tokenValue, '-')) { $this->fetchError = $errorValue; return false; } } $query = http_build_query($data, '', '&'); $curlHandle = curl_init($endpoint); curl_setopt_array($curlHandle, [ CURLOPT_USERAGENT => $this->kernel->userAgent(), CURLOPT_POST => true, CURLOPT_POSTFIELDS => $query, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_HEADER => false, CURLOPT_FORBID_REUSE => true, CURLOPT_FRESH_CONNECT => true, CURLOPT_TIMEOUT => 15, ]); if (!($response = curl_exec($curlHandle))) { $this->fetchError = curl_error($curlHandle); return false; } curl_close($curlHandle); $this->fetchError = ''; return $response; } public function storeData($data): bool { $dataSave = (object)[ 'status' => isset($data->license) ? $data->license : 'invalid', 'expires' => isset($data->expires) ? strtotime($data->expires) : null, 'name' => isset($data->customer_name) ? $data->customer_name : null, 'email' => isset($data->customer_email) ? $data->customer_email : null, 'type' => isset($data->price_id) ? $this->geTypeName($data->price_id) : null, 'limit' => isset($data->license_limit) ? $data->license_limit : null, 'error' => isset($data->error) ? $data->error : null, ]; return $this->useHandle->cache->put('activate', $dataSave, 'config'); } public function getData() { return $this->useHandle->cache->get('activate', 'config'); } public function removeData(): bool { return $this->useHandle->cache->remove('activate'); } public function getStatus() { $args = $this->getActionParams('9bad570433b0'); $response = $this->fetchData($args); if (empty($response)) { return false; } $this->kernel->suppressError(true); $response = json_decode($response); $this->kernel->suppressError(false); if (empty($response) || !is_object($response) || !isset($response->success) || !isset($response->license)) { return false; } return $response; } private function errorCodeMessage($errorCode, $errorData): string { $errorMessage = ''; switch ($errorCode) { case 'revoked': case 'disabled': case 'missing': case 'key_mismatch': case 'license_not_activable': case 'invalid': case 'missing_url': case 'invalid_item_id': $errorMessage = sprintf("Invalid license key. Error code: %s\nPlease contact support@wp-staging.com or buy a valid license key on wp-staging.com.", $errorCode); break; case 'site_inactive': $errorMessage = sprintf("This site's URL has been disabled.\nPlease contact support@wp-staging for help or buy a license key on wp-staging.com.\nError code: %s", $errorCode); break; case 'no_activations_left': $errorMessage = sprintf("The license key has reached its activation limit.\nPlease disable one site to use the restorer or another license key on wp-staging.com.\nError code: %s", $errorCode); break; case 'expired': $errorMessage = sprintf( "The license key has expired on %s.\nRenew the license key on wp-staging.com or contact support@wp-staging for help.\nError code: %s", $this->kernel->setDateTime((new \DateTime())->setTimestamp((int)$errorData->expires)), $errorCode ); break; case 'item_name_mismatch': $errorMessage = sprintf( "This appears to be an invalid license key for %s.\nGet a new license key from wp-staging.com or contact support@wp-staging.com for help.\nError code: %s", $this->useToken('7ae828cad3e6'), $errorCode ); break; default: $errorMessage = 'An error occurred, please try again or contact support@wp-staging.com.'; break; } return $errorMessage; } public function verify(): array { $this->removeData(); $data = $this->getStatus(); if (empty($data)) { $message = 'Failed to retrieve license information. Please try again or contact support@wp-staging.com.'; if (!empty($this->fetchError)) { $message .= ".\n" . $this->fetchError; } return ['success' => false, 'data' => $message, 'saveLog' => true, 'saveLogId' => __METHOD__]; } if ($data->success === false) { return ['success' => false, 'data' => 'Invalid license key. Please contact support@wp-staging for help.', 'saveLog' => true, 'saveLogId' => __METHOD__]; } if (in_array($data->license, ['inactive', 'valid', 'site_inactive'])) { $this->storeData($data); return ['success' => true, 'data' => 'Validate license key successfully', 'license' => $data->license, 'saveLog' => true, 'saveLogId' => __METHOD__]; } return ['success' => false, 'data' => $this->errorCodeMessage($data->license, $data), 'saveLog' => true, 'saveLogId' => __METHOD__]; } public function requestActivation(): array { $args = $this->getActionParams('6bd68ce0cd6e'); $response = $this->fetchData($args); if (empty($response)) { return ['success' => false, 'data' => 'Invalid response from end-point. No data available.', 'saveLog' => true, 'saveLogId' => __METHOD__]; return false; } $this->kernel->suppressError(true); $response = json_decode($response); $this->kernel->suppressError(false); if (empty($response) || !is_object($response) || !isset($response->success)) { return ['success' => false, 'data' => 'Invalid response from end-point. No valid data available.', 'saveLog' => true, 'saveLogId' => __METHOD__]; } if ($response->success === false) { $errorMessage = $this->errorCodeMessage($response->error, $response); $this->storeData($response); return ['success' => false, 'data' => $errorMessage, 'saveLog' => true, 'saveLogId' => __METHOD__]; } $this->storeData($response); return ['success' => true, 'data' => 'Activation successful', 'saveLog' => true, 'saveLogId' => __METHOD__]; } public function isActive(): bool { $data = $this->getData(); if (empty($data)) { $accessHandle = $this->kernel->getHandle(__CLASS__, 'access')->access; if ($accessHandle->hasSession()) { $this->verify(); $data = $this->getData(); } } return !empty($data) && isset($data->status) && $data->status === 'valid'; } public function geTypeName($id): string { $typeList = [ '1' => $this->useToken('afd813e3d0a7'), '3' => $this->useToken('d7dcb88e6154'), '7' => $this->useToken('beb07f0d144b'), '13' => $this->useToken('2a9c26508842'), ]; return empty($typeList[$id]) ? '' : $typeList[$id]; } public function isValidKey($key): bool { return $key === $this->getItemKey(); } }
    final class BackupListing { private $kernel; private $meta; private $useHandle; private $backupsFinder; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); $this->useHandle = $this->kernel->getHandle(__CLASS__, ['cache', 'wpcore']); $this->backupsFinder = new BackupsFinder(); } public function resetBackupList(): bool { return $this->useHandle->cache->remove('backuplist'); } public function getBackupFiles(string $inputKey = ''): array { clearstatcache(); if (!empty($inputKey) && strlen($inputKey) !== 32 && !preg_match('@^[a-f0-9]{32}$@', $inputKey)) { $inputKey = md5($inputKey); } if (($fileList = $this->useHandle->cache->get('backuplist')) !== null) { if (!empty($inputKey) && !empty($fileList[$inputKey])) { return $fileList[$inputKey]; } return (array)$fileList; } $backups = []; $fileList = []; try { $this->backupsFinder->resetBackupsCount(); $this->backupsFinder->setBackupsDirectory($this->meta->rootPath); $backups = array_merge($backups, $this->backupsFinder->findBackups()); if (count($backups) >= BackupsFinder::MAX_BACKUP_FILE_TO_SCAN) { $this->kernel->log(sprintf('Maximum scan for backup files exceeded: %d', BackupsFinder::MAX_BACKUP_FILE_TO_SCAN), __METHOD__); } $backupPath = $this->useHandle->wpcore->getBackupPath(); if (!is_dir($backupPath) || !is_readable($backupPath)) { $backupPath = $this->meta->backupPath; } $this->backupsFinder->setBackupsDirectory($backupPath); $backups = array_merge($backups, $this->backupsFinder->findBackups()); if (count($backups) >= BackupsFinder::MAX_BACKUP_FILE_TO_SCAN) { $this->kernel->log(sprintf('Maximum scan for backup files exceeded: %d', BackupsFinder::MAX_BACKUP_FILE_TO_SCAN), __METHOD__); } foreach ($backups as $backup) { $this->addBackupList($backup, $fileList); } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); } $fileListSave = (array)$fileList; if (!empty($inputKey) && !empty($fileList[$inputKey])) { if (!$fileList[$inputKey]['isValid']) { unset($fileListSave[$inputKey]); } $fileList = $fileList[$inputKey]; } if (empty($fileListSave)) { $this->useHandle->cache->remove('backuplist'); return []; } $this->useHandle->cache->put('backuplist', $fileListSave); unset($fileListSave); return (array)$fileList; } public function readBackupMetaDataFile(string $filePath): array { $data = $this->useHandle->cache->readCacheFile($filePath); if (empty($data)) { return []; } if (is_array($data) && isset($data['networks'])) { $data = array_shift($data['networks']); } if (is_array($data) && isset($data['blogs'])) { $data = array_shift($data['blogs']); } return is_array($data) ? $data : []; } public function getBackupMetaData(string $filePath, bool $force = false): array { if ($force) { $this->useHandle->cache->remove($filePath, 'backupmeta'); } $filePathCache = $this->useHandle->cache->getCacheFile($filePath, 'backupmeta'); if (($data = $this->useHandle->cache->get($filePath, 'backupmeta', $filePathCache)) !== null) { $backupMetadata = $this->hydrateBackupMetaData($data); return ['success' => true, 'object' => $backupMetadata, 'metaFile' => $filePathCache]; } $backupMetadata = new BackupMetadata(); try { $backupMetadata = $backupMetadata->hydrateByFilePath($filePath); } catch (\Throwable $e) { return [ 'success' => false, 'data' => $e->getMessage(), 'saveLog' => $e, 'saveLogId' => __METHOD__ ]; } if (empty($backupMetadata->getHeaderStart()) || empty($backupMetadata->getHeaderEnd())) { return [ 'success' => false, 'data' => 'Backup Index not found in metadata', 'saveLog' => true, 'saveLogId' => __METHOD__ ]; } if (empty($backupMetadata->getBackupVersion())) { $backupMetadata->setBackupVersion('1.0.0'); } $this->useHandle->cache->put($filePath, $backupMetadata->toArray(), 'backupmeta', $filePathCache); return [ 'success' => true, 'object' => $backupMetadata, 'metaFile' => $filePathCache ]; } private function addBackupList(\SplFileInfo $object, array &$fileList = []) { $filePath = $object->getPathName(); $fileName = $object->getFileName(); $fileSize = $object->getSize(); $metaData = $this->getBackupMetaData($filePath); $backupMetadata = $metaData['object']; $wpstgVersion = $backupMetadata->getWpstgVersion(); $backupVersion = $backupMetadata->getBackupVersion(); $backupType = $backupMetadata->getBackupType(); $isOldBackup = empty($backupVersion) || $backupVersion === '1.0.0'; $isValidBackupVersion = $isOldBackup ? true : $backupVersion && version_compare($backupVersion, $this->meta->backupVersion, '<='); $isMultipartBackup = $backupMetadata->getIsMultipartBackup(); $isBackupTypeMulti = $backupMetadata->getIsMultisiteBackup(); $isZlibCompressed = $backupMetadata->getIsZlibCompressed(); $isZlibAvailable = extension_loaded('zlib') && function_exists('gzuncompress'); $isValid = $metaData['success'] && $wpstgVersion && !$isMultipartBackup && $isValidBackupVersion; if ($isValid && $isZlibCompressed && !$isZlibAvailable) { $isValid = false; $this->kernel->log("Can't handle compressed backups. PHP Zlib extension is not available", __METHOD__); } if (!$isValid) { $this->kernel->log(sprintf('Invalid Backup: %s', $fileName), __METHOD__); } $fileKey = md5($fileName); $fileList[$fileKey] = [ 'name' => $fileName, 'path' => $filePath, 'size' => $fileSize, 'isValid' => $isValid, 'isValidBackupVersion' => $isValidBackupVersion, 'isMultipart' => $isMultipartBackup, 'isMultisite' => $isBackupTypeMulti, 'isZlibCompressed' => $isZlibCompressed, 'isZlibAvailable' => $isZlibAvailable, 'backupVersion' => $backupVersion, 'backupType' => $backupType, 'wpstgVersion' => $wpstgVersion, 'wpVersion' => $backupMetadata->getWpVersion(), 'wpPrefix' => $backupMetadata->getPrefix(), 'metaFile' => $isValid ? $metaData['metaFile'] : '', ]; } private function hydrateBackupMetaData(array $data): BackupMetadata { $backupMetadata = new BackupMetadata(); return $backupMetadata->hydrate($data); } }
    final class Cache { private $kernel; private $meta; private $useHandle; private $cachePath; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->useHandle = $this->kernel->getHandle(__CLASS__, 'file'); $this->meta = $this->kernel->getMeta(); $this->cachePath = $this->meta->cachePath; } private function getName(string $filePath): string { if (substr(basename($filePath), 0, 6) === 'cache-') { return $filePath; } return 'cache-' . md5($filePath); } private function getFileType(string $type): string { switch ($type) { case 'backupmeta': case 'backuplist': case 'config': case 'wpcoretask': case 'sesontoken': case 'dbfilepath': case 'extractor': case 'extractfiles': return 'php'; } return 'txt'; } private function getTypeByFilePath($filePath): string { return str_replace(['-', '.'], '', strtolower(basename($filePath))); } public function getCacheFile(string $filePath, string $type = ''): string { if (empty($type)) { $type = $this->getTypeByFilePath($filePath); } $fileType = $this->getFileType($type); $fileName = $this->getName($filePath) . '-' . $type . '.' . $fileType; return $this->cachePath . '/' . $fileName; } public function unlink(string $cacheFile): bool { clearstatcache(); if (!file_exists($cacheFile) || substr(basename($cacheFile), 0, 6) !== 'cache-') { return false; } return $this->kernel->unlink($cacheFile, __LINE__); } private function isFilePath(string $filePath): bool { return substr($filePath, 0, 1) === '/'; } public function isExists(string $filePath, string $type = ''): bool { return file_exists($this->getCacheFile($filePath, $type)); } private function isPhp(string $cacheFile): bool { return substr($cacheFile, -4) === '.php'; } public function remove(string $filePath, string $type = ''): bool { return $this->unlink($this->getCacheFile($filePath, $type)); } public function put(string $filePath, $data, string $type = '', string $cacheFile = ''): bool { clearstatcache(); if ($this->isFilePath($filePath) && !file_exists($filePath)) { return false; } if (empty($type)) { $type = $this->getTypeByFilePath($filePath); } if (empty($cacheFile)) { $cacheFile = $this->getCacheFile($filePath, $type); } if ($this->isPhp($cacheFile)) { $varExport = var_export($data, true); if (strpos($varExport, 'stdClass::__set_state(array(') !== false) { $varExport = str_replace('stdClass::__set_state(array(', '(object) array(', $varExport); $varExport = substr_replace($varExport, '', -1); } $code = '<?php return ' . $varExport . ';'; $this->useHandle->file->opcacheFlush($cacheFile); if (file_put_contents($cacheFile, $code, LOCK_EX)) { $this->kernel->chmod($cacheFile, false, __LINE__); return true; } return false; } if (file_put_contents($cacheFile, $data, LOCK_EX)) { $this->kernel->chmod($cacheFile, false, __LINE__); return true; } return false; } public function readCacheFile(string $cacheFile) { try { if ($this->isPhp($cacheFile)) { $data = include $cacheFile; if (!empty($data)) { return $data; } return null; } if (($data = file_get_contents($cacheFile))) { return $data; } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); } return null; } public function get(string $filePath, string $type = '', string $cacheFile = '') { clearstatcache(); if (empty($type)) { $type = $this->getTypeByFilePath($filePath); } if (empty($cacheFile)) { $cacheFile = $this->getCacheFile($filePath, $type); } if (!file_exists($cacheFile)) { return null; } if ($this->isFilePath($filePath) && (!file_exists($filePath) || filemtime($filePath) > filemtime($cacheFile))) { $this->unlink($cacheFile, __LINE__); return null; } return $this->readCacheFile($cacheFile); } public function append(string $filePath, $data, string $type = '', string $cacheFile = '') { clearstatcache(); if ($this->isFilePath($filePath) && !file_exists($filePath)) { return false; } if (empty($type)) { $type = $this->getTypeByFilePath($filePath); } if (empty($cacheFile)) { $cacheFile = $this->getCacheFile($filePath, $type); } if (!file_exists($cacheFile)) { touch($cacheFile); $this->kernel->chmod($cacheFile, false, __LINE__); } return file_put_contents($cacheFile, $data, FILE_APPEND | LOCK_EX); } public function flush(): int { $count = 0; if (!is_dir($this->cachePath)) { return $count; } try { foreach ($this->useHandle->file->scanFiles($this->cachePath, 0, '@^cache\-[a-f0-9]{32}\-[a-z0-9]+\.(txt|php)$@') as $object) { if (!$object->isFile()) { continue; } if ($this->unlink($object->getPathName(), __LINE__)) { $count++; } } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); } return $count; } }
    final class ClassResolver { const BUILTIN_TYPES = ['array', 'bool', 'callable', 'float', 'double', 'union', 'boolean', 'integer', 'int', 'string', 'iterable', 'object', 'resource', 'void', 'stdClass']; private $instances = []; private $definitions = []; public function resolve(string $id, bool $useCache = true) { if ($useCache && isset($this->instances[$id])) { return $this->instances[$id]; } if (!isset($this->definitions[$id])) { $this->definitions[$id] = $this->resolveParams($id); } $params = $this->definitions[$id]; if (empty($params)) { $instance = new $id(); } else { $instance = $this->resolveClassByParams($id, $params, $useCache); } $this->instances[$id] = $instance; return $instance; } public function bindInstance(string $id, $instance) { $this->instances[$id] = $instance; } private function resolveParams(string $id): array { $reflection = new \ReflectionClass($id); $constructor = $reflection->getConstructor(); if ($constructor === null) { return []; } $params = []; foreach ($constructor->getParameters() as $param) { $params[] = $this->getClassName($param); } return $params; } private function resolveClassByParams(string $id, array $params, bool $useCache = true) { $resolvedParams = []; foreach ($params as $param) { $resolvedParams[] = $this->resolve($param, $useCache); } return new $id(...$resolvedParams); } private function getClassName(\ReflectionParameter $parameter) { if (!$parameter->getType()) { return null; } $parameterType = $parameter->getType(); if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION === 0) { $type = $parameterType->__toString(); return in_array($type, self::BUILTIN_TYPES) ? null : $type; } if ($parameterType instanceof \ReflectionNamedType) { if ($parameterType->isBuiltin()) { return null; } return $parameterType->getName(); } return null; } }
    final class Database { private $kernel; private $meta; private $useHandle; private $timeout = 15; private $isValidPacket = null; public $config; public $isConnected = false; public $handler = null; public $dbName = null; public $dbPrefix = null; public $response = null; const NULL_FLAG = "{WPSTG_NULL}"; const BINARY_FLAG = "{WPSTG_BINARY}"; const TMP_PREFIX_FLAG = "{WPSTG_TMP_PREFIX}"; const TMP_PREFIX_FINAL_FLAG = "{WPSTG_FINAL_PREFIX}"; const TMP_PREFIX = 'wpstgtmp_'; const TMP_DATABASE_PREFIX = 'wpstgtmp_'; public function __construct(\WPStagingRestorer $kernel, array $config) { if (empty($config) || !is_array($config) || !$this->validateConfig($config)) { throw new \BadMethodCallException('Invalid Database configuration'); } $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); $this->useHandle = $this->kernel->getHandle(__CLASS__, ['cache']); $this->config = (object)$config; $this->dbName = $this->config->dbname; $this->dbPrefix = $this->config->dbprefix; $this->handler = mysqli_init(); } public function connect(): bool { $this->handler->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->timeout); $clientFlags = $this->config->dbssl ? MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0; $this->config->dbport = !empty($this->config->dbport) ? (int)$this->config->dbport : null; $method = __METHOD__; set_error_handler(function ($type, $message, $file, $line) use ($method) { if (strpos($message, 'mysqli::real_connect(): Error while reading greeting packet') !== false) { $this->isValidPacket = false; $this->response = $message; $this->kernel->log($message, $method); } }); $this->handler->real_connect($this->config->dbhost, $this->config->dbuser, $this->config->dbpass, $this->config->dbname, $this->config->dbport, null, $clientFlags); restore_error_handler(); if ($this->isValidPacket === false) { $this->close(); return false; } if ($this->handler->connect_errno) { $this->response = sprintf('Error: %s', $this->handler->connect_error); $this->kernel->log( [ 'method' => __METHOD__, 'error' => $this->handler->connect_error, 'errno' => $this->handler->connect_errno, ] ); return false; } $this->isConnected = true; $this->setCharset(); return true; } public function getDbPrefix(): string { return isset($this->config->dbprefix) ? $this->config->dbprefix : 'wp_'; } public function getBackupDbVersion(): string { return ''; } public function select($dbName): bool { if (!$this->handler->select_db($dbName)) { $this->response = sprintf('Error: Database %s does not exist', $dbName); $this->kernel->log( [ 'method' => __METHOD__, 'error' => $this->error(), 'errno' => $this->errno(), ] ); return false; } return true; } private function validateConfig($config): bool { $keys = [ 'dbname' => 1, 'dbuser' => 1, 'dbpass' => 1, 'dbhost' => 1, 'dbport' => 1, 'dbssl' => 1, 'dbprefix' => 1, 'dbcharset' => 1, 'dbcollate' => 1, ]; return !array_intersect_key($config, $keys) ? false : true; } public function close(): bool { if (!$this->handler) { return false; } $isClosed = $this->handler->close(); if ($isClosed) { $this->handler = null; $this->isConnected = false; } return $isClosed; } private function determineCharset() { $charset = $this->config->dbcharset; $collate = $this->config->dbcollate; if ($charset === 'utf8' && $this->hasCapabilities('utf8mb4')) { $charset = 'utf8mb4'; } if ($charset === 'utf8mb4' && ! $this->hasCapabilities('utf8mb4')) { $charset = 'utf8'; $collate = str_replace('utf8mb4_', 'utf8_', $collate); } if ($charset === 'utf8mb4') { if (! $collate || $collate === 'utf8_general_ci') { $collate = 'utf8mb4_unicode_ci'; } else { $collate = str_replace('utf8_', 'utf8mb4_', $collate); } } if ($this->hasCapabilities('utf8mb4_520') && $collate === 'utf8mb4_unicode_ci') { $collate = 'utf8mb4_unicode_520_ci'; } $this->config->dbcharset = $charset; $this->config->dbcollate = $collate; } private function setCharset(): bool { $this->determineCharset(); $charset = $this->config->dbcharset; $collate = $this->config->dbcollate; if (!$this->hasCapabilities('collation') || empty($charset)) { return false; } if (!$this->handler->set_charset($charset)) { return false; } $query = sprintf('SET NAMES %s', $charset); if (! empty($collate)) { $query .= sprintf(' COLLATE %s', $collate); } return $this->handler->query($query) > 0 ? true : false; } private function hasCapabilities(string $capabilities): bool { $serverVersion = $this->serverVersion(); $serverInfo = $this->serverInfo(); if ($serverVersion === '5.5.5' && strpos($serverInfo, 'MariaDB') !== false && PHP_VERSION_ID < 80016) { $serverInfo = preg_replace('@^5\.5\.5-(.*)@', '$1', $serverInfo); $serverVersion = preg_replace('@[^0-9.].*@', '', $serverInfo); } switch (strtolower($capabilities)) { case 'collation': return version_compare($serverVersion, '4.1', '>='); case 'set_charset': return version_compare($serverVersion, '5.0.7', '>='); case 'utf8mb4': if (version_compare($serverVersion, '5.5.3', '<')) { return false; } $clienVersion = $this->clientInfo(); if (false !== strpos($clienVersion, 'mysqlnd')) { $clienVersion = preg_replace('@^\D+([\d.]+).*@', '$1', $clienVersion); return version_compare($clienVersion, '5.0.9', '>='); } else { return version_compare($clienVersion, '5.5.3', '>='); } case 'utf8mb4_520': return version_compare($serverVersion, '5.6', '>='); } return false; } public function clientInfo() { return !empty($this->handler->host_info) ? $this->handler->host_info : ''; } public function serverInfo() { return !empty($this->handler->server_info) ? $this->handler->server_info : ''; } public function isMariaDB(): bool { return stripos($this->serverInfo(), 'MariaDB') !== false; } public function serverVersion(): string { $serverInfo = $this->serverInfo(); if (stripos($serverInfo, 'MariaDB') !== false && preg_match('@^([0-9\.]+)\-([0-9\.]+)\-MariaDB@i', $serverInfo, $match)) { return $match[2]; } return preg_replace('@[^0-9\.].*@', '', $serverInfo); } public function commit(): bool { return $this->handler->commit(); } public function autoCommit(bool $enable = true) { return $this->handler->autocommit($enable); } public function foreignKeyChecksOff(): bool { $status = false; $statement = 'SET FOREIGN_KEY_CHECKS=0'; try { $status = $this->exec($statement); } catch (\Throwable $e) { $this->kernel->log( [ 'method' => __METHOD__, 'error' => $e->getMessage(), 'query' => $statement, ] ); } return $status; } public function setSession($query): bool { $status = false; $statement = 'SET SESSION ' . $query; try { $status = $this->exec($statement); } catch (\Throwable $e) { $this->kernel->log( [ 'method' => __METHOD__, 'error' => $e->getMessage(), 'query' => $statement, ] ); } return $status; } public function startTransaction(): bool { return $this->handler->begin_transaction(); } public function rollback(): bool { return $this->handler->rollback(); } public function stopTransaction(): bool { return $this->commit(); } public function query(string $query) { return $this->handler->query($query); } public function exec(string $query): bool { $result = $this->query($query); return $result !== false; } public function error(): string { return isset($this->handler->error) ? $this->handler->error : ''; } public function errno(): int { return isset($this->handler->errno) ? $this->handler->errno : 0; } public function removeTablesWithPrefix(string $prefix): bool { if (!$this->isConnected || empty($prefix)) { return false; } $prefix = $this->handler->real_escape_string($prefix); $result = $this->query('SHOW TABLES LIKE "' . $prefix . '%"'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return false; } while ($row = $result->fetch_row()) { $this->exec("DROP TABLE `" . $row[0] . "`"); } return true; } public function removeTablesNotWithPrefix(string $prefix): bool { if (!$this->isConnected || empty($prefix)) { return false; } $prefix = $this->handler->real_escape_string($prefix); $result = $this->query('SHOW TABLES WHERE `Tables_in_' . $this->dbName . '` NOT LIKE "' . $prefix . '%"'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return false; } while ($row = $result->fetch_row()) { $this->exec("DROP TABLE `" . $row[0] . "`"); } return true; } public function setShortNameTable(string $tableName): string { $shortName = substr(self::TMP_PREFIX . md5($tableName), 0, 60); $this->useHandle->cache->put($shortName, $tableName, 'tableshortname'); return $shortName; } public function getTableFromShortName(string $shortName): string { $tableName = $this->useHandle->cache->get($shortName, 'tableshortname'); if (empty($tableName)) { return $shortName; } return $tableName; } public function maybeShortenTableNameForQuery(&$query): bool { if (strpos($query, 'DROP TABLE') !== 0 && strpos($query, 'CREATE TABLE') !== 0 && strpos($query, 'INSERT INTO') !== 0) { return false; } $tableName = null; if (preg_match('@^DROP\sTABLE\s(IF\sEXISTS\s)?`(.+?(?=`))`;$@', $query, $queryMatches)) { $tableName = $queryMatches[2]; } elseif (preg_match('@^CREATE\sTABLE\s`(.+?(?=`))`@', $query, $queryMatches)) { $tableName = $queryMatches[1]; } elseif (preg_match('@^INSERT\sINTO\s`(.+?(?=`))`\s@', $query, $queryMatches)) { $tableName = $queryMatches[1]; } if ($tableName === null || strlen($tableName) <= 64) { return false; } $shortName = $this->setShortNameTable($tableName); $query = str_replace($tableName, $shortName, $query); return true; } public function getSearchReplace(SearchReplacer $searchReplacer, BackupMetadata $backupMetadata, $config): SearchReplace { $searchReplacer->setIsWpBakeryActive($backupMetadata->getWpBakeryActive()); $searchReplacer->setSourceAbsPath($backupMetadata->getAbsPath()); $searchReplacer->setSourceUrls($backupMetadata->getSiteUrl(), $backupMetadata->getHomeUrl(), $backupMetadata->getUploadsUrl()); return $searchReplacer->getSearchAndReplace($config['siteurl'], $config['homeurl'], $config['abspath'], $config['uploadurl']); } }
    final class DatabaseAdapter implements DatabaseInterface { private $client; private $wpCore; private $config = []; private $mysqlVersion = ''; public function __construct(WpCore $wpCore) { $this->wpCore = $wpCore; } public function getClient(): InterfaceDatabaseClient { if (!empty($this->client)) { return $this->client; } $this->setupClient(); return $this->client; } public function getPrefix(): string { $this->maybeGetConfig(); return $this->config['dbprefix']; } public function getBasePrefix(): string { $this->maybeGetConfig(); return $this->config['dbprefix']; } public function getSqlVersion(bool $compact = false, bool $refresh = false): string { if ($refresh || empty($this->mysqlVersion)) { $this->setMySqlVersion(); } if (!$compact) { return $this->mysqlVersion; } return explode('-', explode(' ', explode('_', $this->mysqlVersion)[0])[0])[0]; } private function maybeGetConfig() { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } } private function setupClient() { $this->maybeGetConfig(); $mysqli = new \mysqli($this->config['dbhost'], $this->config['dbuser'], $this->config['dbpass'], $this->config['dbname'], $this->config['dbport']); $this->client = new MysqliAdapter($mysqli); } private function setMysqlVersion() { $client = $this->getClient(); if ($client->getLink()->connect_error) { $this->mysqlVersion = ''; return; } $this->mysqlVersion = $client->getLink()->server_info ?? ''; } }
    final class Directory implements DirectoryInterface { use SlashTrait; private $wpCore; private $config; public function __construct(WpCore $wpCore) { $this->wpCore = $wpCore; } public function getBackupDirectory(): string { return ''; } public function getTmpDirectory(): string { return ''; } public function getPluginUploadsDirectory(bool $refresh = false): string { return ''; } public function getUploadsDirectory(bool $refresh = false): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['uploads']); } public function getPluginsDirectory(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['plugins']); } public function getMuPluginsDirectory(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['muplugins']); } public function getAllThemesDirectories(): array { return [ $this->getActiveThemeParentDirectory() ]; } public function getActiveThemeParentDirectory(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['themes']); } public function getLangsDirectory(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['lang']); } public function getAbsPath(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['abspath']); } public function getWpContentDirectory(): string { if (empty($this->config)) { $this->config = $this->wpCore->getConfig(); } return $this->trailingslashit($this->config['wpcontent']); } }
    final class Extractor { const STATUS_EXTRACTION_NOT_STARTED = 0; const STATUS_DOING_BACKUP_EXTRACTION = 1; const STATUS_DOING_NORMALIZE_DB_FILE = 2; const DISK_NOT_WRITEABLE_CODE = 1005; const FILE_EXTRACTED_CODE = 1006; const CHUNK_HEADER_SIZE = 4; private $kernel; private $meta; private $useHandle; private $defaultExtractPath; private $dropinsFile; private $extractorService; private $extractorDto; private $queryCompatibility; private $databaseImporter; private $databaseFilePath; private $databaseFileFullPath; private $singleFileExtraction = false; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); $this->useHandle = $this->kernel->getHandle(__CLASS__, ['cache', 'file', 'wpcore']); $this->defaultExtractPath = 'wpstg-extract/'; $this->extractorService = $this->kernel->makeInstance(ExtractorService::class); $this->dropinsFile = [ 'object-cache.php', 'advanced-cache.php', 'db.php', 'db-error.php', 'install.php', 'maintenance.php', 'php-error.php', 'fatal-error-handler.php' ]; } public function getDropinsFile(): array { return $this->dropinsFile; } public function getPartialDataFromAjaxRequest() { $partialData = [ 'status' => self::STATUS_EXTRACTION_NOT_STARTED, 'indexKey' => 0, 'itemOffset' => 0, 'totalBytes' => 0, 'countRetry' => 0 ]; if (empty($this->meta->dataPost['partial-data']) || !filter_var($this->meta->dataPost['partial-data'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)) { return (object)$partialData; } $inputData = $this->meta->dataPost['partial-data']; if (!array_intersect_key($inputData, $partialData) || empty($inputData['status'])) { return (object)$partialData; } $partialData = array_map(function ($data) { return (int)$data; }, $inputData); return (object)$partialData; } public function getDefaultExtractPath(): string { if (($cachedPath = $this->useHandle->cache->get('extractpath', 'config')) === null) { return $this->defaultExtractPath; } $cachedPath = $this->kernel->rtrimSlash($this->kernel->stripRootPath($cachedPath)) . '/'; if ($cachedPath !== '/') { return $cachedPath; } $this->useHandle->cache->remove('extractpath', 'config'); return $this->defaultExtractPath; } private function validateExtractRequest(bool $isRestore = false): array { if ($isRestore) { $this->meta->dataPost['extract-path'] = $this->meta->tmpPath . '/restore/'; $this->meta->dataPost['extract-path-overwrite'] = 1; $this->meta->dataPost['dbfile-path'] = !empty($this->meta->dataPost['dbsql-filepath']) ? $this->meta->dataPost['dbsql-filepath'] : false; } if (empty($this->meta->dataPost['extract-path'])) { $this->meta->dataPost['extract-path'] = $this->defaultExtractPath; } if (empty($this->meta->dataPost['backup-filepath']) || !file_exists($this->meta->dataPost['backup-filepath'])) { return ['success' => false, 'data' => 'Invalid request. Backup File not available']; } if (empty($this->meta->dataPost['total-files'])) { return ['success' => false, 'data' => 'Invalid request. Total files not available']; } $filePath = $this->meta->dataPost['backup-filepath']; if (!is_readable($filePath)) { return ['success' => false, 'data' => 'Failed to read backup file']; } $fileIndexPath = $this->useHandle->cache->getCacheFile($filePath, 'backupindex'); if (strpos($this->meta->dataPost['extract-path'], '../') !== false) { return ['success' => false, 'data' => 'Invalid path. Extract path contains the traversable path']; } $extractPath = $this->kernel->rtrimSlash($this->meta->dataPost['extract-path']); if (empty($extractPath) || $extractPath === '.' || $this->useHandle->file->isRootPath($extractPath)) { return ['success' => false, 'data' => 'Invalid path. Unable to extract backup to the root path']; } if ((strlen($extractPath) > 1 && substr($extractPath, 0, 1) !== '/' && substr($extractPath, 1, 1) !== ':') || $extractPath === '/') { $extractPath = $this->kernel->normalizePath($this->meta->rootPath . '/' . $extractPath); } if (is_file($extractPath)) { return ['success' => false, 'data' => 'Invalid path']; } if ($this->useHandle->file->isOutsideRootPath($extractPath)) { return ['success' => false, 'data' => 'Extract path is outside of Root Path']; } if (is_dir($extractPath) && !is_writable($extractPath)) { return ['success' => false, 'data' => 'Extract path exists and not writable']; } if (!$this->kernel->mkdir($extractPath, __LINE__)) { return ['success' => false, 'data' => sprintf('Failed to create extract path: %s', $extractPath)]; } $getPartialDataFromAjaxRequest = $this->getPartialDataFromAjaxRequest(); if ($getPartialDataFromAjaxRequest->status === self::STATUS_EXTRACTION_NOT_STARTED && $extractPath !== '/' && !$isRestore) { $this->useHandle->cache->put('extractpath', $extractPath, 'config'); } return [ 'success' => true, 'data' => [ 'getPartialDataFromAjaxRequest' => $getPartialDataFromAjaxRequest, 'extractPath' => $extractPath, 'filePath' => $filePath, 'fileIndexPath' => $fileIndexPath ] ]; } public function hasCancelRequest(): bool { if ($this->useHandle->cache->isExists('extractstop')) { $this->useHandle->cache->remove('extractstop'); return true; } return false; } private function getChunkBytes(int $itemSize, $chunkBytes = null): int { $bytes = 512; if ($chunkBytes === null) { $chunkBytes = $bytes * $this->kernel::KB_IN_BYTES; } return $itemSize < $chunkBytes ? $itemSize : $chunkBytes; } private function searchReplaceDatabaseQuery(string &$query, string $dbPrefix = 'wp_'): bool { if ($this->useHandle->file->isLineBreak(trim($query))) { return false; } if (!$this->databaseImporter->isExecutableQuery(trim($query))) { return false; } $query = str_replace([Database::TMP_PREFIX_FLAG, Database::TMP_PREFIX_FINAL_FLAG], [$dbPrefix, $dbPrefix], $query); if (strpos($query, 'INSERT INTO') === 0) { $this->databaseImporter->searchReplaceInsertQuery($query); } else { $this->queryCompatibility->removeDefiner($query); $this->queryCompatibility->removeSqlSecurity($query); $this->queryCompatibility->removeAlgorithm($query); $this->databaseImporter->removePageCompression($query); } return true; } private function normalizeDatabaseFile($extractRequest): array { $extractedDbFile = $this->useHandle->cache->get('dbfiletag', 'dbfilepath'); if ($extractedDbFile === null || !file_exists($extractedDbFile)) { return ['success' => false, 'data' => 'Failed to normalize database file', 'saveLog' => true, 'saveLogId' => __METHOD__]; } $extractedDbFileTmp = $extractedDbFile . ".normalized"; $objectFileInput = $this->useHandle->file->fileObject($extractedDbFile, 'rb'); $objectFileInputSize = $objectFileInput->getSize(); $objectFileInput->fgets(); if (!empty($extractRequest->getPartialDataFromAjaxRequest->itemOffset)) { $objectFileOutput = $this->useHandle->file->fileObject($extractedDbFileTmp, 'ab'); } else { $objectFileOutput = $this->useHandle->file->fileObject($extractedDbFileTmp, 'wb'); } if ( $extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_DOING_NORMALIZE_DB_FILE && !empty($extractRequest->getPartialDataFromAjaxRequest->indexKey) ) { $objectFileInput->rewind(); $objectFileInput->seek($extractRequest->getPartialDataFromAjaxRequest->indexKey); } $lastResponse = $this->useHandle->cache->get('dbfiletag', 'extractsuccess'); $lastResponse = $lastResponse !== null ? $lastResponse . "\n" : ""; $itemTimerStart = microtime(true); $slowDownWrite = 0; $dbHandle = $this->useHandle->wpcore->dbHandle(); $dbPrefix = $dbHandle->getDbPrefix(); $this->kernel->databaseImporterBindings(); $backupMetadata = $this->kernel->getBackupMetadata($extractRequest->filePath); $searchReplacer = $this->useHandle->wpcore->getSearchReplacer(); $this->queryCompatibility = $this->kernel->makeInstance(QueryCompatibility::class); $this->databaseImporter = $this->kernel->makeInstance(DatabaseImporter::class); $this->databaseImporter->setSearchReplace($dbHandle->getSearchReplace($searchReplacer, $backupMetadata, $this->useHandle->wpcore->getConfig())); while ($objectFileInput->valid()) { $line = $objectFileInput->readAndMoveNext(); $isMemoryExceeded = $this->kernel->isMemoryExceeded(); $currentOffset = $objectFileInput->ftell(); $indexKey = $objectFileInput->key(); $setPartialData = [ 'status' => self::STATUS_DOING_NORMALIZE_DB_FILE, 'indexKey' => $indexKey, 'itemOffset' => $currentOffset, 'isMemoryExceeded' => $isMemoryExceeded, ]; $progressPercentage = ($currentOffset / $objectFileInputSize) * 100; $progressPercentage = round(abs($progressPercentage)); $partialDataText = $lastResponse . sprintf( 'Normalizing Database file: %s, %d%% of %s. Elapsed time: <span id="elapsedtime"><!--{{elapsedtime}}--></span>', $this->kernel->sizeFormat($currentOffset), $progressPercentage, $this->kernel->sizeFormat($objectFileInputSize) ); if ($isMemoryExceeded || $indexKey > 100 && $this->kernel->isTimeExceed($this->meta->maxProcessingTime, $itemTimerStart)) { return ['success' => false, 'data' => $partialDataText, 'partialData' => $setPartialData, 'isMemoryExceeded' => $isMemoryExceeded]; } if ($this->hasCancelRequest()) { $this->useHandle->cache->remove('dbfiletag', 'dbfiletag'); $this->useHandle->cache->remove('dbfiletag', 'extractsuccess'); $this->kernel->unlink($extractedDbFileTmp); return ['success' => false, 'data' => 'The database file normalization was cancelled', 'isCancelled' => true]; } $status = $this->searchReplaceDatabaseQuery($line, $dbPrefix); if (is_string($status)) { $text = 'Failed to normalize database file: ' . $status; return ['success' => false, 'data' => $lastResponse . $text, 'saveLog' => $text, 'saveLogId' => __METHOD__]; } if ($objectFileOutput->fwrite($line) === false) { $text = 'Failed to normalize database file'; return ['success' => false, 'data' => $lastResponse . $text, 'saveLog' => $text, 'saveLogId' => __METHOD__]; } if ($indexKey > 100 && $slowDownWrite >= 800) { $slowDownWrite = 0; usleep(5000); } $slowDownWrite++; } $objectFileInput = null; $objectFileOutput = null; if (!rename($extractedDbFileTmp, $extractedDbFile)) { return ['success' => false, 'data' => 'Failed to normalize database file', 'saveLog' => true, 'saveLogId' => __METHOD__]; } $this->useHandle->cache->remove('dbfiletag', 'dbfiletag'); $this->useHandle->cache->remove('dbfiletag', 'extractsuccess'); $text = 'Normalized database file was successful'; return ['success' => true, 'data' => $lastResponse . $text, 'saveLog' => $text, 'saveLogId' => __METHOD__]; } public function extractBackup(bool $isRestore = false, $restorePartData = null): array { clearstatcache(); $extractRequest = $this->validateExtractRequest($isRestore); if ($extractRequest['success'] === false) { return $extractRequest; } $extractRequest = (object)$extractRequest['data']; $hasRestoreParts = $isRestore && !empty($restorePartData) && is_array($restorePartData); $this->kernel->maxExecutionTime($this->kernel::MAX_TIMEOUT_EXTRACT); if (!$isRestore && $extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_DOING_NORMALIZE_DB_FILE) { return $this->normalizeDatabaseFile($extractRequest); } if ( $extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_EXTRACTION_NOT_STARTED && !empty($this->meta->dataPost['extract-path-overwrite']) && !$this->useHandle->file->emptyDir($extractRequest->extractPath) ) { $this->kernel->log("Failed to empty directory: " . $extractRequest->extractPath, __METHOD__); } $extractSortby = !empty($this->meta->dataPost['extract-sortby']) ? (string)$this->meta->dataPost['extract-sortby'] : false; $isNormalizeDbFile = !empty($this->meta->dataPost['normalize-db-file']); $totalFiles = (int)$this->meta->dataPost['total-files']; $this->databaseFilePath = !empty($this->meta->dataPost['dbfile-path']) ? (string)$this->meta->dataPost['dbfile-path'] : false; $this->databaseFileFullPath = ''; $this->setupExtractorService($extractRequest, $extractSortby); $allExtracted = false; try { $allExtracted = $this->executeExtractorService(); } catch (\Throwable $e) { return ['success' => false, 'data' => $e->getMessage(), 'saveLog' => $e, 'saveLogId' => __METHOD__]; } $extractorDto = $this->extractorService->getExtractorDto(); $this->saveExtractorDto($extractorDto, $extractRequest->filePath); if (!$allExtracted) { $responseText = sprintf('Extracted %d/%d files', $extractorDto->getTotalFilesExtracted(), $totalFiles); $this->saveExtractorDto($extractorDto, $extractRequest->filePath); $setPartialData = [ 'status' => self::STATUS_DOING_BACKUP_EXTRACTION ]; return ['success' => false, 'data' => $responseText, 'partialData' => $setPartialData, 'isMemoryExceeded' => true]; } $responseText = sprintf('Extracting files was successful: %d files extracted', $extractorDto->getTotalFilesExtracted()); $this->useHandle->cache->remove($extractRequest->filePath, 'extractor'); if (!$isRestore && $isNormalizeDbFile && !empty($this->databaseFileFullPath)) { $setPartialData = [ 'status' => self::STATUS_DOING_NORMALIZE_DB_FILE, ]; $text = $responseText; $this->kernel->log($text, __METHOD__); $this->useHandle->cache->put('dbfiletag', $this->databaseFileFullPath, 'dbfilepath'); $this->useHandle->cache->put('dbfiletag', $text, 'extractsuccess'); $text .= "\nNormalizing database file in progress"; return ['success' => false, 'data' => $text, 'partialData' => $setPartialData]; } return ['success' => true, 'data' => $responseText, 'saveLog' => true, 'saveLogId' => __METHOD__, 'isCompleted' => true]; } public function extractItem(): array { clearstatcache(); $extractRequest = $this->validateExtractRequest(); if ($extractRequest['success'] === false) { return $this->validateExtractRequest(); } $extractRequest = (object)$extractRequest['data']; if ( empty($this->meta->dataPost['offset-data']) || !filter_var($this->meta->dataPost['offset-data'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ) { return ['success' => false, 'data' => 'Invalid offset data', 'saveLog' => true, 'saveLogId' => __METHOD__]; } $offsetData = array_map(function ($data) { return (int)$data; }, $this->meta->dataPost['offset-data']); $this->kernel->maxExecutionTime($this->kernel::MAX_TIMEOUT_EXTRACT); if ($extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_DOING_NORMALIZE_DB_FILE) { return $this->normalizeDatabaseFile($extractRequest); } if ( $extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_EXTRACTION_NOT_STARTED && !empty($this->meta->dataPost['extract-path-overwrite']) ) { $this->useHandle->file->emptyDir($extractRequest->extractPath); } if ($extractRequest->getPartialDataFromAjaxRequest->status === self::STATUS_EXTRACTION_NOT_STARTED) { $this->useHandle->cache->remove($extractRequest->filePath, 'extractfiles'); } $normalizeDbFile = !empty($this->meta->dataPost['normalize-db-file']); static $dbfilePathFull = null; $totalIndex = count($offsetData); $filePath = $extractRequest->filePath; $extractedItems = []; if (($data = $this->useHandle->cache->get($filePath, 'extractfiles')) !== null) { $extractedItems = $data; } $this->setupExtractorService($extractRequest); $this->singleFileExtraction = true; foreach ($offsetData as $num => $index) { if (in_array($num, $extractedItems)) { continue; } $this->setExtractorBackupData($filePath, $extractRequest->extractPath); $extracted = $this->extractSingleItem($index); if (!$extracted) { $extractorDto = $this->extractorService->getExtractorDto(); $this->saveExtractorDto($extractorDto, $filePath); } else { $extractedItems[] = $num; $this->useHandle->cache->put($filePath, $extractedItems, 'extractfiles'); $this->useHandle->cache->remove($extractRequest->filePath, 'extractor'); } if ($this->hasCancelRequest()) { return ['success' => false, 'data' => 'The backup extraction was cancelled', 'isCancelled' => true]; } if ($this->kernel->isThreshold()) { $setPartialData = [ 'status' => self::STATUS_DOING_BACKUP_EXTRACTION, 'indexKey' => $index ]; $responseText = sprintf('Extracting %d/%d files', $extractorDto->getTotalFilesExtracted(), $totalIndex); return ['success' => false, 'data' => $responseText, 'partialData' => $setPartialData, 'offsetData' => $offsetData, 'isMemoryExceeded' => true]; } } if ($normalizeDbFile && !empty($dbfilePathFull)) { $setPartialData = [ 'status' => self::STATUS_DOING_NORMALIZE_DB_FILE, ]; $text = 'Extracted ' . $totalIndex . ' files was successful'; $this->useHandle->cache->put('dbfiletag', $dbfilePathFull, 'dbfilepath'); $this->useHandle->cache->put('dbfiletag', $text, 'extractsuccess'); $text .= "\nNormalizing database file in progress"; return ['success' => false, 'data' => $text, 'partialData' => $setPartialData]; } return ['success' => true, 'data' => 'Extracted ' . $totalIndex . ' files was successful', 'saveLog' => true, 'saveLogId' => __METHOD__, 'isCompleted' => true]; } public function processStop(): array { if ($this->useHandle->cache->put('extractstop', time())) { return ['success' => true, 'data' => 'Send signal to stop the process', 'isCancelled' => true]; } return ['success' => false, 'data' => 'Failed to stop the process', 'isCancelled' => true]; } private function executeExtractorService(): bool { while (!$this->kernel->isThreshold()) { $extracted = $this->extractSingleItem(); if ($extracted) { return true; } if ($this->hasCancelRequest()) { return true; } } return false; } private function extractSingleItem(int $fileOffset = 0): bool { try { $this->extractorService->findFileToExtract($fileOffset); } catch (\OutOfRangeException $e) { $this->kernel->log('OutOfRangeException. Error: ' . $e->getMessage()); return true; } catch (\RuntimeException $e) { $this->kernel->log($e->getMessage()); return false; } catch (\Exception $e) { if ($e->getCode() === ExtractorService::FILE_FILTERED_EXCEPTION_CODE) { return false; } if ($e->getCode() === ExtractorService::ITEM_SKIP_EXCEPTION_CODE) { return false; } if ($e->getCode() === ExtractorService::FINISHED_QUEUE_EXCEPTION_CODE) { return true; } throw $e; } try { $this->extractFile(); } catch (FileValidationException $e) { $this->kernel->log('Unable to validate file. Error: ' . $e->getMessage()); } catch (\Exception $e) { if ($e->getCode() === self::FILE_EXTRACTED_CODE) { return true; } throw $e; } return false; } private function extractFile() { try { if ($this->kernel->isThreshold()) { return; } $this->fileBatchWrite(); $isFileExtracted = $this->extractorService->isExtractingFileExtracted(function ($message) { $this->kernel->log($message); }); if (!$isFileExtracted) { return; } } catch (\OutOfRangeException $e) { $this->extractorService->finishExtractingFile(); } catch (\Exception $e) { if ($e->getCode() === self::DISK_NOT_WRITEABLE_CODE) { throw $e; } $this->extractorService->finishExtractingFile(); $this->kernel->log(sprintf('Skipped file %s. Reason: %s', $this->extractorService->getExtractingFile()->getRelativePath(), $e->getMessage())); } $this->extractorService->validateExtractedFileAndMoveNext(); if ($this->singleFileExtraction) { throw new \Exception("", self::FILE_EXTRACTED_CODE); } } private function setupExtractorService($extractRequest, string $extractSortBy = '') { $backupMetadata = $this->kernel->getBackupMetadata($extractRequest->filePath); $this->extractorService->setIsBackupFormatV1($backupMetadata->getIsBackupFormatV1()); $this->extractorService->setExtractOnlyPart($extractSortBy); if ($backupMetadata->getIsBackupFormatV1()) { $this->extractorService->setIndexLineDto(new BackupFileIndex()); } else { $this->extractorService->setIndexLineDto($this->kernel->makeInstance(FileHeader::class)); } $this->setExtractorBackupData($extractRequest->filePath, $extractRequest->extractPath); } private function setExtractorBackupData(string $backupFile, string $extractPath) { $backupMetadata = $this->kernel->getBackupMetadata($backupFile); $this->extractorDto = $this->getExtractorDto($backupFile, $backupMetadata); if (!$backupMetadata->getIsMultipartBackup()) { $this->extractorService->setup($this->extractorDto, $backupFile, $extractPath); return; } } private function getExtractorDto(string $filePath, BackupMetadata $backupMetadata): ExtractorDto { $filePathCache = $this->useHandle->cache->getCacheFile($filePath, 'extractor'); if (($data = $this->useHandle->cache->get($filePath, 'extractor', $filePathCache)) !== null) { $extractorDto = new ExtractorDto(); $extractorDto->setCurrentIndexOffset($data['currentIndexOffset']); $extractorDto->setExtractorFileWrittenBytes($data['extractorFileWrittenBytes']); $extractorDto->setIndexStartOffset($data['indexStartOffset']); $extractorDto->setTotalChunks($data['totalChunks']); $extractorDto->setTotalFilesExtracted($data['totalFilesExtracted']); return $extractorDto; } $extractorDto = new ExtractorDto(); $extractorDto->setTotalChunks($backupMetadata->getTotalChunks()); $extractorDto->setTotalFilesExtracted(0); return $extractorDto; } private function fileBatchWrite() { $extractingFile = $this->extractorService->getExtractingFile(); $destinationFilePath = $extractingFile->getBackupPath(); if (strpos($destinationFilePath, '.sql') !== false) { $this->kernel->log(sprintf('DEBUG: Restoring SQL file %s', $destinationFilePath)); if ($extractingFile->getPath() === $this->databaseFilePath) { $this->databaseFileFullPath = $destinationFilePath; } } $this->kernel->mkdir(dirname($destinationFilePath)); if (!$this->extractorService->createEmptyFile($destinationFilePath)) { file_put_contents($destinationFilePath, ''); } $destinationFileResource = @fopen($destinationFilePath, 'ab'); if (!$destinationFileResource) { throw new \Exception("Can not extract file $destinationFilePath"); } while (!$extractingFile->isFinished() && !$this->kernel->isThreshold()) { $readBytesBefore = $this->extractorService->getBackupFileOffset(); if ($this->hasCancelRequest()) { return; } $chunk = null; try { $chunk = $this->readChunk(); } catch (\RuntimeException $ex) { continue; } $writtenBytes = fwrite($destinationFileResource, $chunk, (int)($this->kernel->getMemoryLimit() * 0.8)); if ($writtenBytes === false || $writtenBytes <= 0) { fclose($destinationFileResource); $destinationFileResource = null; throw new \Exception("", self::DISK_NOT_WRITEABLE_CODE); } $readBytesAfter = $this->extractorService->getBackupFileOffset() - $readBytesBefore; $extractingFile->addWrittenBytes($readBytesAfter); } fclose($destinationFileResource); $destinationFileResource = null; } private function saveExtractorDto(ExtractorDto $extractorDto, string $filePath) { $cache = []; $cache['currentIndexOffset'] = $extractorDto->getCurrentIndexOffset(); $cache['extractorFileWrittenBytes'] = $extractorDto->getExtractorFileWrittenBytes(); $cache['indexStartOffset'] = $extractorDto->getIndexStartOffset(); $cache['totalChunks'] = $extractorDto->getTotalChunks(); $cache['totalFilesExtracted'] = $extractorDto->getTotalFilesExtracted(); $this->useHandle->cache->put($filePath, $cache, 'extractor'); } private function readChunk(): string { $extractingFile = $this->extractorService->getExtractingFile(); if (!$extractingFile->getIsCompressed()) { return $this->extractorService->readBackup($extractingFile->findReadTo()); } $chunkInfo = unpack('N', $this->extractorService->readBackup(self::CHUNK_HEADER_SIZE)); $this->kernel->log(sprintf('DEBUG: Extracting chunk %d/%d', $chunkInfo[1], $this->extractorDto->getTotalChunks())); $length = unpack('N', $this->extractorService->readBackup(self::CHUNK_HEADER_SIZE))[1]; if ($length === 0) { $extractingFile->setWrittenBytes(self::CHUNK_HEADER_SIZE); throw new \RuntimeException(); } $compressedChunk = $this->extractorService->readBackup($length); if (empty(trim($compressedChunk))) { return trim($compressedChunk); } $decompressed = gzuncompress($compressedChunk); if ($decompressed === false) { throw new \Exception('Could not decompress string.'); } return $decompressed; } }
    final class File { private $kernel; private $meta; const SCAN_CURRENT_DIR_ONLY = 0; const SCAN_UP_TO_ONE_DIR = 1; const SCAN_ALL_DIR = -1; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); } public function fileObject(string $filePath, string $mode = 'rb'): FileObject { return new FileObject($filePath, $mode); } public function isLineBreak($string): bool { return empty($string) || in_array($string, ["\r", "\n", "\r\n", "\n\r", chr(13), chr(10), PHP_EOL]) || preg_match('@^\s+' . chr(10) . '$@', $string); } public function isDirEmpty(string $dirPath): bool { if (!is_dir($dirPath)) { return true; } return !(new \FilesystemIterator($dirPath))->valid(); } public function isOutsideRootPath(string $dirPath): bool { $dirPath = $this->kernel->normalizePath($dirPath); $rootPath = $this->kernel->normalizePath($this->meta->rootPath); return $rootPath !== substr($dirPath, 0, strlen($rootPath)); } public function isRootPath(string $dirPath): bool { $dirPath = $this->kernel->normalizePath($dirPath); $rootPath = $this->kernel->normalizePath($this->meta->rootPath); return $dirPath === $rootPath; } private function isPathExclude($path, $exclusion): bool { if (empty($exclusion) || !is_array($exclusion)) { return false; } foreach ($exclusion as $item) { if (strpos($path, $item) !== false) { return true; } } return false; } public function moveDir(string $srcPath, string $dstPath, array $exclude = [], bool $allowOutsideRootPath = false) { if (!is_dir($srcPath)) { return false; } if ($this->isDirEmpty($srcPath)) { return false; } if (!$allowOutsideRootPath && $this->isOutsideRootPath($dstPath)) { return false; } if (!$allowOutsideRootPath && $this->isOutsideRootPath($dstPath)) { return false; } $this->kernel->mkdir($dstPath, __LINE__); $countFile = 0; try { $dirIterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator($srcPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST ); foreach ($dirIterator as $item) { $filePath = $this->kernel->normalizePath($dstPath . '/' . $dirIterator->getSubPathname()); if ($item->isDir()) { $this->kernel->mkdir($filePath, __LINE__); } else { $itemCopy = $this->kernel->normalizePath($item->getPathname()); if ($this->isPathExclude($itemCopy, $exclude)) { continue; } $this->kernel->mkdir(dirname($filePath), __LINE__); if (rename($itemCopy, $filePath)) { $countFile++; } } } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return false; } $this->kernel->rmdir($srcPath, __LINE__); return $countFile; } public function removeDir(string $dirPath, array $exclude = [], bool $removeEmpty = true): bool { if (!is_dir($dirPath)) { return true; } if ($this->isRootPath($dirPath) || $this->isOutsideRootPath($dirPath)) { return false; } if (!is_writable($dirPath) || $dirPath === '/' || substr($dirPath, 0, 2) === '..') { return false; } try { if ($removeEmpty && $this->isDirEmpty($dirPath)) { return $this->kernel->rmdir($dirPath, __LINE__); } $dirIterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST ); foreach ($dirIterator as $item) { $itemPath = $this->kernel->normalizePath($item->getPathname()); if ($this->isPathExclude($itemPath, $exclude)) { continue; } if ($item->isDir()) { $this->kernel->rmdir($itemPath, __LINE__); } else { $this->kernel->unlink($itemPath, __LINE__); } } if ($removeEmpty && $this->isDirEmpty($dirPath)) { $this->kernel->rmdir($dirPath, __LINE__); } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return false; } return true; } public function emptyDir(string $srcDir): bool { $srcDir = $this->kernel->normalizePath($srcDir); if (!is_dir($srcDir) || $this->isOutsideRootPath($srcDir) || $this->isRootPath($srcDir)) { return false; } return $this->removeDir($srcDir); } public function removeAppFile() { $this->removeDir($this->meta->tmpPath); $this->kernel->unlink($this->meta->rootPath . '/' . $this->meta->appFile, __LINE__); return true; } public function opcacheFlush(string $filePath, bool $force = true): bool { static $canInvalidate = null; if ( $canInvalidate === null && function_exists('opcache_invalidate') && ( !ini_get('opcache.restrict_api') || !empty($this->meta->dataServer['SCRIPT_FILENAME']) && stripos(realpath($this->meta->dataServer['SCRIPT_FILENAME']), ini_get('opcache.restrict_api')) === 0 ) ) { $canInvalidate = true; } if (!$canInvalidate || strtolower(substr($filePath, -4)) !== '.php') { return false; } return opcache_invalidate($filePath, $force); } public function opcacheFlushDir(string $dirPath): bool { $dirPath = realpath($dirPath); if (empty($dirPath) || !is_dir($dirPath) || !is_readable($dirPath) || $this->isDirEmpty($dirPath)) { return false; } try { foreach ($this->scanFiles($dirPath, -1, '@\.php$@') as $file) { $this->opcacheFlush($file, true); } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return false; } return true; } public function scanFiles(string $dirPath, int $maxDepth = 0, $pattern = null) { $dirPath = realpath($dirPath); if ($dirPath === false || !is_dir($dirPath) || !is_readable($dirPath)) { return []; } $pattern = !empty($pattern) ? $pattern : '@\.wpstg$@'; $recursiveDirectoryIteratorFlags = \FilesystemIterator::SKIP_DOTS | \RecursiveDirectoryIterator::KEY_AS_FILENAME | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO; $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($dirPath, $recursiveDirectoryIteratorFlags); $recursiveIteratorIterator = new \RecursiveIteratorIterator($recursiveDirectoryIterator); $recursiveIteratorIterator->setMaxDepth($maxDepth); $regexIterator = new \RegexIterator($recursiveIteratorIterator, $pattern, \RegexIterator::MATCH, \RegexIterator::USE_KEY); return $regexIterator; } public function preventAccessToDirectory(string $path) { $path = $this->kernel->normalizePath($path); if (!file_exists($path . '/index.html')) { file_put_contents($path . '/index.html', '<!-- ' . time() . ' -->'); } if (!file_exists($path . '/index.php')) { file_put_contents($path . '/index.php', '<?php // ' . time()); } if (empty($this->meta->dataServer['SERVER_SOFTWARE'])) { return; } if ( (stripos($this->meta->dataServer['SERVER_SOFTWARE'], 'Apache') !== false || stripos($this->meta->dataServer['SERVER_SOFTWARE'], 'LiteSpeed') !== false) && !file_exists($path . '/.htaccess') ) { file_put_contents($path . '/.htaccess', 'Deny from all', LOCK_EX); } if (stripos(PHP_OS, 'WIN') === 0 && !file_exists($path . '/web.config')) { $xml = '<?xml version="1.0"?>' . PHP_EOL; $xml .= '<configuration>' . PHP_EOL; $xml .= '   <system.web>' . PHP_EOL; $xml .= '       <authorization>' . PHP_EOL; $xml .= '           <deny users="*" />' . PHP_EOL; $xml .= '       </authorization>' . PHP_EOL; $xml .= '   </system.web>' . PHP_EOL; $xml .= '</configuration>' . PHP_EOL; file_put_contents($path . '/web.config', $xml, LOCK_EX); } } }
    final class RestoreDatabase { private $databaseImporterDto; private $databaseImporter; private $pathIdentifier; private $kernel; private $useHandle; private $isThreshold = false; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->useHandle = $kernel->getHandle(__CLASS__, ['cache', 'wpcore']); $kernel->databaseImporterBindings(); $this->databaseImporter = $kernel->makeInstance(DatabaseImporter::class); $this->pathIdentifier = $kernel->makeInstance(PathIdentifier::class); $this->databaseImporterDto = new DatabaseImporterDto(); } public function setup(Database $database, BackupMetadata $backupMetadata, int $currentIndex, bool $isSameSiteRestore) { $this->databaseImporterDto->setTmpPrefix(Database::TMP_PREFIX); $this->databaseImporterDto->setShortTables([], Database::TMP_PREFIX); $this->databaseImporterDto->setShortTables([], DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP); $this->databaseImporter->setup($this->databaseImporterDto, $isSameSiteRestore, $backupMetadata->getSqlServerVersion()); $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($backupMetadata->getDatabaseFile()); $fileSize = filesize($databaseFile); if ($fileSize === false || $fileSize === 0) { throw new \RuntimeException(sprintf('Could not get database file size for %s', $databaseFile)); } if (!file_exists($databaseFile)) { throw new \RuntimeException(sprintf('Can not find database file %s', $databaseFile)); } $this->databaseImporter->setWarningLogCallable([$this->kernel, 'log']); $this->databaseImporter->setFile($databaseFile); $this->databaseImporter->seekLine($currentIndex); $this->databaseImporterDto->setTotalLines($this->databaseImporter->getTotalLines()); $searchReplacer = $this->useHandle->wpcore->getSearchReplacer(); $this->databaseImporter->setSearchReplace($database->getSearchReplace($searchReplacer, $backupMetadata, $this->useHandle->wpcore->getConfig())); } public function restore(): bool { $this->databaseImporter->init(Database::TMP_PREFIX); $this->isThreshold = false; try { while (!$this->kernel->isThreshold()) { try { $this->databaseImporter->execute(); } catch (\OutOfBoundsException $e) { $this->kernel->log($e->getMessage()); } } } catch (\Exception $e) { if ($e->getCode() === DatabaseImporter::FINISHED_QUEUE_EXCEPTION_CODE) { $this->databaseImporter->finish(); return true; } elseif ($e->getCode() === DatabaseImporter::THRESHOLD_EXCEPTION_CODE) { $this->isThreshold = true; } elseif ($e->getCode() === DatabaseImporter::RETRY_EXCEPTION_CODE) { $this->databaseImporter->retryQuery(); } else { $this->databaseImporter->updateIndex(); $this->kernel->log(substr($e->getMessage(), 0, 1000)); } return false; } $this->databaseImporter->updateIndex(); return false; } public function getCurrentIndex(): int { return $this->databaseImporterDto->getCurrentIndex(); } public function getPartialData(): array { return [ 'status' => Restorer::STATUS_DOING_RESTORATION, 'totalQuery' => $this->databaseImporterDto->getTotalLines(), 'indexKey' => $this->getCurrentIndex(), 'itemOffset' => $this->databaseImporter->getCurrentOffset(), 'isLargeItem' => false, 'isMemoryExceeded' => $this->isThreshold || $this->kernel->isMemoryExceeded(), 'isRestoreDb' => 1, 'restoreNextPart' => Restorer::RESTORE_PART_DATABASE, 'emptyQuery' => 0, 'countRetry' => 0 ]; } }
    final class Restorer { private $kernel; private $meta; private $useHandle; private $partialData; private $extractPath; private $statusFile; private $hasRestoreParts; private $isOverwriteParts; const RESTORE_PART_UPLOADS = 1; const RESTORE_PART_PLUGINS = 2; const RESTORE_PART_THEMES = 3; const RESTORE_PART_LANG = 4; const RESTORE_PART_WPCONTENT = 5; const RESTORE_PART_DELAY_DATABASE = 6; const RESTORE_PART_DATABASE = 7; const RESTORE_PART_RENAME_TABLES = 8; const RESTORE_PART_DROPINS = 9; const RESTORE_PART_MU_PLUGINS = 10; const RESTORE_PART_WPROOT = 11; const RESTORE_PART_DONE = 12; const RESTORE_PART_FALSE = 0; const NO_RESTORATION_PROCESS_YET = 0; const STATUS_DOING_RESTORATION = 2; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); $this->useHandle = $this->kernel->getHandle(__CLASS__, ['cache', 'file', 'extractor', 'wpcore']); $this->extractPath = $this->meta->tmpPath . '/restore/'; } private function getPath(string $identifier) { $srcPath = $this->kernel->getPathIdentifier()->getRelativePath($identifier); $dstPath = $this->kernel->getPathIdentifier()->getAbsolutePath($identifier); return (object)['src' => $srcPath, 'dst' => $dstPath]; } private function restoreUploads(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_PLUGINS; if ($this->hasRestoreParts->uploads === self::RESTORE_PART_FALSE && $this->hasRestoreParts->database === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_UPLOADS; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore Media Library: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); if (!empty($this->isOverwriteParts->uploads) && !$this->useHandle->file->removeDir($getPath->dst, ['wp-staging/backups', 'wp-staging/cache'])) { return ['success' => false, 'data' => 'Failed to restore Media Library: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Media files: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restorePlugins(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_THEMES; if ($this->hasRestoreParts->plugins === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_PLUGINS; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore Plugins: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); $exclude = [ 'wp-staging-dev/', 'wp-staging-pro/', 'wp-staging/' ]; if (!empty($this->isOverwriteParts->plugins) && !$this->useHandle->file->removeDir($getPath->dst, $exclude)) { return ['success' => false, 'data' => 'Failed to restore Plugins: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $exclude = []; foreach (['wp-staging-dev/', 'wp-staging-pro/', 'wp-staging/'] as $dir) { if (file_exists($getPath->dst . '/' . $dir)) { $exclude[] = $dir; } } if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst, $exclude)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Plugins: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restoreThemes(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_LANG; if ($this->hasRestoreParts->themes === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_THEMES; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore Themes: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); if (!empty($this->isOverwriteParts->themes) && !$this->useHandle->file->removeDir($getPath->dst)) { return ['success' => false, 'data' => 'Failed to restore Themes: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Themes: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restoreLang(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_WPCONTENT; if ($this->hasRestoreParts->lang === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_LANG; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore Language files: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); if (!empty($this->isOverwriteParts->lang) && !$this->useHandle->file->removeDir($getPath->dst)) { return ['success' => false, 'data' => 'Failed to restore Language files: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Language files: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restoreWpContent(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_DELAY_DATABASE; if ($this->hasRestoreParts->wpcontent === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_WP_CONTENT; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore other files in wp-content: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); $pathIdentifier = $this->kernel->getPathIdentifier(); $exclude = [ $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_UPLOADS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_THEMES), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_PLUGINS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_MUPLUGINS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_LANG), ]; if (!empty($this->isOverwriteParts->wpcontent) && !$this->useHandle->file->removeDir($getPath->dst, $exclude)) { return ['success' => false, 'data' => 'Failed to restore other files in wp-content: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $excludeCopy = array_merge($exclude, $this->useHandle->extractor->getDropinsFile()); if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst, $excludeCopy)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring other files in wp-content: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restoreDatabase(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_RENAME_TABLES; if ($this->hasRestoreParts->database === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } if (empty($this->meta->dataPost['dbsql-filepath'])) { return ['success' => false, 'data' => 'Invalid request. Database File not available', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (empty($this->meta->dataPost['search-replace-data']) || !filter_var($this->meta->dataPost['search-replace-data'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)) { return ['success' => false, 'data' => 'Invalid request. Search Replace data not available', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (!array_intersect_key($this->meta->dataPost['search-replace-data'], ['backupsiteurl' => 1, 'backuphomeurl' => 1, 'backupwpbakeryactive' => 1, 'siteurl' => 1, 'homeurl' => 1])) { return ['success' => false, 'data' => 'Invalid request. Invalid Search Replace data', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $searchReplaceData = (object)$this->meta->dataPost['search-replace-data']; $isReplaceSite = $searchReplaceData->backupsiteurl !== $searchReplaceData->siteurl || $searchReplaceData->backuphomeurl !== $searchReplaceData->homeurl; $dbSqlFile = $this->kernel->normalizePath($this->meta->rootPath . '/' . $this->kernel->getPathIdentifier()->transformIdentifiableToRelativePath($this->meta->dataPost['dbsql-filepath'])); if (!file_exists($dbSqlFile)) { return ['success' => false, 'data' => 'Failed to restore Database: File not available', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $this->kernel->maxExecutionTime($this->kernel::MAX_TIMEOUT_RESTORE); $dbHandle = $this->useHandle->wpcore->dbHandle(); if ($dbHandle->connect() === false) { return ['success' => false, 'data' => sprintf('Failed to restore Database: %s', $dbHandle->response), 'saveLogId' => __METHOD__, 'isAborted' => true]; } $restoreDatabase = new RestoreDatabase($this->kernel); $filePath = $this->meta->dataPost['backup-filepath']; $itemTimerStart = microtime(true); $isRestored = false; try { $restoreDatabase->setup($dbHandle, $this->kernel->getBackupMetadata($filePath), $this->partialData->indexKey, !$isReplaceSite); $isRestored = $restoreDatabase->restore(); } catch (\Throwable $e) { return [ 'success' => false, 'data' => sprintf('Error: %s. Line %d', $e->getMessage(), $restoreDatabase->getCurrentIndex()), 'saveLogId' => __METHOD__, 'isAborted' => true ]; } if ($isRestored) { $progressText = sprintf('Restoring Database was successful: Executed %d queries', $restoreDatabase->getCurrentIndex()); return [ 'success' => false, 'data' => '<!--{{saveResponseTag}}-->' . $progressText, 'saveLog' => $progressText, 'saveLogId' => __METHOD__, 'partialData' => $this->partialData ]; } $indexKeyBefore = isset($this->partialData->indexKey) ? $this->partialData->indexKey : 0; $partialData = $restoreDatabase->getPartialData(); $indexKey = $partialData['indexKey']; $totalQuery = $partialData['totalQuery']; $queriesPerSecond = ($indexKey - $indexKeyBefore) / (microtime(true) - $itemTimerStart); $queriesPerSecond = abs($queriesPerSecond); $progressPercentage = null; $progressText = 'Restoring Database: Elapsed time: <span id="elapsedtime"><!--{{elapsedtime}}--></span>' . "\n"; if ($totalQuery > 0) { $executedText = sprintf( 'Restoring Database: Executed %s/%s queries (%s queries per second)', number_format($indexKey), number_format($totalQuery), number_format($queriesPerSecond) ); $progressPercentage = ceil(($indexKey / $totalQuery) * 100); if ($progressPercentage >= 99) { $progressPercentage = 100; } $progressText = sprintf('Restoring Database: Progress %d%% - Elapsed time: <span id="elapsedtime"><!--{{elapsedtime}}--></span>', $progressPercentage) . "\n"; } $partialDataText = $progressText . $executedText; return [ 'success' => false, 'data' => $partialDataText, 'partialData' => $partialData, 'isMemoryExceeded' => $partialData['isMemoryExceeded'], ]; } private function renameTables(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_DROPINS; if ($this->hasRestoreParts->database === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $this->kernel->maxExecutionTime($this->kernel::MAX_TIMEOUT_RESTORE); $dbHandle = $this->useHandle->wpcore->dbHandle(); if ($dbHandle->connect() === false) { return ['success' => false, 'data' => sprintf('Failed to rename Tables: %s', $dbHandle->response), 'saveLogId' => __METHOD__, 'isAborted' => true]; } $dbPrefix = isset($dbHandle->config->dbprefix) ? $dbHandle->config->dbprefix : 'wp_'; $dbTmpPrefix = $dbHandle::TMP_PREFIX; $result = $dbHandle->query('SHOW TABLES LIKE "' . $dbTmpPrefix . '%"'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return ['success' => false, 'data' => 'No tables found to rename', 'partialData' => $this->partialData]; } $countRenamed = 0; $tableCleanup = []; $itemTimerStart = microtime(true); $setPartialData = []; $totalRows = (int)$result->num_rows; try { $dbHandle->foreignKeyChecksOff(); $dbHandle->autocommit(false); $dbHandle->startTransaction(); while ($row = $result->fetch_row()) { if ($this->useHandle->extractor->hasCancelRequest()) { $this->useHandle->wpcore->enableMaintenance(false); $dbHandle->commit(); $dbHandle->autocommit(true); $dbHandle->close(); return ['success' => false, 'data' => 'The backup restoration was cancelled', 'saveLogId' => __METHOD__, 'isCancelled' => true]; } $isMemoryExceeded = $this->kernel->isMemoryExceeded(); $setPartialData = [ 'status' => self::STATUS_DOING_RESTORATION, 'isMemoryExceeded' => $isMemoryExceeded, 'isRestoreDb' => 1, 'restoreNextPart' => self::RESTORE_PART_RENAME_TABLES ]; if ($isMemoryExceeded || $this->kernel->isTimeExceed($this->meta->maxProcessingTime, $itemTimerStart)) { $dbHandle->commit(); return [ 'success' => false, 'data' => sprintf("Renaming Database tables: %d/%d", $countRenamed, $totalRows), 'saveLog' => true, 'saveLogId' => __METHOD__, 'partialData' => $setPartialData, 'isMemoryExceeded' => $isMemoryExceeded ]; } $tableTmp = $row[0]; $tableOld = str_replace($dbTmpPrefix, $dbPrefix, $dbHandle->getTableFromShortName($tableTmp)); $tableCleanup[$tableOld] = 1; if ($dbHandle->exec("DROP TABLE IF EXISTS `" . $tableOld . "`") && $dbHandle->exec("RENAME TABLE `" . $tableTmp . "` to `" . $tableOld . "`")) { $countRenamed++; } } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); $dbHandle->rollback(); $dbHandle->autocommit(true); $dbHandle->close(); return ['success' => false, 'data' => 'Renaming Database tables: failed to rename Database Tables', 'saveLogId' => __METHOD__, 'isAborted' => true]; } try { $totalOldTables = count($tableCleanup); $countRemoved = 0; if ($totalOldTables > 0) { $result = $dbHandle->query('SHOW TABLES LIKE "' . $dbPrefix . '%"'); if (($result instanceof \mysqli_result) && (int)$result->num_rows > 0) { while ($row = $result->fetch_row()) { $isMemoryExceeded = $this->kernel->isMemoryExceeded(); $setPartialData = [ 'status' => self::STATUS_DOING_RESTORATION, 'isMemoryExceeded' => $isMemoryExceeded, 'isRestoreDb' => 1, 'restoreNextPart' => self::RESTORE_PART_RENAME_TABLES ]; if ($isMemoryExceeded || $this->kernel->isTimeExceed($this->meta->maxProcessingTime, $itemTimerStart)) { $dbHandle->commit(); return [ 'success' => false, 'data' => sprintf("Removing Tables: %d/%d", $countRemoved, $totalOldTables), 'partialData' => $setPartialData, 'isMemoryExceeded' => $isMemoryExceeded ]; } if (!array_key_exists($row[0], $tableCleanup)) { if ($dbHandle->exec("DROP TABLE `" . $row[0] . "`")) { $countRemoved++; } } } } } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); $dbHandle->rollback(); $dbHandle->autocommit(true); $dbHandle->close(); return ['success' => false, 'data' => 'Renaming Database tables: failed to remove Database Tables', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $dbHandle->commit(); $dbHandle->autocommit(true); $dbHandle->close(); $this->useHandle->wpcore->maybeUpgradeDatabase(); $this->useHandle->wpcore->maybeRemoveStagingStatus(); return [ 'success' => false, 'data' => sprintf('Renaming Database tables was successful: Executed %d tables', $countRenamed), 'saveLog' => true, 'saveLogId' => __METHOD__, 'partialData' => $this->partialData, 'isAppendResponse' => true ]; } private function restoreDropins(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_MU_PLUGINS; if ($this->hasRestoreParts->dropins === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData, 'hasFile' => 0]; } $identifier = PathIdentifier::IDENTIFIER_WP_CONTENT; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { $this->kernel->log('Failed to restore Drop-in files: Could not get a valid path', __METHOD__); return ['success' => false, 'data' => '', 'partialData' => $this->partialData, 'hasFile' => 0, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); $this->kernel->mkdir($getPath->dst, __LINE__); $dropinsFile = $this->useHandle->extractor->getDropinsFile(); if (!empty($this->isOverwriteParts->dropins)) { foreach ($dropinsFile as $file) { $dstFile = $getPath->dst . '/' . $file; $this->kernel->unlink($dstFile, __LINE__); } } $countFile = 0; foreach ($dropinsFile as $file) { $srcFile = $getPath->src . '/' . $file; if (!file_exists($srcFile)) { continue; } $dstFile = $getPath->dst . '/' . $file; $this->kernel->unlink($dstFile, __LINE__); if (rename($srcFile, $dstFile)) { $countFile++; } } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Drop-ins: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData, 'hasFile' => $countFile]; } private function restoreMuPlugins(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_WPROOT; $identifier = PathIdentifier::IDENTIFIER_MUPLUGINS; $getPath = $this->getPath($identifier); $isRemoveOptimizer = false; if ($this->hasRestoreParts->muplugins === self::RESTORE_PART_FALSE) { if ($getPath->dst !== $identifier) { $this->kernel->mkdir($getPath->dst, __LINE__); $isRemoveOptimizer = $this->kernel->unlink($getPath->dst . '/wp-staging-optimizer.php', __LINE__); } return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore Mu-Plugins: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); if (!empty($this->isOverwriteParts->muplugins) && !$this->useHandle->file->removeDir($getPath->dst)) { return ['success' => false, 'data' => 'Failed to restore Mu-Plugins: Unable to overwrite directory', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst)) === false) { $countFile = 0; } if ($isRemoveOptimizer) { $this->kernel->log('Counting wp-staging-optimizer.php as a restored Drop-in file. The file will then be installed by the wp-staging plugin', __METHOD__); $countFile += 1; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring Mu-Plugins: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } private function restoreWpRoot(): array { $this->partialData->restoreNextPart = self::RESTORE_PART_DONE; if ($this->hasRestoreParts->wproot === self::RESTORE_PART_FALSE) { return ['success' => false, 'data' => '', 'partialData' => $this->partialData]; } $identifier = PathIdentifier::IDENTIFIER_ABSPATH; $getPath = $this->getPath($identifier); if ($getPath->src === $identifier || $getPath->dst === $identifier) { return ['success' => false, 'data' => 'Failed to restore other files in WP Root: Could not get a valid path', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $getPath->src = $this->kernel->normalizePath($this->extractPath . '/' . $getPath->src); $getPath->dst = $this->kernel->normalizePath($getPath->dst); $pathIdentifier = $this->kernel->getPathIdentifier(); $exclude = [ $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_UPLOADS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_THEMES), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_PLUGINS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_MUPLUGINS), $pathIdentifier->getRelativePath(PathIdentifier::IDENTIFIER_LANG), ]; $exclude = array_merge($exclude, $this->useHandle->wpcore->getWpCoreFiles()); if (($countFile = $this->useHandle->file->moveDir($getPath->src, $getPath->dst, $exclude)) === false) { $countFile = 0; } $text = ''; if ($countFile > 0) { $text = sprintf('Restoring other files in WP Root: %d files restored', $countFile); $this->kernel->log($text, __METHOD__); } return ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; } public function restoreBackup(): array { if (empty($this->meta->dataPost['total-files'])) { return ['success' => false, 'data' => 'Invalid request. Total files not available', 'saveLogId' => __METHOD__, 'isAborted' => true]; } if (empty($this->meta->dataPost['restore-parts']) || !filter_var($this->meta->dataPost['restore-parts'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)) { return ['success' => false, 'data' => 'Invalid request. Restore parts not available', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $restorePartIntersectKey = [ PartIdentifier::PLUGIN_PART_IDENTIFIER => 'plugins', PartIdentifier::MU_PLUGIN_PART_IDENTIFIER => 'muplugins', PartIdentifier::THEME_PART_IDENTIFIER => 'themes', PartIdentifier::UPLOAD_PART_IDENTIFIER => 'uploads', PartIdentifier::WP_CONTENT_PART_IDENTIFIER => 'wpcontent', PartIdentifier::DATABASE_PART_IDENTIFIER => 'database', PartIdentifier::LANGUAGE_PART_IDENTIFIER => 'lang', PartIdentifier::DROPIN_PART_IDENTIFIER => 'dropins', PartIdentifier::WP_ROOT_PART_IDENTIFIER => 'wproot' ]; $restorePartData = $this->meta->dataPost['restore-parts']; if (!array_intersect_key($restorePartData, $restorePartIntersectKey)) { return ['success' => false, 'data' => 'Invalid request. Invalid Restore parts data', 'saveLogId' => __METHOD__, 'isAborted' => true]; } $overwritePartData = $this->meta->dataPost['overwrite-parts']; if (!array_intersect_key($overwritePartData, $restorePartIntersectKey)) { return ['success' => false, 'data' => 'Invalid request. Invalid Overwrite parts data', 'saveLogId' => __METHOD__, 'isAborted' => true]; } foreach ($restorePartIntersectKey as $key => $alias) { if ($key === $alias) { continue; } if (isset($restorePartData[$key])) { $restorePartData[$alias] = $restorePartData[$key]; unset($restorePartData[$key]); } if (isset($overwritePartData[$key])) { $overwritePartData[$alias] = $overwritePartData[$key]; unset($overwritePartData[$key]); } } $restorePartData = array_map(function ($data) { return (int)$data; }, $restorePartData); $this->hasRestoreParts = (object)$restorePartData; $overwritePartData = array_map(function ($data) { return (int)$data; }, $overwritePartData); $this->isOverwriteParts = (object)$overwritePartData; if ($this->useHandle->extractor->hasCancelRequest()) { $this->useHandle->wpcore->enableMaintenance(false); return ['success' => false, 'data' => 'The backup restoration was cancelled', 'saveLogId' => __METHOD__, 'isCancelled' => true]; } clearstatcache(); $this->partialData = $this->useHandle->extractor->getPartialDataFromAjaxRequest(); if (!isset($this->partialData->restoreNextPart)) { $this->partialData->restoreNextPart = self::NO_RESTORATION_PROCESS_YET; } if ($this->partialData->restoreNextPart === self::NO_RESTORATION_PROCESS_YET) { $extractRestorePartData = array_filter($restorePartData); if (!empty($this->meta->dataPost['restore-parts-listed']) && (int)$this->meta->dataPost['restore-parts-listed'] === count($extractRestorePartData)) { $extractRestorePartData = null; } $extract = $this->useHandle->extractor->extractBackup(true, $extractRestorePartData); if ($extract['success'] === false) { return $extract; } $this->partialData->status = self::STATUS_DOING_RESTORATION; $this->partialData->restoreNextPart = self::RESTORE_PART_UPLOADS; return ['success' => false, 'data' => $extract['data'], 'saveLog' => !empty($extract['saveLog']), 'saveLogId' => !empty($extract['saveLogId']) ? $extract['saveLogId'] : null, 'partialData' => $this->partialData]; } $this->kernel->maxExecutionTime($this->kernel::MAX_TIMEOUT_RESTORE); $this->useHandle->wpcore->enableMaintenance(true); $partResponse = []; switch ($this->partialData->restoreNextPart) { case self::RESTORE_PART_UPLOADS: $partResponse = $this->restoreUploads(); break; case self::RESTORE_PART_PLUGINS: $partResponse = $this->restorePlugins(); break; case self::RESTORE_PART_THEMES: $partResponse = $this->restoreThemes(); break; case self::RESTORE_PART_LANG: $partResponse = $this->restoreLang(); break; case self::RESTORE_PART_WPCONTENT: $partResponse = $this->restoreWpContent(); break; case self::RESTORE_PART_DELAY_DATABASE: $this->partialData->restoreNextPart = self::RESTORE_PART_DATABASE; $text = !empty($this->hasRestoreParts->database) ? 'Restoring Database in progress' : ''; $partResponse = ['success' => false, 'data' => $text, 'partialData' => $this->partialData]; break; case self::RESTORE_PART_DATABASE: $partResponse = $this->restoreDatabase(); break; case self::RESTORE_PART_RENAME_TABLES: $partResponse = $this->renameTables(); break; case self::RESTORE_PART_DROPINS: $partResponse = $this->restoreDropins(); if ($partResponse['hasFile']) { $this->useHandle->wpcore->flushObjectCache(); } break; case self::RESTORE_PART_MU_PLUGINS: $partResponse = $this->restoreMuPlugins(); break; case self::RESTORE_PART_WPROOT: $partResponse = $this->restoreWpRoot(); break; default: $partResponse = []; } if (!empty($partResponse)) { if (!empty($partResponse['isCancelled']) || !empty($partResponse['isAborted'])) { $this->useHandle->wpcore->enableMaintenance(false); if (!empty($partResponse['data'])) { $this->kernel->log($partResponse['data'], !empty($partResponse['saveLogId']) ? $partResponse['saveLogId'] : null); } } return $partResponse; } $this->useHandle->wpcore->enableMaintenance(false); $this->useHandle->file->removeDir($this->extractPath); $this->useHandle->wpcore->saveConfig(); return ['success' => true, 'data' => 'Restoring backup was successful', 'saveLog' => true, 'saveLogId' => __METHOD__, 'isCompleted' => true, 'isAppendResponse' => true]; } }
    final class SearchReplacer extends AbstractSearchReplacer { private $wpCore; private $kernel; public function __construct(\WPStagingRestorer $kernel, WpCore $wpCore, SubsitesSearchReplacer $subsitesSearchReplacer) { $this->kernel = $kernel; $this->wpCore = $wpCore; parent::__construct($subsitesSearchReplacer); } protected function normalizePath(string $path): string { return $this->kernel->normalizePath($path); } protected function getUploadUrl(): string { return $this->wpCore->getConfig()['uploadurl']; } }
    final class View
    {
        private $kernel;
        private $meta;
        private $useHandle;
        public function __construct(\WPStagingRestorer $kernel)
        {
            $this->kernel    = $kernel;
            $this->meta      = $this->kernel->getMeta();
            $this->useHandle = $this->kernel->getHandle(__CLASS__);
        }
        public function getWpVersion(): array
        {
            $wpver = [];
            $list  = $this->useHandle->backupListing->getBackupFiles();
            foreach ($list as $index => $data) {
                $wpver[$data['wpVersion']] = $data['wpVersion'];
            }
            return $wpver;
        }
        public function render(string $page): bool
        {
            if (is_object($this->useHandle) && !isset($this->useHandle->view)) {
                $useHandle         = (array)$this->useHandle;
                $useHandle['view'] = $this;
                $this->useHandle   = (object)$useHandle;
            }
            $methodName = lcfirst(str_replace(' ', '', ucwords(implode(' ', explode('-', $page)))));
            if (!method_exists($this, $methodName)) {
                printf('Item %s is not available', $this->kernel->escapeString($page));
                return false;
            }
            call_user_func([$this, $methodName]);
            return true;
        }
        private function LZWDecompress($binary): string
        {
            $dictionaryCount = 256;
            $bits            = 8;
            $codes           = [];
            $rest            = 0;
            $restLength      = 0;
            for ($i = 0; $i < strlen($binary); $i++) {
                $rest = ($rest << 8) + ord($binary[$i]);
                $restLength += 8;
                if ($restLength >= $bits) {
                    $restLength -= $bits;
                    $codes[] = $rest >> $restLength;
                    $rest &= (1 << $restLength) - 1;
                    $dictionaryCount++;
                    if ($dictionaryCount >> $bits) {
                        $bits++;
                    }
                }
            }
            $dictionary = range("\0", "\xFF");
            $output     = '';
            $word       = ' ';
            $element    = ' ';
            foreach ($codes as $i => $code) {
                $element = isset($dictionary[$code]) ? $dictionary[$code] : $word . $word[0];
                $output .= $element;
                if ($i) {
                    $dictionary[] = $word . $element[0];
                }
                $word = $element;
            }
            return $output;
        }
        private function escapeTooltip($text): string
        {
            return $this->kernel->escapeString($text, ['&#xa;']);
        }
        private function printAppFile()
        {
            echo $this->kernel->escapeString($this->meta->appFile);
        }
        private function printVersion()
        {
            echo $this->kernel->escapeString($this->meta->version);
        }
        private function printLicenseOwner()
        {
            $data = $this->useHandle->activate->getData();
            if (is_object($data) && !empty($data->name) && !empty($data->email)) {
                echo $this->kernel->escapeString($data->name . ' <' . $data->email . '>');
            }
        }
        private function printLicenseType()
        {
            $data = $this->useHandle->activate->getData();
            if (is_object($data) && !empty($data->type)) {
                echo '<a href="https://wp-staging.com" rel="noopener" target="new">' . $this->kernel->escapeString($data->type) . '</a>';
            }
        }
        private function printAssets($name, $isReturn = false)
        {
            $output = $this->meta->appFile . "?wpstg-restorer-file=print-" . $this->kernel->escapeString($name) . "&_=" . $this->kernel->escapeString($this->meta->buildId);
            if ($isReturn) {
                return $output;
            }
            echo $output;
        }
        private function printProcessLoader()
        {
            echo '<img id="wpstg-restorer-spinner" src="' . $this->printAssets('loader', true) . '">';
        }
        private function partSelection($metaData): array
        {
            $sortbyOption     = [];
            $sortbyOption[''] = 'All';
            $isNotHaveIndexPartSize = !isset($metaData->indexPartSize);
            if ($metaData->isExportingPlugins && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['pluginsSize']))) {
                $sortbyOption[PartIdentifier::PLUGIN_PART_IDENTIFIER] = 'Plugins';
            }
            if ($metaData->isExportingMuPlugins && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['mupluginsSize']))) {
                $sortbyOption[PartIdentifier::MU_PLUGIN_PART_IDENTIFIER] = 'Mu-Plugins';
            }
            if ($metaData->isExportingThemes && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['themesSize']))) {
                $sortbyOption[PartIdentifier::THEME_PART_IDENTIFIER] = 'Themes';
            }
            if ($metaData->isExportingUploads && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['uploadsSize']))) {
                $sortbyOption[PartIdentifier::UPLOAD_PART_IDENTIFIER] = 'Media Library';
            }
            if ($metaData->isExportingDatabase && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['sqlSize']))) {
                $sortbyOption[PartIdentifier::DATABASE_PART_IDENTIFIER] = 'Database';
            }
            if ($metaData->isExportingOtherWpContentFiles) {
                if (!$isNotHaveIndexPartSize) {
                    switch (true) {
                        case !empty($metaData->indexPartSize['langSize']):
                            $sortbyOption[PartIdentifier::LANGUAGE_PART_IDENTIFIER] = 'Languages';
                            break;
                        case !empty($metaData->indexPartSize['dropinsSize']):
                            $sortbyOption[PartIdentifier::DROPIN_PART_IDENTIFIER] = 'Drop-in File';
                            break;
                    }
                }
                if ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['wpcontentSize'])) {
                    $sortbyOption[PartIdentifier::WP_CONTENT_PART_IDENTIFIER] = 'Other Files in wp-content';
                }
            }
            if ($metaData->isExportingDatabase && ($isNotHaveIndexPartSize || !empty($metaData->indexPartSize['wpRootSize']))) {
                $sortbyOption[PartIdentifier::WP_ROOT_PART_IDENTIFIER] = 'Other Files In WP Root';
            }
            if (count($sortbyOption) - 1 < 2) {
                $sortbyOption = [];
            }
            return $sortbyOption;
        }
        private function partRestoreList($metaData, $wpcoreConfig): array
        {
            $hasIndexPartSize = !empty($metaData->indexPartSize);
            return [
                'Media Library'                 => [
                    'name'             => PartIdentifier::UPLOAD_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingUploads,
                    'path'             => $wpcoreConfig->uploads,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::UPLOAD_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Themes'                        => [
                    'name'             => PartIdentifier::THEME_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingThemes,
                    'path'             => $wpcoreConfig->themes,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::THEME_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Plugins'                       => [
                    'name'             => PartIdentifier::PLUGIN_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingPlugins,
                    'path'             => $wpcoreConfig->plugins,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::PLUGIN_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Mu-Plugins'                    => [
                    'name'             => PartIdentifier::MU_PLUGIN_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingMuPlugins,
                    'path'             => $wpcoreConfig->muplugins,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::MU_PLUGIN_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Languages'                     => [
                    'name'             => PartIdentifier::LANGUAGE_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingOtherWpContentFiles,
                    'path'             => $wpcoreConfig->lang,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::LANGUAGE_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Drop-in File'                  => [
                    'name'             => PartIdentifier::DROPIN_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingOtherWpContentFiles,
                    'path'             => $wpcoreConfig->wpcontent,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::DROPIN_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Other Files in wp-content'     => [
                    'name'             => PartIdentifier::WP_CONTENT_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingOtherWpContentFiles,
                    'path'             => $wpcoreConfig->wpcontent,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 1,
                ],
                'Other Files in WP root folder' => [
                    'name'             => PartIdentifier::WP_ROOT_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingOtherWpContentFiles,
                    'path'             => $wpcoreConfig->wpcontent,
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::WP_ROOT_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 3, ],
                'Database File'                 => [
                    'name'             => PartIdentifier::DATABASE_PART_IDENTIFIER,
                    'status'           => (int)$metaData->isExportingDatabase,
                    'path'             => $metaData->isExportingDatabase && !empty($metaData->databaseFile) ? dirname($this->meta->rootPath . '/' . $this->kernel->getPathIdentifier()->transformIdentifiableToRelativePath($metaData->databaseFile)) : '',
                    'hasIndexPartSize' => $hasIndexPartSize && !empty($metaData->indexPartSize[PartIdentifier::DATABASE_PART_SIZE_IDENTIFIER]),
                    'restore'          => 1,
                    'overwrite'        => 2,
                ],
            ];
        }
        private function printBackupListingContains($metaData)
        {
            $isNotHaveIndexPartSize = !isset($metaData->indexPartSize);
            if ($metaData->isExportingDatabase) {
                $partSizeIdentifier = PartIdentifier::DATABASE_PART_SIZE_IDENTIFIER;
                $sqlSize            = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Database' . ($sqlSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($sqlSize) : '');
                if ($isNotHaveIndexPartSize || $sqlSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgDatabase(true) . '</div>';                }
            }
            if ($metaData->isExportingPlugins) {
                $partSizeIdentifier = PartIdentifier::PLUGIN_PART_SIZE_IDENTIFIER;
                $pluginsSize        = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Plugins' . ($pluginsSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($pluginsSize) : '');
                if ($isNotHaveIndexPartSize || $pluginsSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgPlugin(true) . '</div>';                }
            }
            if ($metaData->isExportingMuPlugins) {
                $partSizeIdentifier = PartIdentifier::MU_PLUGIN_PART_SIZE_IDENTIFIER;
                $muPluginsSize      = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Must-Use Plugins' . ($muPluginsSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($muPluginsSize) : '');
                if ($isNotHaveIndexPartSize || $muPluginsSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgMuplugin(true) . '</div>';                }
            }
            if ($metaData->isExportingThemes) {
                $partSizeIdentifier = PartIdentifier::THEME_PART_SIZE_IDENTIFIER;
                $themesSize         = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Themes' . ($themesSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($themesSize) : '');
                if ($isNotHaveIndexPartSize || $themesSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgTheme(true) . '</div>';                }
            }
            if ($metaData->isExportingUploads) {
                $partSizeIdentifier = PartIdentifier::UPLOAD_PART_SIZE_IDENTIFIER;
                $uploadsSize        = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Uploads' . ($uploadsSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($uploadsSize) : '');
                if ($isNotHaveIndexPartSize || $uploadsSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgUpload(true) . '</div>';                }
            }
            if ($metaData->isExportingOtherWpContentFiles) {
                $wpcontentPartSizeIdentifier = PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER;
                $wpcontentSize               = !empty($metaData->indexPartSize[$wpcontentPartSizeIdentifier]) ? $metaData->indexPartSize[$wpcontentPartSizeIdentifier] : 0;
                $langPartSizeIdentifier      = PartIdentifier::LANGUAGE_PART_SIZE_IDENTIFIER;
                $langSize                    = !empty($metaData->indexPartSize[$langPartSizeIdentifier]) ? $metaData->indexPartSize[$langPartSizeIdentifier] : 0;
                $wpcontentSize = $wpcontentSize + $langSize;
                $toolTip       = 'Other files in wp-content' . ($wpcontentSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($wpcontentSize) : '');
                if ($isNotHaveIndexPartSize || (int)$wpcontentSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgWpcontent(true) . '</div>';                }
            }
            if ($metaData->isExportingOtherWpRootFiles) {
                $partSizeIdentifier = PartIdentifier::WP_ROOT_PART_SIZE_IDENTIFIER;
                $wpRootSize         = !empty($metaData->indexPartSize[$partSizeIdentifier]) ? $metaData->indexPartSize[$partSizeIdentifier] : 0;
                $toolTip            = 'Other files in WP root folder' . ($wpRootSize ? '&#xa;Size: ' . $this->kernel->sizeFormat($wpRootSize) : '');
                if ($isNotHaveIndexPartSize || $wpRootSize > 0) {
                    echo '<div data-tooltip="' . $this->escapeTooltip($toolTip) . '">' . $this->printSvgWpRoot(true) . '</div>';                }
            }
        }
        public function backupPaging(string $backupFile, string $databaseFile, &$pagingData = '')
        {
            $filePathCache  = $this->useHandle->cache->getCacheFile($backupFile, 'backupmeta');
            $backupMetadata = new BackupMetadata();
            if (($data = $this->useHandle->cache->get($backupFile, 'backupmeta', $filePathCache)) !== null) {
                $backupMetadata = $backupMetadata->hydrate($data);
            } else {
                $backupMetadata = $backupMetadata->hydrateByFilePath($backupFile);
            }
            $indexLineDto = null;
            if ($backupMetadata->getIsBackupFormatV1()) {
                $indexLineDto = new BackupFileIndex();
            } else {
                $indexLineDto = $this->kernel->makeInstance(FileHeader::class);
            }
            $backupContent = new BackupContent();
            $backupContent->setPerPage(50);
            $backupContent->setBackup($backupFile, $indexLineDto, $backupMetadata);
            $backupContent->setPathIdentifier($this->kernel->getPathIdentifier());
            $backupContent->setDatabaseFiles([ $databaseFile ]);
            $pagingData = [
                'totalIndex'  => 0,
                'totalPage'   => 1,
                'indexPage'   => 1,
                'indexFilter' => '',
                'indexSortby' => ''
            ];
            if (!empty($this->meta->dataRequest['paging-data']) && filter_var($this->meta->dataRequest['paging-data'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)) {
                $pagingData = array_merge($pagingData, $this->meta->dataRequest['paging-data']);
                foreach ($pagingData as $key => $value) {
                    if (in_array($key, ['indexFilter', 'indexSortby'])) {
                        $pagingData[$key] = (string)$value;
                        continue;
                    }
                    $pagingData[$key] = abs((int)$value);
                }
            }
            $pagingData = (object)$pagingData;
            $indexPage  = $pagingData->indexPage;
            $backupContent->setFilters([
                'filename' => $pagingData->indexFilter,
                'sortby'   => $pagingData->indexSortby
            ]);
            try {
                foreach ($backupContent->getFiles($indexPage) as $backupItem) {
                    if ($databaseFile === $backupItem->getIdentifiablePath()) {
                        $backupItem->setIsDatabase(true);
                    }
                    yield $backupItem->toArray();
                }
                $pagingData = (object)$backupContent->getPagingData();
            } catch (\Exception $ex) {
                $this->kernel->log($ex, __METHOD__);
            }
            return false;
        }
        private function printCss() {echo $this->LZWDecompress("#;œgC8´äe›àç!i´Şd0›¦CLDŞg=ÄM&sp´Òt2›Nc£”İ 9ÌF¬Îr7MÆAÑÈÏ+“©Ğ¸j)Åga„ò:3§Ù Ë4C€ÀJ;5`f“1äZc7ÉäÕ)-ŠSJ3T†³¼ç4Øi“Á”È;:Î«YÜÒd:*uZ¹ê=3¥àêµSìƒB/0¸l>\"-1\ròÓÜ®[/˜ÌÇFƒs;‹Ú‘pâ€b…°Ã‘†)ZÎÊH¥S)4¸™¦c:\\MÒCy°êm7Í¦±§—ké·ìh6nú]nİÿ¹ªøh¡¸ÏSnÄ-ÑãÁ`ğœ¤: ‰3,ØÖCÚÀÍAĞF3AØÌ°hÌ0£HØ£ˆ#’*6‚@Ê6Ã+’1Œ!`æ0Ã˜Z9¶jäEcHô2¾\rÛîÇ¿L’Ù²¯û0Í%¡\0\\°,I8XÇ?,‹ù?Ì¼ …ĞxŞ”HïÃ ı²rc-\0H\\§6Í˜öõŒj\nØ‹ÈÚÁÆÒD¯?²ÜHSd9pzNí©ã:¢ê¨v¥M1â†–:Ïx\\ú¯\n\\\":6±L9Mc¨à86q\$YÉ2Äv†I²äé!Â:–%É‚dš\rËËAÄIDVõŒtHuE¼Cc¬2…ªr ©!piNMò]A9Iòôˆ\$á\0ê6Loe­\ndğ¾ÓtscG²t»!JIB3]#ˆò@‘\$‹f *Š£¸jb²­«ªıJ“İ+2Ùi=ÌÄ§5OŠ™2LÁjò½†8‰mË6<}dÈCë4¬(Í.2º‘BJU£u^ÏU-É{AS•6-š¦ùnjşcÈ1†co”SºÛ\r-ÅØ;C˜ĞÛ\rã¾9Wee[6Ìy p:Aƒ6¢:Yl¶ºÉD_=Âp¬.£\r.¥EcÊ…‚t7\rbjX)íW8‚˜Ê3ã(@*‰!`¤7ÍxX'ÈÎ“¢œP9…‚®\$“¡`‡¤?CÑD4Ä\0œ2£,MÇÅqn­=B+|g©›ªë„ˆjù°0»NãÎ½=‹Éß0]µ±}Ìµ°pùZïî4#õåÕ£»j8arVnÔR†%Š9c@ßÏAUäš˜Ffƒ(j{4ôã‡Ûáw¾¼¹j*¯ú`Èİg<ˆi?§âœÓôToÜ°‚ĞŞÕ9ŸUDÍô F7L¡…œ3¦¥1 Ø38·\ns{ÌMü@àÖhßb|ĞM‘Áôa%„ïn±í j\$\rÄ•hC6Ea»í}ğíO½ÈT— aˆQ>H\"È_D6`Ì@r\rC(}@°ëÀúTChl@‰å6,QÑ:)u(iÕ£\\J¹Wjõ>§õ‚\r5¦À2Y‚¾€¦(«ÃäRZ(:,L7È2CÚ|A¬¡(ìC’›Ğ=ÒH“¡²*‹¼F‹'5õ`b¡\$i©İ£±æ£+ ´]…6>¬ ãÜŠÄøéHuÁÜ’ÜÇ\0ÆL`lŒ@²I“g5eRa”Î†ö”šc)!\0>†ÚÑ0v#õwƒ¥“1]éæx¦AÄ\0Ü°pbh™†S	\0xs\$0ÆÅğuŠr‘œÈpe!Áœ‡’Èpl€{™Fjf=fÍ;¨ k;fÄ>¢@àrK\r\0&eÓI	!ƒ d †€ŠjÆ)’›­9–·XŒ]yT*Í|©P„Z&+³/!3T·ÈˆÌ¹#“¸0¢Æß\\Í,§pxæ”R=^EuC+ElüÓÚSñ]óÁH´Õk!´g†à&€ŠÚd’.åÜƒäMƒ9wNèƒf	AI;v§Èú‚`«å…*Å-Kª‰P#Åuv32ƒ0d|¤™y”î˜8ºx©˜KYàÔÅl¬øxy6¥f§ÖrĞŒ‡,ª;ˆ˜BÁ\0s@ºáW@ÅC2\rˆ)÷ƒPo%â¿*`ŞÔYÉñ.µGªà%òÿ7'a¿c±†´¿#fšEw„£j¯Áæ-¡ÉÌ’{yÓ²bªDuè®‚Ê×¥›O_á½5×ëdËUĞdfŒ1†@`%İì8,¬£’½VYcOM^MèÃ)&µÒ¾;[c}Ã ¿!È@ì—éÂ<Z·­v›åàppªô+Øe|/W@è]å§âĞÒ–‹É”o.ÙV‹mw/Æ1Å˜»&h¯ñ¢î7ëÇá|v½Ê–>\$Á“b¬¥‘±~+ ÊüU’”è\n¿dÕ_æ\\‹•3T*7õ†à\\Ùƒ€-|…¾äFÔ)ÁÑU¸qËUA`M,\$°7ÙxîìÙ2¸æÈÌğÒ‰Ù.ï=è«2X«×ÎÙŸ<e,×sâ(Ïê]j:L‹0ySë-I´T¹LÔ„ƒS=F‹ª²ÆÌÙ[4eVuƒ¦±ÏÚZ–]p5Ó~ °Ú£P­W³5nVÕùíCí=h®¶²¼h¬y<Sı/asĞØ˜q£Ñç±²XnËNû7<æÍË¬ô	šoŒNƒ:ÔeI˜úø´¦Å³“ï6DÕ™g#Í ·şÔàDÄ©M™¶³\0:Aqk°EeãscÑE¹Z:`‡£nÒA»JinTìÕ—ÄI@»·g¸öâÓEƒN-=¨n¢{\rérİë²8†CßFÏ~jî/Æw9ã€ƒmË†L®xdÙ×íÒ>£hŸÄ(0Òƒ—xú^òéºŸ§¶m¿¾÷ÏWüc>ğ©Ö˜Ÿ\\Š©Š-\rº¯Î=¨°^Ü•Y³ªù•R\$Pé¯*VÁÄg¿']¾\"±àtÁ p[1Î.¯?MŞÙ¾~ÁÊ'ßãÊ¬Í£é²¨RÚÕıâ»ƒ:“ÍÊ¹€Âç]%{£Üïµ`éæ°2Ü\\‘(§%—‚ôn^tòù‹c2Ï–&öÂ˜¤„0¡`º•|÷RûÁÓğmzzÃ˜sæÇóû;úÿoâÎ!v¶CHAi^ ÌjâeleÌ`p`pÀr—l®Cn\$f~›Êğh†Œî8ªéj¼¢ÙOğü Êüc¦üÀt(JV)JdşËÏ/ÜşäşN]°R.ÏĞS¯Ô\rÉôıD¯÷dN¥‡ş†Å qãÖ âNjæ²jeZÎI¢h¢ì™&'àk‚Ì ÊD\"N\$kBÒ¯hP0/‹w F—O»O¿¯ÉĞQOPnX­óÏÅæg »j&‡Ä|â¶ïï\rxıĞ\r°U\réºh*ò’+Çğv@t7ÂJ|Güğ½PÉv§îv‘`h1XÅ(¢bÅ0c(†Õ‘0:®&wo°)ŠîuÉÂ§…ü)ŠŞA Ækµ Şk¥Ö§îR(æÊlç¤n¦ØmÃ¬n&çÀÚnäŒoFøoÇ\0pGJgqq‡E'\"r`èr§.\$ç4š\$:CäBªDt‡L\$TEˆèì‹2õ)áÀ@Ø'j[t´k°xµ+H<çŒ=Eø=ì˜>gé‹\rìÌ¨¢)6+J5\nevù†YÚR%	\n‘hö\räEˆ\r A!¢Xú‰¹1%]!‰íP„@Ñu	„¦l°Äî/EÛnbÙ°¯\"Ç‰)%‡Á%Ñ¨eĞÀ¯RTcÒˆş9”ÿ¨‹Hp„ˆM\$H£*/)ÊU\nÚ†±.ƒ4\",2¸9B¾3DY*‰Vd¤‹ f`m,í§*Hƒ-„i+ÒÜ­Ñ.\"Dƒí-(‚cD?/²Å. q>ß2·\$²ÔŠd?	U/Òà l\0Ã1ÏÒÙS#/:%ìB\rÓÿàhD€Ì‰r´Ó	4b\$Ó-,ü‹@f p h32HÂí4è´@p„\0‘s	d	²h££#†=p¬VRi%(sZ|^8°ƒ(é8Q.(4@ÍÓ?q5ï1o[3¡	l`‡”û+J¨@JÌª¬ŸÏ/2ó•°\0p\nÀËƒ\"Ñ1°ƒkĞ+PÂî“s¦]o—>Ğ¤¨iä”k5?	ú980‰#,p•s˜\"3&ò%:0«'Ó©@é(KEòSLK¨»C‚•CÓ“¿%%rEFbœ%¦€ÿ“øÿêáNáH™tjt\"%4&ÉŒ¢×=ñV»\0bÉ‘\r/İHÉü\rt“Cğ†ëĞŠE4G	4§9rgE¡ET-\"t[Â:´c:ôfQĞ/J,KÍ\$D¢!@Ú‰åÆ#kø\\âF b”Œ]Œ´Æì(ÇÂ(8ï@ã:+w;\r•P¥(‰ìÑQ%Ê¿¢F¿å×IÆÂL¹S´·EsàUô2ó(RubŠ›% øàÜ½õ^xo´ZçV u µ?OiW‰Ê\rë1õ—PÕ›YğìşşÊSTUr[õÿl²ÆÀtù<BLqS³Ú;„´±?X²XëµT\rò`ÈDˆ¹ Îfîş+t×­ M‚Ìµí_Ã_UøUümÊôÍŞ>¶-âA_\"6·3×KKõ¯6á‹Y^u”¸ñ©¸İÎnvÑäŒ¶LÉ¶ 6Dw0ñ8—¥\0PQıKÇßcÑ\0¼öBGkƒC®:›Dvä,äNDópÅCrEgbg´vòõ}XÓ]²fî<ëpÅÑB^ÄR9²NœP\r@lÁlÁğ!)Tjî\$îßBi‰]Qğw/š´o–}ñú<”&µ´¹GÈ™tøÒµcRŞ€à î–ûSìÌ¶±Zƒ(HŒtÒEq6­qn`)N¹[o?rè‰·k¤ôÑ‚Ñ\"ÂåNÑ¶¶M\"ÒbÁbè@ÜÓl:èíB.×Èö³sw()H+ÆŞ‚\nì\0jìNĞB#dñ®Ú9O 1\0äòwv—G—loÑGdŠƒbd,WBTÕ\$5RER0\"_Æ(À²+l FÁŒÂ%\\Œ¹RÃ?WŒ&\0ïuMàõÑæwcµnk[Öîxê6’0»hÖãKÉéz¯t(…ÚÌ#Œr<×-v·fúïZò—­GôB8J¶ÀWÇ¸)€÷°ôPK4Õ\\ÇİSSC AÃ‚£@hÀe„\"[zøÊª»÷´\\×¸_&½„õ+…õ/~¢h¸\\8˜b*lº+Œ¿huTXh\rxl `}„µKU‰mS¦=Õe:Uâ/·\r€·›[¸+„xp-ãeT«÷‡‹ü]BS‹_ˆ…ˆWß4C˜9ØZ^l½kOm‡Å×V+Bém€‚­Š8¦ôXs,\\×¢ÖJQ–bç–hºÊ—fµ†µ¶ÿ›Ø-‘Ğ¬Ñ‘’Z¹&³ƒ@ÍÉ¹‚VÀ‡a“˜GeÊW0öwÚF:OõŞ<b;’ÉO9x4ş¯kÀ`ñ Ú±Ân P‹€Y™™˜'ÀS•Ø–yaŒû\\iÛ\\Åy–¦ÀĞîUt-å÷JæWPÒ·¤chÂÖèbuâ(é\rEšBš„?šÍdæ€Ú›‘öx9äÚ6^\r™ì9n=h/Bä@¸* f::Yûš\0Ü @ÅÙ°7ùµ])V\0AYóXÕ“g5ºô+r~Êf`ä9àÄxFÇàò)QWmÇp>yz©ªú³Qîw\r3ÇSi¸APšDYúH§	Ÿ¥\\\r:*½µ~WwèaZ|ÌFZK¨n“¨ VWIİhäàdú\n@_¡zhÔÁ©¯-©ú£¤ú¦WN¹«GÔ}xxnØññ¬m¬Ú…­\0Å¨¯/\nğï˜äñ×Œò7’Wv	‚°° Cj9Âxæ8˜‡ØYS™(-z=±8±pŠ9T¢6“±E±¯n–d´±’)n›5†­´rJHC¬AõuPÃw½R\0Ñ}iÛ}¸é…Bå²¸–ÃC‰ô²·–ßµ¸¥µû¶ ]¶`ßTš)¶ÕØßkÖĞÆ»{…ˆƒ—~WèQ™o41‹û”÷{a³Û\rÛiWYcW»µ¢Ú“\\ù¸B9n*Ys’Óë“â±¢×FÂÄ¹‰˜Ù'œ¢y'ûÏ¹›:Ú;×½¥¿Ÿú±ú/¾¤%›ÂåyÃryÈæ—Sœ÷V`x¾Ó7Zè™ÜÓ÷bÌ›C³{ÓÁÛ¡Y¼\$Ü¼'–{é]-zZ¶g¸fE¹»ÕÅûİš­Ëªî?«º¡:ÀÅ{]³›HHS|#¥“bû·Ü™¹|¹Ü£Y¹Ov<[TOî{hÓÛ“|¯½}ÁÜ¶bÂQÔ•lÔƒ‹9)]yNÛË—ÕäØXÃ¼ÏÁœŸ;e“Šû±U»ãR›\";[&ñûÁÄë¸õ=Î´YÎúiÁ|³½\\ÖK¯F{N¨6‘Œ|¡4\\a½ä‡HôÌ›Øw¶ûXÑëÑXÑùvãX™Œ4ÇÂ:Š‘Sºl¯¯¹P.N™ÿÔtÊh0ª¹M•©|OY`}Ô‚Cı‰I	½YÑÿ4‹Ô”“<A¸ài9ƒ)¥_MñyO¢‘t¿dé‘¼™pÚÇÓİÂ=EL¨Àù¯Ü”«·›åj3ÆíÏ;‚\"öö]<n6ğ=‡ŞJ77íe“Ï½+Í] [àGËbôíİOº½SĞ»!Õc‹¸¿ˆİi¸–‡×à9VÀ¯5³>%Å½>íÕ›âıAã#•i“ô“\"0Í\0c\"ØÉl“I8ÎF/Ş€Uş{èÕgÜùpxö×fVã‚åµãK½ÌŞÇ¼æ2Ôbiæ^l,=­}±ÜÜo—èõ\\ıe»/ÒaŞ\$ècÖ˜â1º˜{ãµ'ãøS»ÂkäŒ-Öº\nÃƒ0DL@Ä^º^¢×¯ae9Õp»÷Ï³ÅœÓëˆëŞêQ,Ğ.yJÙOƒK‰¸ûåuî+îDƒî€Ãîß7ƒ?=Ù_CÖÌ{…ìÇÑÛ1¼Ş_ôıƒõ?2=ŞÇLOõìüi›xQ–ÖeÌo–o5Œç+°=ò|›î?v@UîŞq±Ã+ç‹çş—èI;é?½Å0ÑWù/5êÿ£r6uë\\ÿú¦a÷µ÷}ñEÑ‘×¦<_ÑİY+jC•x9áÓ¯ì1HÌ_(õ·Ô?YşI#IjR`œ¡£¡9E\$É+2N…“Õ<!aF\n2\$«î`ÿë‹Måo{Ë›Â'Ø¾å¯…0úi_M÷Š½ÍşNÌ0#æÙHÀÇÏ›fŒmÔ\nNBúBú3\0ÎbÕ{ûàbûÇÕÀˆÀ`m}k²^ÁÇ¼@ğkÈÀpÂè\rrÕÒ<r39«£Ü7Ñ:İö3XÚÀV4x³9›AÄ‹®4ë;ÜR°H™¸oĞrxhTÅrä×ïH ÿ\$‹öI€ˆ\n†T\0¥ƒ¤ã‚Z×Ì2õ{\0Ì’JËÁ`âƒS6‰Úp4_\"ÖÂğ£\"±ñ«å|(\0ó@„aƒ©C„üfI>Ş\0Aò`šÒa.RÃ6ı“&ñ5)½\rRwh[B¤`Ğ˜€ŠbùÈD ‰rÇR ¡ˆbÀóÀİÙ,ƒÂ¢˜1µÀ:àpƒà`–©\n‡»CÅ†<4 m—ß¢üO\nRL2Í•ØUC>Á‚‡3áĞ¬øvyII´Jª%Ôšè2[)AƒH«¹”&s–ñ±uE«_ˆ‡×Š}SØ\n1KĞ\\ÊÈ!n¹Hr\n°00çÀ\n\0dX˜`Ã†pĞIÀÑÀ2ÄzEØLkúâ*³¨‹ÁÎ‰<=¢;ğeãF€™LDÑæ`DìÔÑH@…Úw^ÃhØãf†è !¿iÄÁ4}tM‘P@\0¸U¡;hbA	haEH<ñUÉ®áªU852ñj±/€,LR/8CÃN:[ÙGFğÙF(gnø¯âƒRHn(¡oœR\rç£{E51®:<øËÍ#7²ª°µx,cáVƒXV·ˆ×¯+8t ó\rp´Å5pëIŒ´1\n^‘xMÆq@P@L\$ã]	X?Æ8ƒœ&—Ã¨R*Ğ¢`”n@A\ne‚F–`h…iÂ\\Â'1¿6‘.XúÎ£…ˆ]+ÅprHIİMïXÙÅŠ&‘(f\\Wãcø™EÂ\$ÑÜ¤w¢Ø3ğE¼<ñãŠ´waÜ¯XôÃ’\$±lyLà§ã9·'t†ÜSbŸ%ÆÒ=F\n‡Ã¶¡ôíÂV»z s‚”T“­¿ÙJË2ŒB.é¥…¶!‡ÑˆrI¬¸‰.\"‘Í€,|bf1ÈúE–%1yn#e ±GÎ&²*‰PË°·HÆC2DÑâ‘K*\"U’F'‘x‰ûˆÏ(ÁE0±û64bK°HÿÇ~;0ò‡+âä)‰¬‚GpÑ’´J#îv˜!Ãa’ñÌiz\$§9ÃÖER@ŠObòøø¾F€©G’PÅu\$¨ÃÎI£×bÃì\0005” f\r«>@æ1€µpaØŠ‚V'€«Bl`êL@\r`Ğ¼ÊH/Gÿ\0fˆ§””Hf†x/ÙG„eu¡Ó<|¢ešCø†`:ó”8Æ\rpu¶Ù‡Xz1¼4	—Ğ¶Gp%-˜E±i‘l‹\0Ì ì€.ˆúÊà+0ë±Âóc‹Çàö—·ıÇÑô¿!Ó#)‚Oé>ÓÚ¢×\"@l\"™`ˆsC’7¢ÑD(FÒc8Ø/Ä	ÜJ%R¤„‰€öÉ\n)a=í,itBå¼Yp„Ì²êŠì»@Ù.ô<„šÈpIÃ%Ø™wE-jÊNj“¦ÌHJIAEX…Ú“£\né'Rn	4ÂåÖ‡LAÈ‹ARYáE3S¯K\0005(lÏ\0Y3à|5äâEhÏP(WhÉ¼*è·³ANÚ)\0L¾f1E\nÄ!\rE£*€éÈäá:11Ç œq4©Šñm„»Œ“ß–ïã†Ëàx\r3°YÓ-ÓÇ”¬Á<T~#>-Y<ÇUpb¼’r°Ö¨°ÃX6@xTË¦1‰‡Ìhš¢â_Ùã|¦´Œ¨‘|/Sœºˆ¥êT…ğÁÑ¬€ÉçB}yÑ¤ZgS>aCfƒ46ig!Nâ\0µÍœ¨o‰\$YÏã€¼´ùóyXPsfùÖ¸îbÓ0œ„Ì„‹”.LNK%ç\\ÔåàéèLÌ óÓ1Lõg›9\$¯NLšóš”÷ç¯>rcÁ\n8@w2`·Ì¦}€:Ÿ„Ê”ÉÃŞD÷ÓŞ|Æg¯>™îE³m+Ÿ4ºgË?ÙÈÏı«©›€T€[\0	ºnÁâ“Œ~Ü}À");}
        private function printJs() {echo $this->LZWDecompress("\n™¦ãĞÒo7\n¢Ã ¤ö\":œÌ¢™Ğäi‚ˆ‡b#yˆÔeŒG§CÉÀÊo3\r¦ó!ÔØe	£‘éĞE\"’I¥©d¸Ê.2ó‘Ğæ?•Ëeô\nŒ=Ÿ™\ræ3©´Ên:Î¡`„`)@ hD*i3\n5V¯\r:Fó¸€Üeº‘NW3 Dj(L§#È€äe8M8s˜€Â ;šMÕ+®FãTª•jÀˆR;Ã§#p‚¶eŸGZ}Múd2™²FS ˆC#’Éå9Ğ¼ÊjÍ“˜²ÅƒÂM0½D>#ŠÅã#³±„ä 7™G¥²è°ä='Í ¢ã9”èP¹\ró“)<Ì,0ö»\"ã™²13~fca†²ã¬ˆKšĞ´m+öşpÆ0ƒbÌÕ@JÌ=À#°ŸŒhDŒ#€à6!C¸5èX9¿Iøàˆ;äŸ²M€ğ÷ƒpz=ÄÃHz7ÏP¦‹2C8XÁGapĞ0bxî7=zL¢!`Ş°Qèß¢ãt„0¿PT<(ú\n…‰|oÁì\"²Ào4\nMcrnÜ'IJ`™\rÊ¨ÄÁ¶Ó£t'ãrX2ŠÊc8 nB>½‰Ü`:£lL<ÍTL	Â­,ó¶ó»´‘'îéaë–3Kr±!Æï`t¯DãÇW†¬42Ö•°š¥õ úÎ)R¸:F¨sªëA`Ó)¡Dl7ƒà†ÁL;ü2ˆ©{8­„C˜Æ‹	³>´|z Š‚ŒÊ Pë²A\0ê…Ğè-Bí£¼£ ‚:HëH&7ãÍà8Ëw…!Jcs\"Wö\0‹àT…ße3ñàĞ2Œ# ]¤Ì˜†áƒ%Ëj²Ş'Pas•Ã.F4ä¹8û`ÄİSce@Œ¡XDIœÄ›'\rÍhÑ“9Ñ³°~7cL½¬×Ğù£&º&£@¶;L„A˜\\…Á€E2‡¡x*	¢`HÙcµ8Øhu2»/ìÑã\$4´÷]`ŒpÅt„+]—ËKˆExÓÁw 3®1¨{œµ\rv7#²Ì˜„1P˜¯Î°ä03œwh†	G¦O#lö9jRR:&!€yŞÃ Z„£S`ŒØZ†Âœı\0PM€œ0ªÉAúj´¬&.Œ‡\$aaOe½^àïï|CS¿ğ!îş8=/[sLK<µad27:#ª\nQÓvL¥]Ì€t­OPA/n°°©d\$Şqñjè8¸œCRì\riÉ²&xK™ñPğIµˆ*Ë\0e€À­0æÚ'rĞ¸…Ó„q!€]DÈ¬9†„~CkƒJ),c¬ØÛùVG”C'öÿ_ù}Lu½ —àC²a&¤ÄLÇo	¶VÿDM‹¨”Ô;Ô¸nƒ¨ÓBà]!ğtˆ¬D”;!ÆÉ±u¼ØÊ] šÃD†¥†¢cê}ã„ÑÒGˆADd‚@S¬ÕQF‘§Å‘Y\"Ycš†F ôLe3x•&–U‡Pğà¼X*òœ„Ë8êq#º,’ññ¿†s\$ì‚ƒh\nB©˜š\nÁˆ) È>Ê2X%ãê’rª;Ihõ&&,Ç™1f¼àé4æ¬¹3dæ¸¸¹0»™FÀ­ ¨VV 9ŸŠ³vaMøøï‘Ù1\rÎü…¸NÃp]†Çr\\™9³/¤©‡‹'ˆ:/¸íÈ³ş=QJ¢ÀtqC™NGÎ”TN‡ä…\$¥\rÙFpğ¤›î'î¦S\0È¥f\\ÚlO-å””€=“ò†ƒ´8=*²¡­°çÜŠ-gÀ„¬Üƒo\rä¼0§&’Ls¨gÁ† ÔÒßÃG‘À¬¦F¸HSñ;+EÑR´dÊgLˆ ‘WP—!:' ´Ï‡0xAØs­€¥r3ÓnBªK¨n´°ÕÚ_C£Å4¦ ï†ZwA_ïÀõ†ûDŸI1&Ü‡*ÄLmp&¨À9…úd¢Ña•á@§VAqÄ·Î±†0Ğ~³ªŸY¥Ò\\;p Z!T•¢ßÎ%¶6âŠ,àRÔÁÕHGUPøYúeMJ»&\r•\090Ğtƒxi€€ZËc&o5íŸPF–“õÑzA@{(,¤Ş´S\0`Œ \"s<&ŸàĞËªéR\r¯©jLsúİ@/¤&†™ Rc‘ü+_'a.%Ìº—rò^×tlPI:‡Ş+kmÃu¹˜¤=¸´jç£(!-A•¦²:ÑÁ:¶¡«BTju²ÈK¤¾å˜œ]Êu¹	W'DĞX¨Àr£EÏ0TB\"…Ê+›.Ç'e]‘Ès¡µp‡œu]V³i@•3U2+È˜3†Â:ƒ)Õ\r“°†,P÷„ÈX{PD\\<¤ébÅ‹ñ1H9˜ón;ÀôµÈâr€{²÷ ŸÏĞhàn4-rUGÇ\"lòù§Úä.°ĞÄµƒX}¡°‰\rV¼,İŒ-:Øœk…ğwVN¼×Û@¡`Ê‰”…/Ïb#f‡¨Õª:zæÁB’mLZCIg- ÅÙ]`øwjÑ;°‘n–öOöÈtd\"˜MÎ\nñ€Óvçç¸Ã(VA†alB&Ù#]:æ¶kĞÜ\nï>ù‰·îC†äMƒXe¹Ú3PDtmECáÜÚªj)×PâqÑf»ñ\$ÁÄÍD·RZË)ü2ĞPË\rE‘:‹(#Tr¹äÛ²ù“€~ğèúKùx…Ç,âˆD*{lëñ7¬ô el¥»P¤\\Òw!@¸ºpåÖŒÙoZ&¡TÕ¶¶T‰oå`„6.r+Vˆn OAìv˜ÛÛ‰ãüÕ\\t¢ªõ=h'Wn”T*„ÖÈ9X»<x©#Ùü­çêåL‰4\$Çäš;ÈDs¾ˆÑÒµJz¶\"Ô¸ui¿SX°BiÚ©İİ6°0Û	ƒHWÁã‘Ğ9qøßÒ´sbY+-)«T¹Êµ?2ØşcW°Ş—Ş±v9f¯o’ÎèoX¹D0üxÓºAŞÃØº¬7çÃQù©#üÿ§õÚ¯Ü^¯–BÀÎ“(.”äJ–`êCÊD%éªËì°«äş\n`òvÊ¶åêfj)Îúpƒ?Âˆ¤Rp6«0:RV£mzKüÓ‚üJ´«ƒJ	Äô0`@K‚\0ŒÓÀ@¹À@…®DàŠ(L’¢€@/Bùğ+ÀD>ŠP[€@3É”ˆ†òÒ@¶L	†„ÉpD|€Ş|ÇĞ\"GÔš£¨ˆâLE(îIÀX6Ø¤¥ˆcpâ¥cğ;P´€¸\0dôôPô\rĞôÌ–†bGfûpŠ™\0DÌ<¦„`ø\0PàtÎRĞõM{àu ¸ÀR@S&‚Á@Hm`Dã<fşhô2DPñÌzäËRx(îeB°eƒ`ô…>uàøÈOpŞKXG‡¢PƒrZ,„C\rø»gøhÎ+ k¼QœÎ†Tî	B\n\0Şg9\"b lÑ²e#…·ÑÀ&50Ò:àÌm¤D€`°ö Í`oÀøÀZà¸Šï \0Ipöî€êÒÀ¸àZ¬:Xx™–¢\0~QìuÎ{#€êÒD6±°>ƒìÃj—…LCMö6şím\\—­´È&…\nf‹%‰è¨Î[Ä: ¦Øbj(Šq\nÈÈ@¢hC<eÀÊCè€acŞàl@ò;@†£´`v -<§H!`Ä€î¨\nÈ\nŠ}+ È§àÖ¢¬€¦¼ìÂ/Ô­HsEjE\0®}Gô²şL€ë0S\0\rL†}Íî|jş¢¼L€`DÑæÌ6ˆ#h‚\$%â3\$\"€Â`bP- ç4SI*€ò‘d.mˆƒ`y å6ˆ Ä%àÈƒ†ƒ`ZˆÀàIÀø\r¢\\ Ä>È„œ*àøZÂ¤\rÄA9£1C7%½9cj!†ÉDpô€Â Ì ZÀºj–Àû„àù;±8‘‘‘ı;Ò#\"r!qùÒxm`àlõ W@DBüòf„^‘1=”D@TÒ ü ~±O@±SA0ñ-DÒ N×±LóŞT?1;D@·>1:TL ¸Q+´®ô3@ÀUÆÖ?&‰A0¼3Ó¸‘,=H°õEÔ`”K SEkEtCDtcFtj®ô©E´­HÀS?\nk`VÀøÑMTtmdÒÅĞ„°ˆ(@Q@Æ„Ëñ\\L…)MÑNQAÀTËô4h@U\n€ÛBëOqAà¶ÀWB´Å@ÔwCp¨\rMôâ™€}\n€ÕRõNdÈ5AN/ÕHqVL€ˆFà’€uOUMO€¼t…Ap¨`˜ ¦\nucugQ4—Vã=\n€¨ WõX5QXRM{\n€‚\n€¨\nU•SOO´ÆL€ \n`Š\n ˆ	õ¯T5´äÈ`	 ™VeS5PEË:V6@ä\"³„I\n*+\0Ğ•ô©`! Z¦I7\"P£ÙA”YA4x!FbZ	¯¤Dsô^“¿Å¡Ru#btü¾­ øa´ıMVC€Ó\nŠ²«uÇY‘Ôl¿däÈ.ãhmö+Ñeuİ\\´7Qµ_Uà—V7€ø1\0øs…_\"â•é^ÕÿaõaT7Eqñ k¿b¶1@öIAƒ· ô‘Ofo€œm ½\$Ej+3bjòeJæb£ÔFæY`ãm\0Ñ vî€™mAVÓ½\"@»d<öAqëpŠÚ&æ€m ·h ^€ŸT¶{;³¿<3Ç<³Ï=3×QsİDE>3ç>³ı”î€¡)e/#1n:í¼\0ñ%²L>òt ZÀj fÏHåœòpç\0.`Údc­&&â÷ywÀl,‹xàßy2_y…œÀ| `—z@rÀY{€d`LZ\0jÀg©F\nW\\’@­àX	 z	@PŒif+Çd*3k6ãh&'’+„à9fJb6Ê`È1f‹VPfÖ.í´B^<£&PĞ\"Àò`Öcä<“ƒ²¨N¤k¶AVÎÅO\\f€É„ÈPJK=„hÃ…XXó(jğÈ72„?ÈÂBrâi6l{X~+ˆHêb‚ìœÇ˜…ƒ§L>BAÊÌ+Šš#R¦o€’êŠz½·bx²ÔDä†L§ô8Â ëîÚ0qÔ-àY@.zá\rÊW\0rßåZ\$Fç³ÃnkîP÷ñŒ\$@à& rµ˜şÍçNã­ş+ï~#PD‚ Z2ØZmÕ'@cGQ0 ˜)‚@ãf\\„t ¶-zeÈŞı’l„K‚~_®ğ[`„ ’dÍÒa¦ö÷\"Òı«äà¦÷ƒlÚ>já­Š\\€Í1àÌ<ƒÍ•¢ß•ùc\0¢	1§gùˆØ¸*nŞ¹™xêmD®ÎŒş@Ë”‚Ó”\0d×¹wƒ„>ºDI˜Àé™¥•à¨ Î{.\$a¬~\\ó”\0f×®¿•…´¬ù^i`yåšä-ƒx†è™Õ‚ŸúY˜èÍj\0Ğ Ò{FE¢‚bcÅ ïø’`Æ\$xÈ!mè¹\0ğïÂ¶!¥¢\rºQ“‚~àz	Z!¢bN×‘zPjïhçÅŒ L)s®\$ÊğQ•fb`Òb¦rcj¸ÄQ(d\$Ò3Š7¨ˆR>ša&-©†ÖE\0§„¦|99†^âha`Ş°`R\rŒZ‰#`FÁ@æhª>C\"L3Ú*`£¬ZŞ3ú>ï Ô¾\"ƒSƒ	¶4º\r‰…œ8À\\~Ã	ªr’@‚k\0Ç<Cxv9¨ÒX“\"fã\$Kø2¯€z\nn¾eàŞf&b†/ËäñX¬oc‰²¥*Œ0f,¾€c\nª;Ç“‹¯^¯HX.\$ÑÛdÿô‡\n`RÀÄKÈÂ ˜öº3) Pğ>ƒ†Â¶#º³Û—FDwŠÅ‚Ætë<\npDÄØ®™`@‡Û€z\n…ªc…!¡Bü6M†˜3ûÏâÖãxr›7 c´\0İ´@öŠÑx˜LÌÚqÁÆ[µbffj!³´g»É€„ã{ØÒÇ)¤-€\"r`İmŒËÄz~bqÃ{Üü<owñ±7îyVgÄæ3Å£ÄŒ¿n…a®¾=‡ÇÅÈˆ\0±Æ'Æe2Nˆ\r¼LP¥Â<Í˜ósm76?¤ö\rœŸ#ü¸Ø|¿c§#aÊ<„ÅQ³p|rKÊü²6œÜ¶€‰Íœä'æ¸ çÎ¼±ÀÈµ~& š,ÜçÍ|ûÍ¢ph¼ÃËÇ(ëüáÏÜ‡½¼Š\n°	ÃûÌo\0Ş—Ä¦oÕÒüB­BÔ`ÂD[¿¬Ä¤Ä[¬Àw­^^ºØ³ÄlO‚§QÖÖ‰ª]l‘©ÒDmÄK6Pã^6#f6¸òwGŸŸó óùâì'­×Ã’ˆÎºí1¥ŒîŞîÎåhàu+oHtçeïU.ïŸ¢°u¾\0¨Ge©İ‚ß¡@XT B»\0æëÖ*}åÕ\0äÈİÀ†74óšÇÎ@ÖpŒàÎÅ²H¢Z>\r çáû¢~\$£`Y+cn\n‡P];é63F\r€è\nÃ–ì£@àµ,cóxÑ\"Ş	ƒˆ¦5Ó \ræ:m`¥1ZŒ™”¦ûáÉGàf@½<,dÎé—*\\À‚¤+»éÙŠ•ùå£˜ıİ¡`ó¡Ş›/¨ÕĞ)|®bï3 Éãè|B—Ìšê|KPi€cé¯µíJr—Ûé²\0óãb‰²¤hš‚¯çÄ`æ	¾í\nÌT0œ?ï,ïrŠ\"üDÔşGêÀÂä1NLŠ©À|±-ì>è+¹ãeäÃÕ_}è9‹®'¶¬.a`Ÿu4¾‰½Õµz“µº¹§¢GŠÀYõDcõÕ[ÅÈ\"'ØÂïÙ\nâwzë>‚&%§‹®Oú}›èaé_r.Ê\n¡ t;˜¬,\0QøßYà_Æo\"'ì-* Qöà¡ûÿÙ¾_™6=Q•çÿ}©\\¦ÿÆ¤?øPm®jjğLÌh¡\"ˆãÂ&~0dßûO²MşïÎ\r£şŠÀ0fu¿Qû¯ÖvÀ]Š|}@‘¿i•Ïİ1¿İ2ÎKØ\\ÂıèÂ˜M\0@ÏDGF	g>ÖóS@…êLşzÙØBÌnUœD\\¢¦ÁÅM•‘.À²\0000.€ğ`dè/í ƒT?@E2şDÀ4cÊÇ<®ä·b†Éùğü6w3ÁAZ Ğã ˆ%ïO|l ÖÙ *éW‰u~PZ`'(\n»&ğmvs@Ğ'i\0˜ZdU„#Ö¡øh8\\¦â#|Zëà:oç£yé1p@ÜvãÄ-ü@<{.@N` )à l@zxUº=Í '…m\0Nà\0¼0¸[pAGŠSxS€® \"GÂ|ábçh`BÔ·=Ø¸]«ÑHC€/p€apøh*u0s|™FŞüâÊCt6†KzùíZ.ôq@‹DÄ\rÄ³b‘M±ï‘ƒ¤7Ğ´_ ?\r’CBHšTM]r<ú-¼‡˜ıáê5€…®pû0SÜÍøÀ#p\n×&¡X‚Cqª}ˆA\0S.r¦Ä=6èˆğH\"@…B&·Í¾¢×oÃ‰œP0Ö¬I«MKb+v›Ø‚=dPœlL`ZÕ·…Y€DB¤&FBG¤ÂvI¨‹Xø6NÀ:†T?›¨aDhWsŠvF‰Q2D¥¾‚ßo°¶²ÚçˆZƒ‰ûRâ‚ø#/ÄæQ;aQÆá·HnÄqïå;+ÔKb¨Ä@ùé‡Z†âŞ§è}C°P°QJ–_÷ 1¾ñŠÌDFˆùt*ÜcÆ®(°Ü1æÁ†H/Ã[g¢bİ><Ár#!£{=¸­MJyµ™HévğVØ„\0´÷ñ²`ccløÜpyÂĞD¨Kd#cµ»r7Ç(\r@H›i¨Úœ !¨ÒÌlÀË¸ÛŠ\\!¨iÙß~‡ \"˜Ó‹ÿEÔuc{ìˆJ9°\n\0ì€¨ç˜èÄ+\"JØLYœ‹\$j€¡+d‹:xCWá}°IlhŒ}ïtg¢Ò?Iü€Şğ‰\$ıA¨l„Lº~¨í€À@4_<c¸€ğ*ÜÆ&|fsÁ^3åØ\rí‹'U!\0dZÑH•‡Oƒ¤Š	\n.Gk»äto­¤›”a´hìM)G{b\"öØ;­BÑ{qÈPÚ=¡Ï.öw{!**ºÇÈÒîé\rÌÂìú\"4¶pËÍŸØï˜²,D¶ô„9‡)TdbÌôjÈØy„Î	ü|IBw0¼|&29EŒ‚Y¢‘™7‡ø\"Òri|díÙ-IâO@&vº<BìÖçÊOˆ˜KÊ=±î¤À}-Ş5à€LeÃ©ÊDbr” KÂ)óÊiÅÆ4øWE¦^òø—ÌÔ£{/|‹è_`É\$°J‰›€€XÌã¥)Æf+ ß^À¿ Q€xŒ’/ 7C gp@ôA@1ˆà@¦OF\$ü `i°Â\"ˆKhDaß7	©\rş{v 8‡ëşdwƒé/\"=/øµLO\nàWĞß/À…°L…vÛ«\$ªÁ]\" >¼‹YÁ­\"ïk`ÆÍp#Sg¯Œ5#{œš®‚ÁÀò\\rå—9,œ>IR~‘ì¶`.™`¥ÌRL„ıˆd'Gx‰ŸÎ—imÜAmÑn™T@tQZÅZàPB¤ #¶F`: |%Fa^•\"DØ*@aÄ‘˜—„J/et')08”Šõ1Ù Ì¾“æ0*li`p‰Œ4I´Í|Šàv8D@¦Ôûêld¦›0€ı7	¯0,¢SuxÔŞ‚Áb†Ã4AAxU¦­Sİ”.ºœ›íàÿ\")mŸ\"Í,ó>Næ°\rh³À5Ñ‡ç,a‰Ì~ kûÛ8İk9¤\0Š[ó\\,ì¢Hô”\0°„WBº•`û²:y”SeªÊ>/«é#Ó•ÀÂÅj2ïŸ&sÀ|–³’TiÌÎ„òGÉĞ\0·:C–xÃZ&ˆ€d €-ysÓâo'ÖkÙÌÎ’{eºáK<‰Ò“^}åî¤}‚=bÏO`õ¬Vò¸ÄÿWlˆØ-`[Ÿõ§ZÜA½*éW[KhkÑ©hs°ó8æ:ÏÎiƒD”ğšZG'€õ¬†¢L‰”bR8'æˆÎËcâMVCSÚĞº‚ó*2íçrá‚ÏPxG“Í%hºáåDIç’52“Œx`¸uÁ±¿¹*oµ‹î'*¡ğœ­@éüÉŒş™9È¹%ÆÒ0\"®Õ{Et½(S 9Šğ.•v«dšPr†QRµA¡ñlP1	Š5‚<U4µÓ‡@£„ĞØDˆb‘Vd_ó#>ö	cqT¯XÕ°1´ÇC[|‰§­a¼ˆúd¾ @ØÙ) ®ÉÜd :pŸG]+¦EQ¼ãß	½+Â´nÑ¢†Ş–Àt-`FŠá²È@?.`	‡blÑ…=È\0(\n©‚*aÓxc¿„ğŒ©–n‘¢€’™å­¦!™¨ˆÑ«¹øT¾›õ6é‡M0¿K|A/ïm¼ÙÚõ;ajS&†TÌQµ9¨RoY\0Ñ YMf&…8áùâNõ]dÙKyOZ®f.‰¤SÊ-`Ç©õ=‘_å¨¨rIÆÙİ‘´l‹‰ªşÏHÒ04ôv@g¢ëÚßI°†ÀB§>‰ëŠ~~¢²0 v-`R6Á\0:Mİ6ó‡œ'	S©¼¢¸m‚*›`lÓ¯£%TWüªØÈ{Òµi*¨¹&€;:`À(˜Ïg1'V‰L\rà[©¯EÈÚ.µ¨A`i¹‘’Æø)š\0E	…â¼‡\\Ñ‡*ÌPõ8ªáà¬9óA‹E\0[©±ßVo¥€èïªÿzHw@òÀ\0‡(¯\"ï?(ZÜŞ¢¼V]B8hÀÈ5¢†l¨Ã²)l'&eY3ŒY‡Åiû	d(2…¸•[tØ\nëXñ‡Õk@(P—¾-§…™7Xìªİ%À“Ä­Œƒ˜¨ÏYäl¡ğc[q\r~1Ã`³\0\"lÂEoEšïq ’·mé¬Ù:k ÇC^Ö‰A\"­(	dÊ'Ğ4+´<•m†[š²•â»Õ¿LÒk‡pU¡€'êlº+ŒÃde˜Ê5ËC0Ëdš±Öa¶¯-gk>ºéÁuÍ\n´ë°rºŞ\0´ªæ’¾Àø\0¦ Ôƒ½ãI\0^†+™[ªßW	£ Ncá.7<E\n•†´ÜñˆÎ-ı4jªd¦Æµ=Yd?1H\n,¶,vY£¾[É-~økÄB1“ \"f.8ºH`\"H¸‰H;¶@5PT÷Ë‰(IsxIÖ>YLÕ4|%ßtñNW`êïºŒ!n™<»ÀÏ»U°4º¼¡\0=³Ûƒ¼|§[‹Ü¡%­>Q5hÂH{=\0°ŒĞ_‰dû(µÔÒAëšBıêL} íŸ\nÔ%I¹ÚéØ0¶Ü\0‰·P1=!ÛÔû:¥0Ñ´¢€ì(ı\"á”ø¡…A=¡TjÀ0´ğ–Â\0[­•­‰h›cŠ™×‡«#“‡İeZ0…£XY2j5ºmZ95\0„jÕÂC:ß/iÑ¼IÍÉ5&“(\$ÎH¶³?”R~k­¬c´QÚŠ¯óB¬%›Nƒ|¡g&ı.½¾J^s*jÓ(-4dLQW@Ïn±Û4o`A¡=Úwd‚«„ÉaC²i|Ãú%«)€¬Cšå¼§1dZ§R”ßÁR@º@†+‹Ü\0½\0Úh»”Ònåt—j]ÌYh9ø”¡‰”X0£¹Ç`kzyS,âğÄ<UVË!TG2¸H6Qi¤ºw· <DK5LjĞ©‡ù@&9¡„6‘#	 ÕäU¦õE—r¿¶j˜.vœ¤fé„™DÓZ>x#²™3ÇœßB f-\n“\0„Ø¬> ˜·ÑòàFM5à¨Œ×µ	hâF8…¢ã|&Ø	41(lW@tärª	’W—T¿›¢™‚l]´ØÎ6‰sL¢8Ô—D™3+N½\$5FŸzxØÃ@6„‡èmÈpw2046_©Lwïky1v˜ç˜½¾g@’ğÕaÚ)™)kÂ£øŸÄó|œ0– tà“\0ñz&*­ÈvàÛ¹6:±â7Â|Ó\\cc¢mè€ãAk4èël¿\nïáT£°]ÇÀn¿­èØ¶»Ó\nÿi¼I‘/XÀ,\\ß4hn-é0\0.!C›ç\0ìAÓEšû:K>ı«q5hÓ0\$k{8J¦ÎF¢\\Ù\0téJ/@Ü¨ÏaWÆ	í=gbr‘” ÃÃ!ÑÉ×‚Š–\\*å!aÅÌvP”­È¶=jì¥ÜP3\\ 2HŠ¢#J£{ <šøC9ãÅÜyªá xJÂf‘…ƒ½„Çx8ÁÒÛ„gªÊ`ğjg“\rY¬Iğk\"€ü8M?ù{Q€€ñÌaS§¬Â¹ë„vO|*ÄelïÀw‡H€áÛ\n,UEùJ¯©HßÁ@ˆ(1ĞƒŠæóàk*Èrú±\\\nÀe/†¸Q	°+\0004‘¸<¢¾Mù,¡cÒ€õÃ£¾Yf€ö€ 1´p‚¹eÕMŠÜIb½ø±q†-xrm;P!\0 k%ÎMö/ÔÈm€c!ƒE[‰Ui/fCG}¢@gÙTT‘¦ÇSŠ¢aøj·0CúòSÆBùp‚É²»4ì]é?m-Ö³4í›•ÁˆˆŠ³Zª\0²²,ÇxâqãWTô-\rİ§Ü@QnÀÁ*Ä!¹Ö=çOj‡·1â\\Ç­Ù¨pUz?¡y¬ ŞÉ¬ƒ`tŒ ?@é+)’À.—¯±nÖBÓm¸ˆä?\"&¢qú¤Lğ|Šäl¨T>^n>EÆl²9W]óµ\$8VOÚjëJ€şâUªO»³¸X-}nFÆ+QZô€¦£Àèk³,ó³¦> hzCæ‹~ÅdÆpuÚŠæ±d¦P\"è`k€˜àM®é,&qzÑæqp£\0pÎ©.í›U»æR¬ÕÅ’Å[Ñ–šÉ/ZDéwP©^c :}1fWH8Ø×Ncà…™Ê³Úé¶cyuÏØH¢9UÅ2lŞ†iæšœÊ_N¹l»'¦a#ä·¨¯BC`…ç„ÜÛÎˆş'Ee\0Ò°63ê½Õê­%i§† 3Iz¬t­Ni-œüè‡éKôÌÑúï½}Ç\":ü	h\0pø@yNÚæ6ÖXfµ|-}U°95°Ö¹ÏÎl.ndîÖŒÏÁX°@µ›áX/£b©…¨ÄP+\\½:˜gÇä7ña‹X¢¶Á³Å•À¨é~ªQ•àı±³à!4ô––n0ÂÒßPtD<t‘n°é\0í^!Ô	¤¶¨g¥cÜ¨ÈRš;Ñ\"ÄÉnèõZÍ·Ì]>¥u*ej1Ÿ£ç”=«H\\wƒ\0¢ÔíÒ¥¯±\n“Y•¶Ea˜Î:Ö‰\r1Pb5Í`n_ÂCcQZí@ !’\0ã€LŠ.€ë@áïªØ·”u9€ı\0`p*JjÑ*sÒlŒ®…ßàşmhQö’\" ,à=Ğ8qfˆzÉ¬;¶´.[ª«hÖÂ¼ç<ı.!{[Ğëp8gA­è\"ÇˆcRÉ=C\$\0yÔ˜ˆÄ¦Tû?Á	­vÂáõFœ#U'è*gË¥\"ÚÁŸ	·À}Õ¾§µuª\r^µü&4èÕM­\$’»\0ë&ÆM²5»¬V*æĞE•„¡v`1ˆŸ%SÀõk‘b½ :l[9Ì¼B‚jã(–ƒÊÒÈØ…–mÓQåˆs\"Sn|Õ®´3W«\0¡İàÅ£°Saì³Ó¶èŠ\$í/×éL%ò0@Ò\"b²&Dm‹ö»m4õ® áe‘³d7Ùô-›j\0Öâ8âû\r»Êš†/ajfX\$À?®«ŸçïˆêMGÍn!aÃ¡ˆ#‹§bWØF†ñğÃ^´|köÏ[eÅÖÜFog7iY‹NÂJÓ°¶srcÙ’x\$ŒØN¥öõid`+K@ÏÃ˜ˆ#í/b[	(NÂñZmC{Œ/=Õz6ÕDÉ·°˜ås(”EšM‡ö!³\0ÀĞh¨SfáYÇ€Tªh¬‰…¼­Sj±2kÄÖ#VàÃ‹›Ÿƒs—GÜè6İ¸™%	¹Svßµô7Ä+º„Ú2ª ?j1)ˆŒKŒ £ZŒ™0Eá§­˜®f&íhŒâN¹¬í«õ‚Fl‹š %vÙ^vz½-ê£C¾Õ²L©aßÙÔÁßÙv‚X»S0&V£>Ú“'q£ÌDjKIMğ€˜Áâ~Ó]µ“SŒ`X\rÓÃÌ:°Ş˜M§`.*X~c6{\\4S\0w	H£Ş·hŸÀ\r…¬9@Z,Ñ)“:½Øü\0šõ¾`´EÍ'amğX¯ñ³å…§kö¢\0÷Fàa‚5`…ËÂĞ>I¤a¬\$H²]2¼¥‚Iz@jj‘`ñ)€‡o™	ˆK\"@˜Œÿî™\rH¹\0êq‚õñ?ƒe}uo¸p\" pğf½F¶ºCß™½WÀ wÂóªØÙLè¸ï„ex7ÚI/ÂıR‹÷\rN½I8,œì‹(t‚wó1c8ÈBÃ€¬¸üËRŒá†e0œKè‹x(•ı~ÖÈª;Ú±²ÎòhBÚ,AãlÊykM&cEŒ;ì@‹ôE\0=dÀ²(&8Í¬·)Û''†9#Dm#âÕ8 P A•£æ^>1»&ÒŠUã®+ÍXç±€Ws7gBwşR¥´ÂÏUx»PeDJ_Ï˜¸*¼“;exQqñqçÜ®½\n_Ä—e¢bÙ¥Á™ƒiÓ#£¿vaˆ7m»°P8^À¸›ªàĞIfÍü·Øº(,]ù\r›xÎ­´	a Q°Óà¿›ûsAûyÉÄ“Õs3SÌ•ƒ¿³Óöäüì[\nç…½dÑ°­ó[iò]i«m/zÓ¶À bvÔÉ4BºY?E]¯¼\\’÷!™Ğ^Óñ&Æ\$Îaà\\lÙqz%³É]¡aÊ:_\$1Ñ€™¦Üû½ĞjöËğ#‘Ü~.BÉ`ÄQŠëLôjÈ8åB›€÷\$9KÌLÀS×8áü•95ˆZ‘¡l(g‡d0/Åğ‘àt^…±»{\rî8ÈçFÜz#€)x±‹Y¾ñg`~Ûú÷öÄ§hzk¬†ˆŞ.Îã­<•–¨ı‹ÏÉi—‹AtkJ\"¥Ëà‚–´!lÎŒrÀzn×‰ª!Új‚Q`éÚñ~P]Ëã3ƒˆïâó÷ë!Sí|4ˆÔòÿKk¹¨¸ ºFÿq0§v¿µçí\$¹&\\í4|lE7ÙvgíF#©ZÊÉü8º9gÌ¢ÈÊæîù«æÛ\$éŠa‡¥£[6= x@DJÛ!&#˜}Â1Ê€Ë},½”¾¯·Öï¦nÇ¬òãÁ÷ùûç~,ÎáÎÂ0CG}7æ_8(ğ/s&Ø€-ĞT^Ã\r\0ÀÈ¡Câ‘\0ˆ\$B\"ÉŞñh<^ŸDûuG /ø‘@Ïp >Y\nİÒ,Şà*R§Ir´.ğÉÂ•0®&2°¸˜¸±P_kG;¡†û;òzF-ë­»ŸË`¤Î© °7y9•dâ£ıíÈË²i~O4Ğ´wf_ÒØĞ…—µ(WÍLœªZ,ƒÑÜnµ\"¬CÖ ”T¾}|f·ä{HŞÍ¿5s cİ*xÔ^BúmFåüd6ìÌ½(Ï.™zô^\ròÙµÉF>Áü2fš¶Nı\\iT^Ä.7ÿ™XñËhe4Ÿ„=&lá:ú´v ’F«I©rÎ~/´ÔèÎF¤‚³Ÿ–Ğ†»?æ‚LŠVr]‘æeV»ö.YÁ–’¶úuéBÊ.V™ˆéêa÷vd“0#’¬h–ş›şÌ£×„şw‘2p\";Ì\\PF;½¼½´I\$–Şáİ¡iœoê8ß€‘wÖÇ;w7º`o5™è\né4mÒ†±­.‹€Q\0ÔÅÁp\në…ò—À¼ïñpIù\\(ÃFç½_TBxp~Ù}K´Àk_\\’SÖ…FÆ¾HQ;s”;ğ£YQ…©ÙÖt1‹zşÈ°A¤6ë¦ANdŒÊ÷Èú5u€‡êù.Z“Sø‘ŒĞQ—ÊD÷ìƒ'ÖY'Ï¢ÍT«£v	Øİ‚µ^¡üœÒê„Éxu‚%\nwÎL!†fô€‚x`íó¥v…©hioÛ¸²[Bíphìœ69Ÿ:\"ƒ‹ä»ÿ™Éˆ½ÿ4Cr§Ò£ªFÏ~ù	ùT ¯Aü8ÜÏ’gS_äv=7O¿Lo}6ìì“¹Ô_íjT¯hX½ÿïL­à•!ÔÚã¦#¿J6g•PŸœ€äEÑ´‰«Ö\"Ò¹Õ\0,K‰†8n6\$½xM<ÜÑ@P¬Ğ~jåU>¶Œ•Ì¸šğë…sS\\ÍÌWŞGr(P®º32,¶®˜Š{÷\"*0Âù‹ÕA“\0ö¶úäB™?ª °sM.4'Ø˜`n™3+Àí\rJ!PÖA ½”JO¯f™¢,^ãŸ½¦¿Ú¢––,*Ÿ°<³Öbx4ªİËa²¢œ;Ìá;?(Ö¦ç¾2€ÕcX4À<è­¯k½`=SÇrFÂÚaê¢ƒP,l\0 \ræ+ú?«-©äF¹¤o-,»ÜO\$hô³Ïwª¼?ú•N¥@(ÇãŞi•3Üè“>/u;¾™Ó\"‘ˆ.§’ÿ¤Àn9Óè(6Nõñ‘¢ƒ§°:ˆ‘/>¼{Ò{Â£°Ï­?b¡ê÷¢À*¾p}N±°†ûš\0¸€tdŞH(ƒÛ/^ÀÜäºf½˜!_¤À;ğ¢0”a€ÈoxˆíPYÊVD(ˆ‹;Áh(˜hí®=‚2‚İB3\\ÙÂ+«.êê\0É+]\0+\râ/¾ºI®«Ñ=ÆÌ«¥²¿Èÿ@ ÃE»ÊpüëŞ˜÷ÑgkÑ?üq*úŠt »ÿPo›š§ëê”î2§¿/û|* Á¤(”ĞgAÎœ\ré»AÔ[*§jh¶`—ĞxĞğZ¨C«Ö(”Ğk4±ÄêŸAòœ,P‚ˆ.G\"0f¼Ü# &›ÀœŠ³ŒÅ¦ÈzH‘b––ÿšôI0Â<©é&\$?ê\n2ÿ£ò=˜7ê9bØÖE-èå‹¿	|&¨è•àqàAHèAğJ1	 +	<&@\"P‰˜R)…Š%<¾*”oø26zP‡ìÎN\0™-”Ÿr!\"Ô¦øª&ÜÈPğ;‘]Èxä<ìöém¿‡~ığª!Ô\0Ô6zß©–;Kx+k€Šüj	â‰:Ì³›ø04=ş#àêÏŒUhï¼jñ»Æ%Â’*	Æó§‚İx–Àæ¥ˆ¨Úá`€˜ĞA‹2Ì|pNáÑ\rÄşÃ@‰¾’@Éçz‚g˜pí„÷l4#üğØºpÔ6|á±â…j!Ëpbı.úhG†!Ãt“\\Æ+şøA 1öE©,pÎ’Ê1ûèp³>	J˜n 4*DÆÍÀ¼@†·ãpjŒ¶¢˜@{İ¢—\nl0\níNñ×û+Q“\ngÁá“\0À]Ì(;`Ò‚å¢˜Ø§UCâ&‰Áa*2jnà}N\nØ`X²@r«T¬|&f¯SLŠøŒ¨IÀ'€Ü0A€‰\nÄ´!ÔÁÆ	¢‚±àH‚ˆÔ2%+Ì./\"µ‚²ÔhÃäÊoPÆ¡½¶ø\"ht	}P€°ùCúˆ	ˆğ§…4œ;)0Ä|İˆãi\\ÂìîğNæbŸ.8Ú¸\rÕƒV33‘ï…‹XÉ¦…óœ\riŠ\"Q?r£¾ã\nC»TDI¬°R\$<H\"L‹\$*tHlº‚ìáÂ°:CúxÖH©ã±²<˜Ót*Ëbã°«©\\ğI:À«x¹À®P <A+t„H¿DF!çDfÖKs1?Şs´Q«H¨Ğs‚£o¿M™ĞàÚE*4bW‚¦\"Lb:7vµåwDT!\n‘M®¼‰Sëñ\0B´²l`2€® Ó¬2‰|+™«/7°çÓl¢ Ø¢\$\0îÄvˆ‰Ãwg¤SezK”EmÀ™1,˜[h1qOEK\ræ+Š/è½U€ö	6Dê¨ İÉ/}‚ĞhËâÒ	Œ;aµÃü9B ‘¬*À•Œ\$^päÃ–ô_1¨­Q Æ\n¶‰„ç¢áè\r‘z€×¸Và•DEÚdäFú®¡»F%Ğu¤É¿0\r˜ƒjì` 2†EaóšEä	übÑ‡F 4¤cqEùd^‘ŒF#b‚¥€D8hè\nŠ<ºlô§*â=!ˆ4­ƒà`áÖB,{¹Ãâ§Æ0”§>Ç\rtj‚¢E\"\"mÆÍ\0Ğ\"ôNCiŠ×\"6\0àH\$Ãµü,Œj¢³Hü\\ƒƒhö*\0‡DÊ?QÃÈÃD(2t…¢ö|o­¢Æøe&)“\nv(	|í°„B,ZÀÏÃbQy\0âPrŒr)U¿±yğÁw0ÍZêµ)àqc¸f‘ÍFx	¨,Yî*0hQ[‡\$üè‹¯4ˆr´¬Lâ³zuAÃA”G*ÛÚ	²Ø9Àë·Ôÿá×í”1¢¥\$Z«³Ìv¦)49ã\"=Ó8ìæƒ´?i æß„Ş”wc\nC.–07”*¸\nÚâ Ø`3\0[\0`k×²Ìiğ/Ã#†»Ë\0Á¦ˆ‰˜Ï§ ÃhÔÂ:`£„2ê`*®\n€s*á2È?S­•àâøiSÈ 3O£õHahAÒ€Y I1R–Ì”squÇ\\âh½ÂL‹Xs!á_3 G„ÍZ`¸…èµ¡Š¤í|jÄGyôH\0ŸÆâØ6€¦ì†p!\0\$\n´[ñàHvN¬‡Ä©„\"\râ>–Ÿø/„Â,Z=8ÂÙV	2¥b/ Ôi6H98 ²›\rğWà4‚´Q^¸ccÆV%\"`\rh6%‚üQrÈ{#p/’8HÑ#¬†u	‡#ä2(•#ä‡’6ƒÍ#üˆq/Nˆ\nŒT‚N“†Ø‡\$!:±ºŠªò½IØ`/Rˆ …‡D/jW H¼s€<È>krrMA`pV²ODŠ1Ø	ÒTÇ%Q7†±I:ÔÀºkş\0âuä4‘Éä-\$4²68pü%ÏHÀf’Ò§âPÄmä˜wÄÃÈ…î­P;á{§HÚÊÁ{Ç,@ï‚dÁš’s@~ yĞÇ8ÉÈœœÏó'J^™IÑ|œ‚£I5yf²Mq'|YÂ½Æø*1QmƒJ!|uiÚÈ>Q“¡Ù\rÓô\0øG›ÔSr‚+ÕlWñytƒìÉzC1…¡ÊŒº)\0¯a %Ú”¡E­èGcQÅI„DÇd’‘¹¦‚òÜBCœ”/0°/gDÏt¤ãWF—äq¤l\0X,8’Å<…¢4©Gğ±«ÈªD3k=Ç>¶¬²H¿0& ®®72jBD„hÁğŠè¨³í`>ì6„w¦9+@*ñª¶°ÍÊt‚.Ò£~ü{…êÉ|ÄbQÅÈ¿Ä4HA‰êt\r\$° ).2 ÄDÙ®°‰eôÃˆ\$eÌH1ñ€äù|¦!ƒ!ü‰r!Âª8‰â­„ï-\0ÍÑ²SÁ;º®;øP+üµ€ö`¤ ¥Æš\r /Ã‡ï\"4rB¥³,à9+Œ¿£\0æè'ğpÉœÉ²Ş—A”‹ß\0é\$h*Ğ|‚¶»¨.Ib½LølEËË3(X (	´ıQÂl¡æ€Š‚®y¸Ep€šèh+î/ØNÊŞSËÂy”½§šËÎé‚GKá›ùÎùŒ¸òæö|­‚\"³ô¡†ÿ.<¹2%<Áôk‰Ie c?’x©rã€®.ØÇs¿˜1âó¥ılÁoæL6ÅÙß“HÇ”Å/æ\"˜ó™§Áß1+ù3Q0\$…’1ºØnÃ^Áz¢“ù3ƒÁ0(Ëèó^€uÒğ?˜!øR¦\rÄÃÌÀ‘L”şLÉ;€BıRJˆŸ¶;¨\naoŠ¶b˜’çK¡\$x+sLVWƒõPUKé/\$¾Ã·ı/T½’òKô€«!SÌÕ/¼§›Kñ2|¿põ®_s|¬Ğ*`€ëAáV:cÆ[Gâ( uÁœ³b5X1¡x‹ÆË\0 )	Üû2KáÕpáÁâ‚Ó\rĞ¾ÁÔM&ÔÀ½­L^,\n†ÚHêBÌğXtÁ®Øï£.»ğY¨‘Ì‹,p±\"Ã‹,Xø´Šz”'È4È8³`µ6Gxô’3q3ç·À¬Œê…Š)DÅú<@iâÀ—*Hm¥€¼Fñ(0Ç›€‚øœÁ+\0ª„Òç†ß›Üo1ØAç­ähËón’¢·í\0+ƒR›OQ®ĞæØZ&S3tõ)‚z@\n9Ã‚m	V\n/ğXI–ä+`³ˆƒ)¸©sˆ‹HQÈOø#Ç Â;î\rê¬¦¨Ş8†øN-’û6ÎB&ä`©ÂĞÇAT (jÍ¦&ÎzpıL\r*·X\rÀÙ	@YÌVE˜½£ß…¤<¸‰ÈñÀxƒô:P´Ù¼À†E\0ìÓ¯Ê…âk9æóÚCôa½Æ” ËÊBCÃt×,é¦è¾\$oäS†NH tçd¼<M:©»Í\0Æ±ğĞ€4i\"ÚøhéBÊ9Ì,î[;:=RÌÉ^\"Ïñ¾/F\0ïDÃ&uçP1,ìåâ§Îb?@ßóP) dœóÀ‘‚@]\"çõ)[óm²‰1üÁtÎè,`4ªa‹>\r\\;pîÅ‚è™y¦¶a28\"qÕr!ÓKS¾Ç¨8ƒ9Œê9ˆ¿GZÁ‘;dæ·NdáÔïˆª¬œù“ÍOœÕø‡Ë€#Ôä¸ÎH=\$ô!àö-¤øÀ’b£\"QÀ/•.€,OÖ`\n¯BÛä/‘”zé=à-ÏêñĞƒ^—ÑrDò\0´H ñâªdbÖlÏ…•<ìøHÇ7û¾O¶†ÈO\0@ºÈ¸\0=¾h€™¨Á\nxäÀ>~~ˆÕŠI%z/Ññ¦¼(ÉğAÙò#ÊñŸ¥èu\n…ˆ@tŒÁ€tĞ\$@@ô0Nihİ €”˜	à'ÈC2›tÃ~p ,\"bÏò»IGÃtwÏqpÀ>Ğ?À0¯ûB•‚=Ìˆë°œ÷BèAä7Ğ»;Ğot);ÂÖã¥ÄÀ‚:ô5‘mB”¼!¼„Lı”/Mfu´7ÅáB €/ËÃ>İĞPËCÊPÀ)xÀPì¸KFcM8¨Â ¶d2RL\rÅ@bVËá­š †NGóB”«>*Û\0(t”2ÑR2ı 0›WŒAt²à„­æ¶I²‘òøjÊQv;\0*Êx¨b¦XT>Ğ,é0³¯7L >ı 3ô2Ï?PTXÑfZLç6>r\\~B¸¾óH•d&MB”ñbÀ¬ÒÂÀ®D0&3Â,:•¤ñÈçÓEDğãõ3b'Äô(”ÑG¸Çt+ÑÂaİt)ÑÍ	ßO¨—Æ…\$¡ñ	ä‘i³óÉPéDDÓ˜QÅMÉ½ìuíÎèÃÎØ~ÌˆÁ®>ßïœ‰TÀ¸ÊåÚ“ÿHê.\0ïÑ]<9#!‡P¥‹ıbèÏNş ºt•?;½bü9P¬Ø²+!„ÙR!Ò_ô”A7å8j”tMÈ¨o)NLÚ@l†¦øô”HáCˆ3è—çÄ^#vÍD+!QÈÏÈ°EeJPuÁà&Dã H´EÜ–Ú33éƒ±.\rĞÛ<Û€oàØRQCÑÔçĞcDÚüã:Å¤_•+4§€ËJİ(òi…JÔˆqxIIXp´\0ùD\r'Ì›z0Q‘†F]ŒfœF;'ÒdÔEÒ,Y¢€RP`|ÇÔŒKø)¤Ât£-ÂóøP%¨µ” Ó25ˆl€3j!­’‡FÚb‘¬Àzı/ğD…Ö•…'sÁk­â4Ç »·”§, 8‹¬ÍÒ±L„‰¤k@H+u7Ô¶…[]+ôº\"¯Tˆ´¿S&JFI§N½\"3ÍR'Kà*4÷Ò ø×¡ı\n¸04ø=ú#µ/ñúÓÂÔÔ‘İÓ<»øK¶ JqÆ=`u49‘¨0äàÉ*RØ†c#ıF¿…“×\nƒ™>…\")™İ<.-ƒ!x´ÊÊ¶8D”ÇÓJÚ‘Ä…\"3©Ş¶4ÙQ˜\$’}4Rµk&ò™æ*QÈ.ÃX‘BÄî\0ÈQéDE\0È0Y\0Šñ5H„T–Yé{ƒF %Ì²[Izéd%€PXDDğ€ô+ÇEÇLB \n í€D IÄV\0¤Hà4l2ù€ªEaºfîd`	OÔ¤²%	ß\0%Ì\$œ%èü¥;r9p€XÒgEê‚†“äÉ€vQ@)\n´ºPíUC/E	š\n?\$Tb:\"EœòíS@õ ^ÑøUE¤’Ô0ÔêïqÉcIĞZp;Hæ\"Ö	\"I±;X¡í\0Š€óUé5@˜³×9Z¡pÕ‡\\E3C/œ®MPñT1–³•í%µüKS	d¾©¹(0ÁFC<i¿1VhX‘…XF±1QÄ¨šehÈšA¬uE†`]\$Í÷ÑX-•Y•w‡l³á¦\0à]¨µ|\0V›—l”UÛW#ECS¸²…` ‡\0ê{	a¶:šMbvÀÖ7•DÎ;£¿UâŒaı\0VÔ¹'@2P@%€µnPì¿ÏFbÅ ¼õbŞ\0Æ¦pIU„um'U²\0!ü†Z^<“Q@>V.â]¡ªDòpÁu£\n&ˆAªÆÒãJÉ¼-‘àiq…Ò/\\+ë©»¬ğ(˜™fPl#“µ&`X.W	\nÊò”J—´œåšÔf…jG› ÄX\0x1Å«(,ğc^Ë‡VíLšVn_åWÁ(‚²õVÁ×%<kõo5™Ô½ âU iÚMF·Vò+Åk5¾MTU†z†¦oubúÑóH‚öŠÅ1á®Â¶QVB ò/mTuW‰B*HjËüæé.î’Šğ˜ı¢¬ŒHg2hºÚš²gíš¬L ¼õw8\\mp\$*7¥q*T:\"’³åY »‡\\+ğ¶Jºo\n•ÚŠÒ¡ï2W\nF=tuÄ×¢‹…SÃ…“^EZ9°Ñ[unUº\0Æ?ƒõÉ\$|>Mo5Î×\\Å~Gï¯D}#b‚éW	¶bÀÒMÜò‚IeNÀ‚Ãl\0Ş8Ğ¶ŸbrYe\$Gğcm9æíé#@†VX19>gÆ`@…ˆ¦i`°ò=Ø@¥ƒT×%Á,À…‡Ï?“lN“šÄÁÓ%¾»„kÄU3<LDãÅ öCëÆÄù<pñ”Ü¡e€†ÓÄƒ€¼\nH'Äƒ¼PE\0\r@0ƒªZ8b7Dèüm\0:èİ…•AW€º: #V8[pH¢tŠBÕX¾‰i ; bÈOv/;\$‹5xKÒ¢Ô‰ò’hšrE`uf€ŞYD¹È¶‚üÄÜÎ„Ë/Tjiø¶@Øó\\ ì\$VÄe…•Ş\"è¨OgÂ16h5aÍ©\rZäÈ©dâ\0vO+X„–CÙ ‚àÁ¡B wátäğ\\–â²@¥…dÀ.á‘ÙL€6H©3e\0006Î¾WÁé3›8N8áLˆdˆ2HPà…¨k!r½p!Z¤ë\"V^[f!`iGBjıblB2øwç8¡´…é|‚^Tè;¡mœ–u\0Ê…ÅÂcAò…œè\\Ùê™hkÙåg`!‰]Yà…Ÿáß×tÍŸ6tÚgõœÕ?€égvxÙÍh0%–„~•¡ ‹Yıh]–V†ƒ»Cà6'’Œv­Ì!¡ˆuV4¤£·b91Üa²)ƒ7'Ÿ ÎƒIÚ «¤ÙÙÈ7 }JIî¢Ú^„×‚›\"ô±ïÄi\"¢\"Šö<ôÜñ´æœğ[ƒ8¡¼½#\n@abÍ®8ğX \"¨Æ×éZ‹:uSmÌ-‹_(ø„”ì449@6K\0rğ+£”TT´P´BkPÑJ€Bí àÚî ] õÚôFp\r–{køKÖl[ˆÄoîCgl9-à¥Ù¨ »¡«l@6É[\rghhTN<m3\0NN1äÖÎ€Ê…¹ˆ\\¡²Q”ÏÀ?aÀ€\0FHq\0€_iAµÖÒ7HÕO±[[O¢õ¸Ü\r‹èåØÄg…Œ¡2¦áZ“[ˆ€\r_R\\äKf®§ká2~]¡¤DË‹QÔ\"¸ïRŠ\n?DLå™‚H#æ–¸¸ağ¨GÙ%cB.±ÿØçcbk7„Pw“º&M2c¢––Ô'+Aİ¡VBÌZ6»ÚAf9äÀø!=fpn!*'&Xoõ;.^AØÏ;3XjF™@ô«`1ÑtÊ”kè[¼­åÁOø˜V­‚,!.Š¬<%!sŠ_3ŒU”*€ànR%aH;„)zî.qpßP•ÅXCz]Êú5Snƒ2ly€L¦œPê4@dëX-Í9o9¬@³Ö³àK–•‹c=yâ·¼‘î|Z´@ ÑÕö+è²µ}î5€Sb=½L®ğŠ\"'L¿N65l ë£Â\\EàTÒNàÀ\"*QÕ¶pÈÂ}!2a`€ãsû=Í[lÆ‚Æâ#ªbõ­»1ü™4€³UQ¾˜u(CQm\rÔ×FéÅbÛL“a8Âˆ±°YbÀ´ŒÔ¥;]@¾€°]IA¬ê.[<Ó]XÔwLá'S¡Í]0¯ˆà¡]o2»ceì8:£êãXÿ5O‡Â;a\$HÇ°ldq”„‰\$âì¡’wi‹Ïv˜±hyÑ\$°›B3ËÇ'Ô‚Ÿ­¦uèX \$lêÅBãÚÈ\nàY1]¼|ŸO<±,‹xìdªÌ0`HéJ¯Ú´øÛÍZŞ\"ëÁr;sĞ\n€‹Ø`¯Ø¿4@I•–ˆHì`\0Òd.€>B;=ÙNkÔqTB€8Npg‚ëBT]y4ğ¹\"†±)}İqå…‹ÙäÀÓwø.¤^Sxâ¥š\\’õca'\0’h*Æ¦†³eäK¡ä›œ&¶“yu\nµÃŞgyŒÂWo„x8/¡ªQ2¢ìÏÛ=¤¢8=W^€=ä\"^™Ò%çwŞ&…ã÷®øIÀ“\nnpD¢tÓ˜•vU<ò2İà¬]â)õíõ‹ı/Fö¹ZÒ/+Û\nròµÓ–Ü4máŒN7ÖÓóF4ÚŞÔ¾#\$»\rßBû£˜í…	¨<¸bKA\"*jp/#\0	AŒ4Ùv›&wh˜e\\ââÅ²©˜(ñˆP+Åö‡„Q9­‹ñ²&qK‡WÊ%Jêñ¡.7ÅwÍ÷õCˆÊƒú‘î[í\"-ááŞ):°Ù&5Ûõg¤gPËÒ Ãt{\ns‰Î.=¬;Ö“A*k:Q>\0R)¸?.£ÃX0³ÌèÃ¼¯I”W|š!yœwïÄìë±	î¼90¼ÒÁ¤»]¿xˆw‰šÜ¬ü#\nnª‹³›'xÓ\"†b€ÄŠ®‹˜É{­â\0“`x¦\0—‹\nZ™Å#aWŞ\n%N†7€t¬Šİñ|ÒWCQ]¦UõD·»œ•ĞYn…–pğèÛNû»¢ğ»'ï\rnèh¥ô\"j;\rŞ¡»,úñ°ˆ‰Ê³â·—+>Ç–	&P,.(fWŒ… 7Ø ob›‰aÒ\$®R&\$Á)ğöÎœI73\\Ï2ûŒÍ3¹æs6nK{&ÇAT°l“x/¶¬âÕø€—}A¸:`QhH·i™é#5ÆwûŒ1qã³eÈ+&‚Æh€àç{İŞ¿`0oX•Ô9›S`rU¶Û :ßCVß|[Eâ\\U.¬ŞäÔFµêß{}³ˆaŸ9ÍJI*,ŸÃ¹\rµ\rg&'0\\—ô_æèM¿!Ä…ïsoÁj\"÷û£‚2-wıŞ<}ÿ×x`€¼ø\r*§‚r»øá€dm8` ’é¢êĞ6*´\"èÎ•–Yêd÷‘FPÈÁî¾üV4–Y)?åF£ÓÑUÓÜg=À\\®;šHkx+ªb€Î-c‹X\0ÎÍi_ƒÁİ§ˆªàH*{áî-]è;%øØ€åˆFô€TT¾Äf8µ{X@ºx?S§Ø½,	„¤a\r‚ ++™‹/	€æá.˜»ªlI‡º*åû…Î°2Œuà¶f(RœÚ˜¸E·áß4iÜÄ´:LÖ\ná-ø¡]ô¼Õ.Ø'L|Í Õbù…â˜—cŠ@ònİ8L‰ò/Ğ9`€SMC¡€X:Ï©}‘¶NRx3ÕÖÛÃÚStP²¨rf* Ù9WH˜ŠŸN-ßqc÷JÎÃúÚƒâ†â«†}â˜Áƒr+S\rw¨NĞª¬³âäµóÙ1:Í°³ãÃû†½ÆÂOƒÌõ€+®-®sæ-b0‘™‚Ş4Dt×~¸Ë¢ÀÇªæ*¸³ë‹¶Ş·3r\nCüÉ²‚0tş‹³zİë·´!43ˆpş‚©!à+w®¨HjÄ¤*¤°Ô«£¦¶6B³Î8ß\0ç{6—±€Ë{.87¬ŞÔ0¤?²Â­‚ÊØ-ªİ‚\"Ş†]ˆy*·4ãà³PşâÌ{XÍĞºí¶¦»ÔqŞ7‡_ÿ€¶Şcx­WÌİôáu÷Ø;¨b9¡m2®½9¤j®T*N8µªDwğ+rZDL?·ãÁz‘¡½ãDFø˜Ò\$fâÉ\r ŠäTx66‚@…æBç“O³Diìù ;‹v÷ CŒæø*ª†î3ÑéÃİ^ã!ÀI8\rl	…ä€óıÍvë„§—=hğï«ŞŠ, µàAÙúæİªL‚1’múLË‚½XÜ8†?¢Ôa.\n‘ƒs_6áGrÊÁ+)k‚-oK1¬+øø'Tàeà+7\$ı˜‚Ë€†r˜€+æ½W”#sé¸G`i†¸TÃ¨3ğ6Bâ40C\0ãœ¬V÷Êšëëu‚CÍaªšGå20[YNc«=¢ˆy>ŞNqûFsšŸğ¼ç\0Á¬ÜüÍoìN8\rÈÑ£j@üè¡X÷:4g8…r ÈtQÉÉÂ¸ØCk -÷œ	:;eàÙiÌÖ\rHÆ´Re£–ÉÁ9j°ÆÎ[õ:eµ/³ó¤y•´æë6eu];,K®tè^åŒFYz±B¨à‹ÙäËqRëµò%#ËÑVñ¼îA ¯•ä¼t4ã4Xs>Â@:€Å–)2tıdÆ/\08.¬!8îcÒÎi›\0§•©“ª5r°qMÜÃ/d—“	Ch\rN—èf>!vd!½æFøx|š#Ë’fe'¢^	eµräÄ\"	\n‰³\\iY\"c—™ß&fJ36hò\"\0L`eH´œSk|È¹&)7šÀËà0âÒØ²ë(rè¼5IäS^-‡öQâ<À1„ã˜Í—\0ÑñÏ•––\\9^…3fHHŠ)y1Š§âb‹„¼Íoš¢–(Ó<`Ô-qÂg/4ÍÔ9&Aœ* µ	:ûˆCJ>ê	ş[n{û˜`-á¼ârMÙkæñ›¨-áudrß`d&CÄãÜ)/ÎTúÜ>CˆhŠé@iÊ×£M˜-èòª¢Œ¹¾{{`WAÃšp_£Ì.¦ëé‹´g4ùyæg¨½é\$¹éÍb(¸4k¤ã•hCî‚ßø<Ùå¶†|‡ÙçÎ\r¦}0DídKpóNşy`ƒ¬!øÎ†Ÿ\rñ\$và€ù>ØzöÆ¶!yë³’CÇœÛó‡äC[ş³ŒÄ\"Q LÖQÏyßgqx|SÙåc~uè äã…{Vy …ŞÁyæ;·Ÿè9{6ƒØñh3¡p¶Ğ®Å8©w¢d; ´mD²Ô>\\Cg_ ®vùàu{X9Òhv›¾…Èh¯yÎv'“å‘8\0èkN¦†ùºhrË0Ú\r†S¡@!œå{c‹*h†ÅH%Ú¿¢f„÷®è—zÎ;—Ÿ^É¢l·´éÓj!–‡5è¨&„w±hM¢¾<7´è·¢‹Ú*è[£ˆ×hÑ¡&XïèØ%Fyz2hñzpRgÒañäÀ%€Ê1(V¡f.	y®¹c>æØcı]ò(•8üÊXÀ‹\0Ù¤–’‰‘f4XÄÒñ€ |¾_d¯õ¥Cÿ2à€±¥¨< ,…sK(šZ`› /\\“,°h™{ƒ`ÄÑzáA…r9•:f˜Êÿzh[®“) ƒì~–ùci®\nÆ—AXP0b:ldIb:n…j\n2DbÿÉ°„`D…Ëm€İ§Dè²¯§0!™\"éDxÈW õ§ì:~`k@Œ!_^›w„SœKùvİ· ªêrf¡ÀÉÖM¸4a¢…Û¨tºFD‡\nˆX 7„&\$Ëè…vºYöUÅAJ,\r·ÍW³†mîØ» JáÈZˆ#¨–zM7jeˆhºŠpÓ`8š\"i\0sV\0Ö4ÙÑÜõoc‘.~H¡‚‹½B®0ú0B€ÔÊò8åê©h\r8»Şõ}Ù^(ÈFùè\0ßù¢ŞhÙÊõA…·©\$AĞ\nª,†/AŒ:qyæ9ÇÌôÔÊÏ—¥ƒ‹v#\0À¯©ú6Åù÷}Æ Ş¬W˜ŸA˜Î' 9bsªûÀãZêB’Ö­\n¸êÖÖ­£t‰pş®ÂŸƒ–¼¾z¹Kï« L˜±á/\nÈ”y¥“¿íîYëˆóÒ_øÜlÈ¤iXÃê´ks[ì[„İ˜öÕ\\å¢”:9y-Æ¤·Dík%­çÊ„¶kƒø[!­ş¬áAØ+ƒD¾¹Èn>¡ªúæLÙ®sÒâ ?yH§ág`IäCà†9¬0íºÄho­Æ·…¸í2Î·«“jÌ~¸\"ë‡©4ğábk\$ÂÎ²”\n~÷häkÙ®P†ZÌàÓ®¦³„’ëı®n®n#/…¬î*Æã™f±¡p_‚V~x)¯ì–úì&JEŠA\\Eñ|Ì…\0iÚÂ&ì>˜f:éÑ,«‰¬¦Á±£\$Ê?mÆµ×‰^(9øFù `D^àÉìo”í÷ø¶æàØ}\"¶h= =8|yœYTJATs2T³x~gâcy;Gãe~ÅÙ´áÄµi\rkk\0é°bdØQ![³@‰}tÓàM%kQ\rZÙ%‡³@{5&46›ó½N–ù¸-!Êèe‘N‚Ã¯ãîòKÖ¯ìşQƒµìm’(ZšoÇ5˜ç»)d\rÈh@ô~=—{mX¯½-Zæ6Zİ4d~,€Ò?°:8]“`óî@Îı8ªd2Å7%áNNÕÓŠÎÈEĞz‚`íèP©j±{µ;4²yaT»6\$DbÀ\\ >DXæÙI¼€Ö¡u–\$Y @Qx¡}\0{¶¨VR¯@E&ÚÑ¶à€¡Ô…i€‚‡Q=à.ÏûaóãÁ¹Ğ\n	¶­œ?	åG˜d+›Lİ‰ğMÅ…Œ¾±Å3[«\\ÿ”­°Úò½C¹·^K_‡ì¸Õ÷P02jP/ÌşyvÀöP\nûˆœ—Afã”n'³+ BÂF–uŒÑ–j¼¼6AB{cØŠm×€·²Ïµ]’îß¯R™'C.³•¸s…>×âQØvÔaxë=Š½\\¸³‹ë*ğ#Z½‡[¶°=¾Û†*°Xkä8ø-æ\0wC\0ID:²‚\rÔÌ¨ı)~Qqlè%šø™|b¨X ›t@oK†”p§Š˜¹fL\rmá\"*P}B¥ŠnÇ½şAÑ7úÄÌ÷,Akê¼%íd¸ĞÀ»‚b†j ­’ç¶×t€]ø:èH¢V:©\rjŒê=nÎ!Ü\$qó”›4‘ƒ¬ç0;@ †¶›aOÃ…V/ ÔN.BN¾xd¯E¤±q1†tSUrÜË…Ù5…İo¾6èˆÿiQÄ·-Ø²B¦â˜ºnuºŠÚËĞFKaTEò!…´úƒdM¾\\F+%¥M>…»¶B-C‡]!Œ&Ëş!@Õb±­ˆk3•Ö¥]xÂd\nÉ¹_‰wo¨KÌ ×M¿eÍ‡ã¼PÃôVÒoC„€DVÚÑ›¨A›‚ì˜fˆ½x°ä,˜VACØ˜	xµÙ~ıÀf	øâ,¨N|c²<&¤`ŸH‹Á(Ñ\\H* VWy…nÖĞğƒJ£îâçà\r1\n•à°'ŒßP•Ğ01.\$ÂË‘*8m×€…mµ«dN˜ôŒÂÌ	*ú¬-¸>Îq¯ãñ\nÚ&oG³wª“!\0İ»¥BıV¸½ëC­¾†	Ëü4Œ¿¾p\\67»û^d¼(¤¹Âí4-€épšñ¬rÀëVÍ”¾lå•·µ{Y~½“©çV;P!Yl}—™\">½^s–‘ÇÄÌ\0³âúîXÕwWr¨am«%Ø<º{æo¤àƒKí0ÄèÒc<uÎj}ívC|Æ³ŠSc(ævêÆ!‰.¶y¶ò\$m¦.eu€°#epZÂÔV'±ä–d:ç'“gŒÔëŒÒîê¬AhñT+/ÜRÒÎKØGYK@&ÏÛ›ÔV5ÒÊÏÍ^GÇP!{÷-Ê+Í¥MtïÏrâ&³pà¶\0+«o™Ãı7€ÇWÔˆfÊôxËìc€¸½|,ÑòË 7hI¾Ø®JØt2Ï6e›¢ïàpÙ\0Ór=:˜_7ê]áCËQùÁ®÷˜mm;Ób†Âß# ¦NxAûaªP¥ÈÛ -òo>¿&“¾gM{Õ;1À\r€›ñ\\É†ß	ï{l„´LP‹„-ÊM?lA»Êp4´tˆº½Ôã­üiKÚOĞS\n³pZ—m¦M‚Ø†Y0AŒ	ÀÜ³;áP|à^çßÂ)gHù„KH,l´á\nˆÇËd¹ß`ğo{¹E«HLM ‡rÉËKÁÜ³òÙÌ/-|²ğÆæŞ´‹ÛŞŠO.iÄ’¦/M\\<¿l²*'0(GÌTà€>\rç²H˜¶ÎÌähK€{Ìm?³!<(¼'æÊ÷p”ŸÊ€`6 ¢a0&×/¶ríBXbnÀxH˜ŞóW•×5ÜÛ@×À‚à0›¹«÷/çs,\"ìZÜÌ‰+Íøx<ß9ø\r‡8|Öók[7,°ÁÕOÍG G“\0É;CRñ­°‡=ÀKÔ-0¤? ´4qş÷É#|Ü`0ùx1IqÍºE#ƒ„vàøSÇÁd\nÌçâB±VæûÕò•OkÀ<?Â³°—Ü«	\0l8’ø5®ÉÊÍûóJ„e™¶UYõ­Õ¡¯Ì¬~ò®¬€Cp¿öah‹ş\r§ñvÁÈÙÃ‹Ï½=Âœ* JlD6°¶á«ŸOØŠ€+g<ÂÕÚA—\rãóèOD)IÙ°ì}äÉÈpiŠÃKND¿EŒÜ½PÒ˜÷7b³òñ<‘¶Ò¤ê¾°å\\Üòÿ²Ğ8½\ns­%É\"ß‰’#…|÷ğÊ*ïI\"NÅ˜32ÔüÈC%0»˜va÷/U8‹c9@c‹ÓÔlC*Pœâs·°ÖlœÛÖE¦W@ ó–šÿ9DÖµ<Ñ¨¦J¬ û0`˜#ÕXòmĞÛ«QÈè×âÏ»ú\n—ÂÈûáSp½µ(?Cvë„\"vû¶am\nu“Tkc¢ìı	±ŒWÑTã÷={Óìı í(âU¥LU2\0~\r_å/ÔÍS@e™&B \$6ôşYô!©-UÛû…·f5¤G”ñ+ğÁƒ989mÌ™Ô7ØÎ…\0Èıo”4¥¯œ1¸J—„Îà‹òÀªÆÖıcu¾:3Ø\nRÍo…îÖï_xƒ¾ä€]v	µO„<\0ìYHÏNˆÑlB§ZàÏ…‚?\n|´š©ˆ½lšg^'aëæà\n­~(\\àG~år†xÑ&ø.So‘²9@Ãræİšwµ¨hcâ£Ä„ÔÎëÖ8Éô\0¥ÆÏa`\r„\0ñ×¦“ÃpJih®É=ôS«%ç…äÉ“ç‚%¸Ä#p°3ë@7vÌLGm\0000öËÚßm­vÂL@¬˜våÛ?näÄ2óÚ÷m]¸OÛomı¹³Ä\$ô3b8yW›•†÷\0³·°S =ÎÉKç\n	<3ŸYİ™Î…ÙÀŞ0n¿r‹’Å¸Âm1°ŞD5\nÆátìVGl·¤ÛKbS„ ØŒvŞcyÕşu_n\0\0l‡vâ7–‘`Ã\0cİ¸e\$Ş‚ ;‰S(šßsÓUİİHl•I£‚È®È	¬<V}sàW®\"y <\$*–·xâ¬ë¬N½±(?•ÿÜ·2ø}ß\0Î’Ò¤Òw“ßW]á€ğßx¦®1ß·w¹÷t€¬³w„(muL\\°„à^|Ç¡8˜ï«õßT—^]¨à`ÛÀø	~‘\rÁ\0b|cµ÷Ü•O§w›\\µàÕ;Éİß€fàéıÕ:TíÇ…`œÖndÅWí‘j8!²w\\*Øa]IŠ•àç†C¾é¾m`ª!TÇ>UÒpÓuàÖ>à0eØDLG@\"@#L\rdßsËîtßMH²Ûécu]65[æì¥¤WøF	x¬^Æld@û3%}ò€ßMüÖ»îñUİrŞ5Ùùãlm‚—Ó™&îø\$\$(á1:Ì/B=øØŠGÓ	r„kÒ,N¦îĞH#í–U[‰äIÇÚXØ}§x%‘Î×£{“ézª†NÌ±äùDeüò^K—ü0owà7xCW\nyXw×O~IónØë&ñ0y\\	}à§:ƒ8–àšÄlÔ¥XA ‘DRJåÆ¶:XË	éÎ1Vøº‰c²›‚2ã‹Ş2[ëzÅrİç»‡İ_z~² ‡v&îA/İ¯vİû÷Ä\rƒ?6wú²xê\$t®]âç\rŞ€«åwëWø\"‚­›^•=oá#\0Ëáwu]ûá¸c~‚ÈÍ_şƒúŞÀu²w·’mP\"ê;§€öø÷,Š:)+ƒ¾8_½xØ\r§‚H»¹â «É‚·²· u:…ˆ^á'”zU×`ÁÔà‡meñêõé+šyõSÈ=@6úsé€Öş!ƒÍw7 Šø“è¹‹K	Òï\nQÀ(ƒ´BÎä'†E`W`=XxµOà%y™c_›^0Wìf’]}´\r¸ˆÕ|Îp±÷hbÏ~^¯\níƒKêöaZIuM¼ÚŠ-…Õ7XIüN¢#§Áz¥…±Õ­äg°;^©r<œö\0z½yÃMàJ)Ø]/ñUl[Î\$K€C1 †bÜö\\¨`÷¥¢r! 1w_2A\0â’ püQª/’'µw²*ÄUú#ç˜ˆ<Ä\0¦;'xíö†Öh+ú(€Õ8A½É˜9^ï05ƒË€ş(vÈ¢+;¥€£ˆ\nù'À§Ùèl\r×ºíV®õ†.„¸†É5ÊØÀ©-Œ·Ö—sç™\07é¨w¢ù\nŠL¬4I°à’l0Âd_Oêi@p¶NW‹Vhâà(ÔXÅE(\"ãøáË»QÄ*¸±_0sÁTœ9¯ŸÃ\\×ƒá9WÁ^¯Âq­oçÏ~ƒ|­”¢¹æc«qüÎ/üğÿ?\n|Fø'Âñvü'2ˆGm1ğ‰Ä¾ø|D¶?Ä¡ÉM4¦À±_°ç×Ç\0EÛ_ÄéˆŒş-ˆ \r™*¸8Çº]·(‹â€nÑÒ¶'ê0&Ø¬¨{ëÜİ¿^!z©»Õ‘\0Zâ³p Q™®ØHu·^ô?¹fá\0{Ùjù;%íä—|-y-x™Áõ>ÄÀŸ^9j—Çùsóª¨Bò:‘˜¨=	ê@E\0iÈ1¾½\0ßëå\$|¾]Àš#ö¦ø{(pÌÌ›H`°™ß9ıä§ÎÃaüğuloäf–š·ÕaBß\$p€äÕÑj‰Ä¾k˜M=A×EyÖƒïÀ2ø¬yĞ+}ª‚d’o5[ü„q/¢v}ıyçæ€–\nÀÄ@‚ÕÑÇĞ¶A|AçgÏÕeü=òiÿBQ±0Ê,-[íHL{iÆz\nóéGØ`ÿ	®İÁÂº-ç½ÿ\"{å	]yBŸ\nì¡­×ıİï\0cbúöWÚÙQîşÎT'pú0\r]ÔÆ*½íâù=Ç.«œ¨ä¬#}q.ƒ˜\0‚HEyÉuÃ6mÛİË‡ô„…^ğŠºÅMë \$Qzî%	²­€\0’ÃÉ¦‡ŸWË‚š¢Ïú2 h\rß–±>	x\0#ŒYùø( )‚äˆAX{€§¸^—e–¸‚9úx¾C}şv¿àVc~«áçç€\"ŠúÏê«ªp‡à¢ş¨%S@;şÏûOëÕJˆ1ú‡í?²ş\\(6~¤~¦÷âTùôÈ(€8]ó¦®¡icá .ŸÀ\0ô¾\0(!\\€ô*Ğ\r¬O†h`	ÿ¼ş~*ĞŸÈˆín—w¬ÿ-øÖ• èÈš(	¤pRÅKzx-üÿóÌ&ÍWw­wÀOXæÍ¦.#Rúd\0œ³\0qı¯0½‡­klîVvM™å]=”¸½98A…´R*\r_4­Õû²_tÿåM…GpHÖéÉêÖQòĞı°R‚ö|4êÅc3A„W(\\×¹¯«3c+1”TİÖ:¶°JtUöÁf®À“ğp•xPØs%l™0¦+9õvUò Ñÿy™!#ggŞî,–SöâUà‘B1àìc—A üñéâ‘I±¯Èx‡eü!P­`-×Ò\0¼_ë3¼^J¡Lmè_¥ö€ÌnævfY¸:[ıä<FlYÁ'ylÌrÅ‘êw@ôïÎ¬\r*EzjQş3î¦ù/óTÈ¿Ë“\0ôĞà'ú±¿ÙRXšhİã‚@Ÿ‹†pÔ«\$FáËÊXà˜êBÒmº\0\".%Ï…~Ç¦û±â¸€øP\0â‹%ªXtÈ÷ÿ†”&¦?o2¤BËĞ—¥Â+ò£_ºªìõ;¥n—¾¿B¢«‹=F!¯·ßf+W>@ÅU8gÚ@[ßjFA\"r<};Å H0\"ßj5A#ÖP8SO§»Ik‚Ãˆ«qó`ÆìºœC8RtùÀdiÅ¨	—\\>á:Eí+ÚwÙE¶ÎÇÉWTÿà¡@'¾·_@@„}¹^BÚÇ[Jb@šM†aŞCı#…@ê\rÂ¹§)èdñ/–.tdúQ7j°3pa4@.€-€Àû+!VQwOÂó>:>*`kæaˆŒÀï¿åXXÂøıy6`€	Ø*şRLŸ)[üÅ7 LãŞ¶ Ê»fHÃÍ²¼±xBP©à™E'—ïèÿ<²wd‰¤‘¹p2\neo`¾í·5ïìgvP\"Å‚ı`¾›M·æ0%ˆrÜ<H8Ø4|×–lÆ^LŒê‚>•tÔ	H\$an ’¶_ûğ]ª‘˜\rá€_›“ÖøÌ¸8m÷¾\0Ñ_\$:¢¬×AµóğØ¡è\rbOœ_)Õ@—°FÄÕP'€X&#I¸…%©aOo=ùc*\rP=àc-£§*ñ(`Í¨œÒ½J	·>˜éÈŒ”¸,„LBÖ©ílÂåHÚ®å(àŞİ	:iN”â˜TƒŒÀ¹ÓĞtÜ¾H>Ü\nÕ€¸½÷4Àq©×ò7Â¶BŸ@‚÷+eœÄP<ä[ª<P§vŒëÕî[AxÄ 0=/Ré¡ËÀ€ñÀßÀ*ç®‰ë@¤ã¡˜C1’ºüê0ÅÚh¥áŠ“‘ÚgTt³bR•\"8-¦Bêr ¼f´K‚Z8,ØPÏl&÷ (®aÎ\nNU¾Úè/HZÖyjÕ€ıxş«ÀuÌ\0005^‚ÙXãµâ~ƒü ¢ ““’“Áö™i»_+°yË6)[\0],€Ã§bí@@ÛAôªÇ?!0Ç£Re’`ª©3‚¨/ ä§™mw\$É‰ûØ@sTB’\0¨X+P©psA\0ÂR%Ÿsœça	è`]91`Á é\r\0T!y<Rµ¦ypRá‰Ss€>¢^eÆmé˜2s%¤™¢’äq ÄA©©ƒæZª™>î5WˆAò@ ~˜ş¸88IöAùÁşe~˜Èè=*dAÁøÂî=Öøg“±Hç8øè\0\n\0- †ö\ry’b,”r{i1Î„× !±—ÇšAHŒéƒĞá&ƒëÅãË‚|3\"ğ`ÒxGPË6\n…ÔÕL'2ÃGÄœNBerÚŸä¸RDŒş´¯ØÛüQ¦å›ïBu„Õ\nlÍÓö¨3êÎ!>R…\$3¤¸‚\\PÜŸs\\±é¼M}[€l‚ÖÉ:K­øSÀ¤U{ÂÃXá±9d*ØQ{!T¤1„	»ì:Î Á¼.]µó0×Ê‚7À…\0*x A, ğšŠ!a&E\0çY¨Ô(˜WPl€ÜŸ2‰\n>Ó¯°l@EA!‚Ä\\3ÙUK€jÖ¬\r4  › ¾@jÇ‚áÚ§qyòğX.ØBmR^4ŒıQp*ékÄ©ö*–¨ğP9+pÖ›%òSß^ÊõØc†‹ˆí¤‹Ì™.‡l-˜F“´é)MxÒp Á&F‚ÁTìæáFÒf\$‘ĞÊI“]P{UıyĞZáá¯0´¬]’\0Fğ•;Ø¸çÑÀBVo~Y˜KNË¾ğNJÒ@¾?4 ˆÎy6·xÁÎ‹ºAŠÔ;c÷TÎŞK«`‚Ù÷‘‡ÃAgÍf€yÉ¸;rPFÀµ\rÃ¾î†ìöÁÖa\" Ë\r‰{¸gØ<Ğ}ğ.¬Ô	¢1ğ2ôò²tö&ëá»?DÔ3nT7ğŠîT…-; –á#³@cèÓ§.ÜîlšÖ9ÂÄÂ²Ï_pÃB8q^ÂnŒ†é|¢äßZ\\0:ÉsÒG+„+|ÈÈĞFÊÉB&€/ú¶İwd»C¾€t†ñuŠèy°óÃgƒ.xµjAÔsª†	5…ë2x ¬(ETŒÁÁz¢MEÕm¢¡½aRÊOLs¦è‚²ó!+Ñ\$ÀI|Ê ˆR4 à“†º¿\n‘¤?Õ#A€X·k^C¼xñKŒBÏµ	- °m…A ËêYí¢“ùC şa¦c\n>¢¹y¨BÄ·AÃÈ^º2zÔÅ‹ğkZy*0²ÁÃ÷ˆQ\0`Ò‚e†ÒBƒc%­Q\0003„9\$™İO”‘ûğôÚy2™.6xQxéuc,¢\"W‡:ï  WdæÖŞ\rdÆV\"h?Ä¬á˜€„¾™\rî2JX{†tñ–’W¹*:2©'â2 &Á <F0½i\n!(7ıd²Y°K\0j·sì¦‚àa¡Hl“7Çç”d…ŞÒL!k4Ph¡N*>Ùq`¿Ån™h³¦KÍŠh¯j¸ª@İ€¡+ï „/?4\n9‹ıW`³ƒ>+¸rdD;Z<Éõ	Œ™‰\nëˆë—Y–¸Ê^F~z¦ƒ]1â3ˆ\nŠBà¡Í8,‡T2ëÑŒq/\rL(†½d¡rµEêdF±B­ˆlCT*z’…W`“”²/Y\0\\Øä1ºd™©ê‚>ˆˆ…ìî&¬Mˆw@QC(ÄÌ‰Ö›DÀ~£RñÖ’Û‰Ó¹-´5E©bw#\n‰¯á24J´t€ƒ¢w#)&h‘Ñ;E	[\n=Ÿ°ówáfŸaü?*ae”â”hÊJt‚eP³aù@ €!»–‡œÓÈë15£r@Ñ¼ˆ¸œ±Î!bVÑ#\"ğL¢±Î,Á¸dØáˆˆàT¢E'Kî¦4ø•CğàÖÔØ0ÂÉì;A»U¸¸­‰„ä@5Ô7ø§ĞŞbYER‡+ÔuÉñHªÑJ\"¬u.\$¥Å<Œ9³­ƒøàR†x¶Âtc™X£“Ô¤ÃLllª˜X¦áRå½còâhG3ªÅñÊâÇ©¾WüÜEzu SDA@Á3Adæ&’u+c\0”ğ‘0¤ «ÁÎ¸±êb–ä…IÚç…’à¨³³ˆ/eBí@®\0†r`qgâÊ*6\\g6)2h´ğé\"…C¦}‘N¸CŠêrõÅ}RLDŠ,¹~…0èíÓ]Dƒ&27qğÉ8“1.f?øB{õÀànĞòäsŠ’=¯`v~+ù\0·a€E€)z#0³;µ©ÇÒ²\$ØĞgš®0©4nåª·õİ‡@´\"…0ŸŠø]ÑˆìF˜¯'ÇºŞ^,lgC©ÀÜ÷ÇØï 41Á@aƒÛ\n»éÓ0\0@¾\"ÿƒŒdÔÜXpgÑy•Ë«Ÿ(\\ù{’¬èÀë7«ã9¨Æò¾ˆçëÆ±N+,+ÀmšÕcd@Ï‡ÆU~'LèÄQ‡³­UˆÜE„q3ì•Y`Ód³wÊöÕô&ãŒdÀŠ½ªÊzî,cÅqÅ%W\nı%S‹\\=öiYd<ˆ,|}9;¸‰¾Åp¯  ñPŒF1Ìcç¢kÁFŒ¢0D`èÀ Ù!‰r×\"-J<˜Í £EŒÌÑ„g8ÀÉû£2Æ	\0îÃ\n0lgHÀ*–c>ÆjŒğ3(è˜P¼‚A•Z7´r{\n‘Nkïc6`Šö¢	Vj”¨º&%Ÿf•Š0¤hä3Û5àEsaÙñ‚ÜhÉÈ}K†|\0Î<R5C`…‚\0<EÑ/4\0¦Zîq\rçé#Dœ:¶T’/8Ce«ÃÔ³†|‹²\"V6\n (E±©•ê«]\nnî–Û#HE¡©FİŠÄ{ÀE°˜˜«…K3¡vix|J6Ü9ÀÿëÛRş/å”,è(uÑ¨ıc<‡ş jø•b¬DfPË42ˆ¸„Í€æD<bÙ£`ã`ÁA_²&\nf4¦ú\$hß`rCREjÜƒ ±àqÂÀ„ğ‡~½t3ä/\0\$ÂÍã…D#RšTq±¿ƒvcŠâ'x/:›è‡\"Íâ3·â\npuJ’€t}JhE„0iIB›Õ^­”’©Š\nAZäs÷[Ë’O‡lJV¢:3z1ˆÇ´•®G2\0L¥õO³›¡àq6‹µ\"àlÄ¤l|ànBÊPİbº:4w\rñÈÇï ÚÀ¯¬\$A\"|2bÏÃßË—Él¾Y€µ¡²íNm×Â°FÖ;ñ?(gh¾JƒóÆæ=ÆjqqR<”pğåBÀÃşˆ\$:œŞ¨næ­£\0Éj„=Tb¼ä&@ÙóEg:\0â;t”Æœ°:’…‚«¶Íl„2Rş™I@Ëà‘ËÖBl›)N÷‰.`Nèòæ”»GÙM¼e7'ƒÙŒ–w¡ÉóEØóÊzÃw)lTÏ-Ëx&†ÍP\\ÿÇ0ø¨º,R™áÆRG\nõ\rFÅéÆÀgä¬¦tÌ¶©x:WŠäğ\"g±>íUâVš>ªñÖ\$€ù ™¯²a £€ê¤®!Ğğ˜øcœÉX xƒå1cöÇ6‹®\"V?«òq¥‹ÇbTÚopuÔ0ÃÒóEFŸüGPS’uBpArQåBl“/¸Ç¢’TU«Í#P\nv'j>‚Ø‘ò\0Á*I« mMTM5;ªŸÕ3û”˜ì\$S*©ObG”zE0™€p!Y]±¬ˆN&ô#À8¦È€íCˆƒ”,ˆs9áÏáV¹ 6ù!ÇÄs—™I¨ŞGú¬êè³!ÆÃ4 âì?ˆ~0‡!@ŞH{!rB\$…àÁò€ÏÅÃ\\XÀÄba!Â@õ,p8áÎ.©h`jŸÂr#Á‹¤ /–+¤ÈPª(:‚ÉªÁĞUÁ«3w0é¢	úîbœŞ•¶ˆjéöCS§ğ‘àl­K`Fr5°\"p>ÀluÈ—ü°B¤‰0P‘r\\CG‹!¹\nbı…äÂğ¿1P£Q¨ë {AÈ­%8|™BıŠœ\$TH¶ÚöºCjÀÒqD.:¥li!øùĞLÿ@µÁ‹½Ë€İDWª¶ØààY1Åº.\n”|ÉìcAt´\0†ˆˆË9‘ğóLeBÈGíè®@,ùğğ2\0‰W}3#ˆZküÈåQÿ½‚é_·²F D¤¹€ßäh2™ÁZĞ¿(QğË€9Š\n”´FH;¹ÀÀ×ÚŒk0õ<5ã²BÓV‚\r­„ğÚL°>ªÿİİ«o0Z!ô¬%ç¸¬ÆO˜§»z\"\ràôÁlèå'\ncI^*EQ{è÷juÏ(¤Ñqšâ]’ÜÜÕIIÂR)®ü2„’ŸBWµêzÀ±Õ™Z/É(ÏÖ:Ğ¢ôd«Í‡š‹VÇ¬oL`ä!÷äd¢EX¬IAîD5GrÆ_Èògšjğ^gÉI¨AE	è6¹*íLV>’ä?¹a£<`lƒd«™oÄ´ RóÂÃÑ‰§®ú÷]D‰)(/`ó ¾“ %%Õ)â—KíÌA‰©ñ‚vL™ÉPr bjEÍ|Á:L'&*7C¯¤å¡¿À…¬6dşİ*ø)ÉWÈ)¯È ÆÑA1sCÒ0z\rŸ	:]…<- ïàî\nÄJÈ%>Jés§„ªÊİ¡`r°EÙ8…®OÂ3o<Ò`Yñ©üY3#jÓÄÅ€5½JÖmæs =’„;YÅ‘a·D¨&¸PvÿN\0Ü‘|çÊïÃk´Q%	b²ˆJT	¾”1­A)'áÀ«¡`)G\rjú¾KÉĞPPò]V?‰f®`9šÑÄ1ı§AeŠ“Ä\\°‰¨.¸HAÚÈ(—l¾üŠL h…ÄœS‚öÍ ÔLW=¥\\\n­‚63øAœhÚ¥‘r\$\$À5]€üfx}#™³Ò2†C*¦×YJõt°Ëô\\¶äã/×“…R»|¡àÁ†Dˆ€+:	’)hpKKà2Š<JIbä” 0_àSÌ“„èĞ•Xî0·€æÁ{‡èY!)v))ÓƒæyBé–R\$Õ)0Ö!m`ùª¬@\0b…ô-¨‹H™¢¢Üf\0øÉH[èyMâ&¹‹r±lG‰Äğ\"ÁaÔSÀİx9Peî””.f¢ydI¡§`ĞÍ×€k•\$R(Trm†û¿(€¯B…™1¸ ôI‹	&4yfÉ2iÜ»”âr˜Ò1¢Ÿõ\"RVEÃ?c*¤q8°…£˜5€Ü˜NU,šhÁrI¥“R\0šM@©5Ri‰/mHŠw(wûŠäò®\08Jº–×È/<ˆ=B\nÓĞi“¬04”¨3¬€!µ§‹{'|•ĞŞêòyà!Ié‹-dÉ=òºl2é‡÷	mNè^_/âO?…‚“\rëL¤é7àE†ù)äüp'0÷§ƒJ^`”Ü_Ü<*ŒZ2¥ˆÒK\\8¨…h`ä†L—,Ù*èšĞYs³íéËÂ5;d–BìùI’ã<‰Ä„’È^Öô)(W…í`>Êÿ-í¨½ÈæcÀåÙ]BÈ:¹,èí\r_…®\0006@¤zğ>Ì½é–d%ñe¡Ë?U88A^d´Âò’¯ä5¼Ğ\0Å+ñcàb4BÀˆ­»n&zE`7`A¯nßË“zÀ`(\0ºKdŒÜñÀd¶á€sÃ€pFP¬\n°ƒÄ)‘P•€EÛæº<™Z’µ™Õº3}*!…û”A€PUy@Iø¨(ğ¢Z“ğ\$¯(§H©4€ÚI&åÇ&ª–\\¨§NÅ²ÂÈJ–Ê”ùNŒZåRè¡ ÃÃŒ«Ü°¹P@ÃŠšYu`§uS‘#ÙDÂ%/0)¬Aø–úƒñ¹œHã¿û—jú†]›Ñéu`èY.i*FYDz\"ÑÈ¦Ü<G~@vs©r¤tåÔ¦\nXNØ&\\ìO\0‚œeÖ/W-(ÆL (\\p;Ré†#–\\—ºî@Hù|¨%×9hsBæ]Œ»0½²íVÜ¸Ü%Ü}[Œ»£Rg8JÙB)§.ø¸NÁRğˆ‚ÏP1.Æ`aNà5 —%ä©g—–*^t©	dãáµ†p—¦¹ÊñJ‰ƒh¼\\\$¬\n‘Q.9•¡óU:‹¢ Œz\n’ÿä©”N#÷ÏÌ›—.»pl¹¦P²0@—Ëádb]•É~‰®G<ùKDuÁ)à(ê8¸<Œ!mÊô½Y‰ävåÒ’D€Úğ\$‘X(”jUÖ‡—\rJé[ˆz\0­©æ\rn¤/.u%TeyÃ@NR(œy1¢>Ğü/Ÿ&#¾y”5_”Ä#E LZÊ…¡\0¿,¨„€v´ãošDÌ•İcÁrÉ	¡U²«Î<XZ)‘c<‚ÓÊ“*\0c<½bäG«Ïcd'f4TáÁí€‡\" YUîS¢_È¹tFleÚ²†)–1¢ù™ÉM€Á¶‰ò*¯ñDˆh ›3\n¢³4\"´‚\$Ñ\\™\$§ÁşAÑJ˜n£­ÌÛ˜CÔ¦bÃ±Ì„‹ è³Ã …ƒ\n\n±?6Š²8sÿd-\0VŠYƒD˜epğ'Üªv‘ˆEI¹B,.ªåB\0	€Æş`h©Ğ‰¥‹ÚpBèÕ,®à¶)è[MLz\r\0ˆ¤ †DF#¯nW3²g”œah]f„À{\$·3ÕÅÑs·Ft„VÌú•ÄÇı~ëvíÓ@‚1fŞ0ĞY	,'AP›¨~á\$UŒÎu^`®íÉİ45|‹f‰Ò¿‹«Iô“68–h	á3ƒàVCË³‘ø İI{Aª—åË\0]ú¶Ü<Ä PY…ï†ª´–†÷Œ†‚²%zMMa,(25jbºh\rA4ª¶|!`S*%”¡©…ˆ‰„3Ø.AŸÅoEñGŠÆ/Nğ†R’ğGpˆeÌ5Ípeõ²k?%» bQH€8#”ÍB`f6BÄ›«\\ŞòÚj*yYr3RkÂJ`ß)IäÃ0¨2—òTN\"YèùlvO3SB›²‚!<ŠüÓG6Å¾‡ŒHP(\nü¥£PòĞf§1\0zà‘c3ÒÕeKa’´ñÎC¤¸ 'óh“Ì•›…ÎVfø\\I–îiÍ¯3Üé´.#p`ÑKË’¼’º¶Bm Éf>æ—¢“¡*=¸ÄÑÅ™@g„‚‹J@Ğ×ÊÚ¬s¬_\0°P.—qÉÅÉ¦CC…b/ÈüËPëlÏ¾Šõ\0Êw\"dÚh -`ÓÜ¸9ìøÁ] ³¾‚§KÕ-ªo²ºÇİ‚òâËĞ\0¼¹påà}dŒË¿µrA˜zÉÙäˆ‚sX‘Í¸b‰”üDq3“Ö\0D^L4Q+aßÅÆAg-Sşì0a)[3'îû!+?¯*O¢Ô°è­—ÉG\0D”ö„M0&¢àDE§	¦K¤ÔêSø¶dÖEI£È2#\nıø[H¤Í_I]@/U®Âq»Úø¥¸äq\0!İL¸sFå†E\0…»lœÀT-´¨7i»‰kXó±:êq´\0û\0ZA)ˆóv`#Jì¬`H¯í„c²Iœ|4µR[Uéëô\0Ú.)¦z\0s€uÄÄa¿ä‰„mğLƒÎ¼Œô:!Fàh \neâu%¼ïk@TæLÂ…73 zz%¼+zİ§DÀõˆ˜Ä^\nÌÔMÀ@–ˆÎlÄÄB2¼¨yÎ#:XóN·ª_NsHs×Yb#˜rŠKaĞìdl+ÀL;˜yŸ„ÃÈQGµÕGÔYÈöNøçÕ’+×´Ã½BoDVı ‰ı\$¥hqÆ n‹åÈc\rj¬F—çN°™Æ¢wjõû\r°Î!†µb;ì<#1V’é•‚é7Æú5=‚‹f ‚[ç?Ÿ\$]¸%Ó¦á÷ƒïO\n@œ\0¥„jæÔ¦aï.ÎŒÊ×”H- Ê3ÄCäí„˜ë˜¸-DcƒQ[½Bñ×iĞ†:,n`/“	\\Ìr,sÔäæ‹-\n˜âì7´ÜL*QòB\0bo°Û/:…r¢¥‹íŠM±\"ÊÆRûq2GÓĞ”X5*?^ôb!‚,.-ÊüÒ 10	ˆß‘¥©ƒ±2®kÚz“kbKÜG“4G^Ñ\$nê¿©+[Ô¡th,Ğø†‹ç¡5‘ËV‘0Zbá~ÃĞIÓsf9v¯Rf*İ@t“¿AÔy©‰ÉÉVXø¦¥çANTäTy‹Hwì“ÍÃ.Wjâ×ı«˜–ğ	ÑNŠè	€?!>I¢ÓF%­€ÚéÏ™œ”Ô-ztøà¦èpÑÏ‡5X3Ñ›ğœt™MSk¶v0üü.u+9Ä¼aOGÎdh(Ôù\n©Q\rãgw\$Ô\\¼¸±Ás•%SHÉSå*ÑµìüEê\"‰š3²©D¡¹”G;†9^Ÿ¤¿@Ğ@ÙùM€^ÊêOú.<dşR˜b}‹œ~bàÅĞQRÿsÊUD1zTÅ§ğY`ç˜”ä5RS Rñn@fXŠ¶Ğ‚’ÌTC<Á„Re. eLª^â>)BŸÈP\r8ØÒ;tAgBš-:‰^\0Hæ”ïÖœÀ†ƒ6‡ÛA¦.D¦ÈJäq÷ãP.†(Rˆy]F‚T,¼ò)&PáŞt*¨Ş’W¬@7d5pë  € ÒÉ½\"CVôô\r@4\"Ibè&g œİéêh˜<AÕ†9H€“Zj€p[õ&n›É ƒ\"wáùÇ!wh	4’jB‹–Pì,òÈ7­b º%xŠÀ‰\\†à7&T:èêĞ^H¹¬`KYåØáù¾ õkĞ†B!(J:À}‰k‡IyT˜#³ xøM%jÉ'Ó8fX@Ş‰ÅÓdh2H‰?®èé­ª ğ=€ÌG‰OÆéØsĞùR,\0Lgş\r3™””h:@	ùÖ!b\n.â\\Ó«‹ú«³èM\"ºB­3JKx Ä@£-PYIÏ(57wìâà™¨‹¶†’ÂĞ‹xˆƒ ­€O†ŸÒ}b'#ŒÄèk†— æn(ş[¦ª\n¹hp°¨‚•+à6 \nOeBi€0ŸÚ5ì©@Bãß•ô\0¶\0ü0kÔıB‹è€€**^Sx¦€óœ€6<t.–‡\n¦–ŞET€#N?\0x€ Pî‹\0Ièòñr ¦õ({¢JŸµİUa	ğßAnpOá%-æÊÇVlNÇ„\0z:b\"†2é)SÇ>Auxà¥2TÑ8k±åP)CO:2rQ)I”‚†UÃê09ì©3MjŠâIEYR†R*ù“aˆâ¼\n±ÑUt‡E^l1ºğ\nÒÃ‘¦„NP&˜Z¢ßÀ°PÔ˜gDdbÅ–5ä¬7 ˆM‹õE–=ˆÇxâ JHâ+*c¬(Y6¡ÁŞ‹ÛÂú.³P¡tcxTú+(¡¯+â=Ä>jkHm\nhÄ•ê#º¼¢Ù#áïãÜ\r×€G}äÈìİ¸7ğ+]§½¹‚<àèƒO­˜Ä€uúŸ„uÌøS9áBY…)B+(VpRùh¶O\\æ<M26¸VôÅã@Íã/Åär‘²ï.õ‚‚eğÔŒ@²jÂ=ƒFJ80\n4áx@Ñ†‹£,dÈš6TEÀ*:“\0æp!(£üRŸ¬É@&ˆñ!P£ipE3‹=TóZpÖ‘:BiŞıÀ\rÌimfK;20’iXÓœš‡\"\0¬Ï?èø1‘ƒ F®Î\0ZŞÀ!É¯˜ÜĞT`Ø7á%àÄ_7€T\0Œ\0´ÄÁÕ'\0b•D\0°šÄYÚÙÁâU‰.øt\r7Éb÷AÀÚçºƒ¤'7˜táåà\0ÓNœ\"\r¬PRÿ”„‚•Ò¤“7’(Rğë@ê¸tlµ	YiªG¡†€1N\0¸ˆ\\«zz=T—ÛÓŸî¤•IŒõ\$ªKàÑ0cĞû` ßÀ¼4]ORnu;ùç¥#1€PİF»©¤‘I“­'À\"JÔ\$ÂéØ”³<RªJ M€Ån …e#¶˜A¾VÕRJ‰JBSÒŠQáKÀRGh\"ô wğ™@AjÃ4QÜpSH<ÀXúP>vÙÏÍ	T\0şŒğŠV.<I™R¬£´	i¸\nV4h©[¥pª:¶çG. ¨›\0(¢HL\"ğP«´›Ÿ³RÅ\0¾8b–0TÓªV	ªÚÕ¢QÓ–‚Ïl]ğÂˆÒh&šWÛ¨ìˆCÑ(H¡¾\$&KÂº¥ÔÀÑH\\-¡p¯ƒ÷pFGèşƒtU’yÒ#¥2<*¦y²šbhÇD´/½>ä‰˜BÌ›‚GQgyÒlMÀ6±Ş`óS*%a£šª¢u½&õÜÅû'*ËGo2 ¶l0Wé[ÄÁ	>¬%`J¿òá l››L¨ç2Ç¯ÆÅ`Ñ,p~¸éGMs=ÉU˜Ö\\Ş`X\nÊƒ A2¡é\"e X41'cïáˆâ”ªÜ4ğÌxpĞ\"è\$pÅ€:SyÙJ¸9d&öôd\"S<Â Šší3\"ÈÁÜÂò¶40Šm°«õô	#˜MÖAeMlß‹ *Ãá¶¥FŒE¦z³Uà¹&—x‹XQøØßJ`àDNPwö3ú tâFï…ŸƒÚq0ˆ›`¤HLMfI&È²œ‘3„Ei³¢HÊ¤i<?aÀÂ,pR4PWJtÊÄèï—ş›Ş†è0º#à)ñYÜ\r\n‰…C|CVâ°ğ”Ü@óC\0\$Ní–¾*2€nVûÓÊ£f\\¢ÚúlHå¨¢ˆYTş`Z—P1ğÁÖ‚7’në%JKc:!\"+ÆÿÑÇæoŠëiÊ8•‹Æˆˆª#|sØ½Q›á)ê§n\0ŠÈº8@‘™·;ûNØ*İ;†ã€\nÛ+2(PöŸ}=º~@dñš®M?F#ÒÓû‚”.§¢ô¦æuA¶U`Ğ>4µP@+%;¹. €Aô¢ÇK!»2M¤É/\0Tv\$ËJ‚Tƒ(”Ç£D ˆVÅù“êgâ‘	£°­jÔ•Šy\0I“u>]%7Oºc:ãà4EF–ÔCOZŠj\$HLâ\0ş åA ´•*\r(½¥ßHB€•Ñ4““Òw½H`HYp\r´‡È¤?. âûÕ®ÕrŠ9¢¸¾öé¸3‘Ì\\ê,Ô¨&‹ÁDDßÈ*Èj.`4øÒÓ;\"FÀ™E6\$ò äJ¡ºõ!g±/½&vmëºv \n•\r¹v|0EH“áõ\"ÀT\0ER<~ ÁSzX…l9N–è&ª•ôû€´QĞõDø¯ú–Ôt¢–>§˜15JjÉz\0\$£ê©vĞi7pÒˆÊÁËQÏYGD\r¥KÖ¡tuj^—\"cSZ˜i~*b€-XŞÑ#:™„x¢‰ş\"ÃQõŠ7Á“İ€‘T_\rúÄú²®5ˆÔØ\$‡Slòg£Å6ÏgÆIteKúju6iªÓLKğ‘Z§zƒ”eO£\0&7Ä¢\n º¡tó\nõ\nªT\$!}Q0\$Ç;…Â›üQThéXTa#tÑ@Ò/…H„[È\0m/È…ïp‘*ŒÄ8Jt«8N³\0)ªSÚ¦ÕPcS+ğêƒ/5ºÂ¢&û¨ñ(àdá‡ƒ²7ÄêD=T8éCª€š’¢€Ùw‡|CwXÍ<:ª¦ÔM'Õ.øŞ@¤2#toˆ@Svj«¨kQÒ›âHæßö¡=,óF5\nÆIôK™TşÁĞÃK'ë¥ø¥£U(]8\nÎ&ÕQ¨|˜š\0e‹)İÃ;†È)¼“‰(ItÑäÕR90„ö¬0. M>S2*& /##ÕaY	T¦øÚ•.ªƒ„¡-Â¥¯m3h9 eˆøNRÌÄ¶Ğ2{f§÷s–Ş K,L4JAW„´\n~êÅÃ*oú‡P\n” Ûõ[“%¥a\rH§øJ9 ¤N¬\0ğ›Ü`Z5tC‚ ”&¨DÏš›HÎÉœ#Î4#O.¨ÙÃ‚‹`ì k8Rª&Gp_u«ôÜ-’¦1M)L]S U>ª~+	—ãºJP2õO‰UüBø¬:6ò—şR/êâÈœ\re×’ÿAz˜êQXsÈ?ºQà¦.J\09™Ä€Ø\0Q^¡RãtM@àdäÇÖ@Ö8«XÉ±ÃQ­æš\rÙœB@SJn:Ê„P®=ÛçÓî«À0Vz'MÀ_éKV6§ŸTntâ²êÕ„™]C‚¤eJr’°Ş\"dÌ«Ïš*ÖCÖâ bÊÕÃ±ƒ5\0cP¯ƒŠ0şÂ4ë	ŠõÚßÎšğ>¡×U—+\"ÅX+e<5ˆÚ—3åmëª¥IÀì°Ö÷',ß«7“‹¬âj=ÕzŒã*f^Dğ\nŠ…ÚÏÎ«@„z*à¯?R•z¼åq*Mn¬¼Œ~[€¯§ef>\nò5¸âü»ı“!Á’ß5GWà3á0!ÀŠ\$(Î˜)|Î¼¬¤ŠÅeŠ²\r{ª9ZpP9-ú£‡Åı<ÊGZ°Öè+E`,¤ˆ!œîõX¡ Bò¾\0gëËhk\"‰@ä'µhQx†½Qln%¸øêÙäŒ)„³F‹>ÖpóbYÀÚÒ9™Ql5V®² ¯QÚ …äß­2˜?¨`¤[©\$‚2Ö×Ftò‡\0ml	ešZÀ%°¶²êS|U-jgÔ·Ÿ\n&z¼:¾>\0\0Lñj·í%F\$Êê]W	©¢\"^âúò%U{ë7×©É\\,#=MJâ?D>ÕÕ’cDjˆà:ê‰Ğ	²‚{ç:Ğ…qàç±–€õI© \$‘rŠ'T¬ ³©(]DNå<Ñ,áâ¼.ë4ìã<ßdâ\0D’ñÈ=ØªÇNj-PwTÂè€…N¼Pñ¤ğûÀ¸šEŒ1ˆ kĞUÔÒ¬\$aE¨	F«Ğ^²ØµÒ«¤ØULW*®“•T>¥É[ÅÙúZ)eª¼‰l7f‘˜²•v1øÀaÒ1W`Ø¿º6a`Ş3ø+·\0xzDò»­W°L‰ŒÉ™T83xÂA{µWPu5Õ8“5ª¢5)…\rŠ¢\"¾J¬x8ş¼ÁS–ıÖqWC†«–²L†”L¢_ê\"ídQ6äÁƒcb×çL5](\0‹kH÷4¸ÁœÃş£º•p,ºÆì	«z'®àäğ¥ÇÌS• ¼‚uKF–çut\rÍ½¦8¡ğXoÑ`ËÌSÓÂŸ)ä-7@'õM\"¬m.éÁƒ‚ztò2W¼^üY‘J<tF+yÖT…ÌäÒqúA•`iä¤»Ş·\r(!ô¥+\$†5ˆ¡ö´(•nÖºPÊ‘œÌeÂ.&_ ‡PzÓ@Î§¢ Ûv2ù“èË1·AN‚%¿²c+a¾:¹6E`‚Yó‘è£Ïİ”§zş…HBs‡óI²\r\nBS`l¬=ƒe›¦\"Ö÷Ë£D¾% XòaÛ¦]„¾™‹0ÚUœ¹‡[ô2…XA°†<İØd¹ùˆ²å÷ÈhmC NÕ]ØÃ¥÷XM°(\ra\r…ƒØvç‰c¢í0Ö…d™ys2÷ì	€p!¾£‚aû…wA€À7Xm“,8D„éï°/ÍÂÜ°ÌX	P…J…jtŒA§°s‰~iqárÓcP‰¼ƒ^]¶À`3¿¦\$Éº!VpK…É•‰3ASìJ“x†v\$ò™—ÑïÓ¸ğ±FıÙS´ßPH F¾×•]&:•ípÀ\0¹HÖ.ìvp(ñßÔ.À:\0pªpÙ~¶¨#„»À,Íê\$5\$€_!|¤ù«”‚&\0¢wZ•­.J4Ô®,YrD\nñüîzËËà¡ÀË\0h\0†'T{\0\$^ ¼lgr¥£\rt`µï)eü;ª»q|0Dr¦¿ìl\nõ£cmå@š~4÷_èƒŸkd:‚J(mv-)ÿl¤†CqàjÔúdB×ü5Ód-Íe\r’Ûd!ÌRµ†oÕd³°ÀX”q+Á«Y¤â u‘h»# \\¬ŒÇ#\nF¹4“µênl“Û)6%¦Ôş†ÉY\\’½Ñ l—Óls\r¢¯aWŸi—ªXü'>\0ˆÀÛõ2ƒ•ã³eÊ[+( Ù,]ƒ\0±}}dxYÊšöT*|Dn½9Ög)2gÉÊîTLùet;pË,æC*!øŠ\"—JË\\z`àHOºÊ\$Zö”\nqEÁèÕTÒ4é­¶˜‰ÿtq7*Ö@ÿHÎ\nÖË°Å©oJë¥V¹]!İJ€1Fì×Hµeò˜E˜znîê¾VGm+Å\r1šO´ô	J“›ú\r~ÌÍ_u À=¶9¬âÍ\rfğ5C‚ÅÙ©¤Şî¤»PûÚif©¥××(Å²“6p¦íG’™Ù¹Hçe‚Ë800?@_§IÍ•Çeªš\$`!Y¡)?e©+F°ò5«Y™¦*^¢HÊ²1k™£SòUˆMUq¿Ãã›M2‹PÌ™Årj‡Øş@ƒg	ÊCštÊ=ğÚæÈ*©†5Q6[v¾}Jú'°G‚§µcìF…~\nK0­–,•T;1cF( ™ªŒ3¯GfZ’šÅ I\rú)\"›¤(Ğ%[B€c©Ò@º’µ¡[C¶†ïZ´&ãâ’ÃàÂÓT•©4,P´\$±Qbµ%jMvŠk.–š³,QÈ	Ğ˜¶µ?³çgæ–LcØ5:¹™r²7B¤Ä,š’ñÿŸYş§áOrĞ,–¦4yÇØì«[c¾Ò\"§r”*›<Î±õi@”ºJÊ,È)Øÿr÷6)»¨a…³-úN²“6Q\r¿B‹7H›†R#.«iÁrà¼©¾Æ¼AY0,£º\\*_c¤³lÁ—”Ø\$É¼ø¸÷Vq,»’X_¦÷®UŒ†âcÜaˆ†#z,ÔÇ»œ›>©8M`Yø[R( ²ËÀw@-=•\0P(`à\\qBà	˜z	ï7ÎCİWv¶a€i''{äŒÕšDŞŠI@– ­¡Sjˆ¯ô÷)ÔND‚ĞÔ«ø¾´ÌMvÆƒ{- \0x³ö—¢Õ­)P!İ¨ºÕÚntUQ\0%«Q¤¶©KøQİ¾AÁL]\nkPT>•\"UqSùMFRïÁÔÚ#“HZ£E!¨\n5)£kqıB\nDWÄQ™khÀ%csÍ CÌZÚµzáĞv»`Ö®\0005Í%¶‹ú ’[aèÅÂ ğ'¼VÈ˜A[%©jO6“ÀÚ´’Å­¤ùTwCŒT¡¡x]­ú”Õ*\0ŸÚÚ ¥lxgM€ÈÔ•­ir”¦ÕşÔrzÁçIÙ#M{ìğ%û^É=T¢V…\0ÃT0«W‡ÀrÉŒÛLj¹ei`TD‘œ\"Ö£†gx‘˜0Ã®ÖÁ)ÎR[N†ÁAğÊ|«bhÃˆÛcöõ\"±\rFÌÑ#TVP	œ3ñö–MÊXæB„í°‰kT\n ŒT‰É¶½uæm·‚:']Áj-Q„¸1f…F¢öâ•¹M·n8[Ä~¨ÿìBÖU)ÄLéBÊºı€œ¬Ïû¨+Âb2Á*M’Ş\"&„ñşÛºÌò9aqÊÊ)?UmôzÍ¸‹öà‰-[„†ù%1æz \"N>eŒª‚.½nzkRÒT“ú#şL—©)àDaş@;º.5·Ê‰ŠÆS9å|!<…½P4³â˜C’ƒZƒ\n©KøµPu%“»r¦ŠVÔ¦‚¡Y/Ó¯†Îê¶À™w¨Æ709Y²baÒìÎ‹¸`l§Óüy†´SÆNÖTB‰<	”n´ğJXÀ\0P\0N(™\"ËvöÁÑ¤¯ßJ¿Š6˜÷¶•5V†¯äŞ¿ª}È&ÑªÊ“+¸s\0µ½8.óâ\$(Ö6Úm°{7ÔûúÌòàÙ\ry¹s¶¨wè`è²ö‚^¯“‘ÿÄ&ºOvkÌ¾VSZ˜A•Úeù¤eÑJhøõÈù°_o„U.Ñ­.Ë‹€:îiÁ6\$ÒIŸH0âŸF2{F^¾1‹ªÁi3œZ\0ÆfÃºk‹érİOà‘B¹vEÅu*Ï²Û×•bé„	z§èÇ8²^®ò˜\r«%àÊ%:‰<	¨Ç\n\nnáY4V5“ˆ!˜İÁüÏIàŠÀa[ö­<¼\r÷İÀ¼ß4W¶¢y/ˆòTxfBqËÎ1,`à½d\nõ’B·#ŠÛ\\¤órEMG±»†ñ]Ù\\«¸ò!x¶°91[653ˆ<ÎÌóÅxÆvZF“!Ÿ”ùb^¹Ñy˜E`Î/pœ×ºÂªL0oï–˜ÜVQÊËÅË›wƒÕó0Ù\"FzCb°	­Šã>'P —@’‚cs‚	 9¦ÊSÆPºäY<¦)0cGB™&¼4¹¹¬Ï–å¸’[–åÆ„]å\0±sŞ	´¯ Æ†úİÙU#ŞãYÈuÈ\"eì6jQ0Zˆp]Ğ0€o9•KËbì@V2:¼e|÷BHÙe‚=s˜<(¨M¬ºA„	bŒL“FöÇ•¬®Ø£œ;~k¡VÏBÇZÍª*e‘¡P,)ƒ}½ q-ÑÀa¶å¤‚MB2@„Ì²Áò	HN¾X)r²ã£í·¦À“Ÿ\$H3à7qñ¼b¾nªŸwyaN‘É‰o«ç|2ôİ™ˆóçœÆ• “]W„øFë4¨#nRÇâ>Xº^aW k&Ú7²Ñº\\86é +§·îŒÆ™º„g “­×òé\"0h€J“:éù¤|+×–dÚî]\0»²ÀcK²€@,İ–‚ŒGiø	 Çz)pnÎØ£Pq&?jü`’cctÿ/ÒJä\n€¤àQ^»Üîq¬<¦Î5ÌáŞ—7GYV¹•Ô4+ÕN\0¹Q#4W›ït\0È‘ òÿî{X£R˜ğ¢øI}	¥ª;Ø©Ä2ıËzö¬‹fÄ‰dî-ä€Gw”ÒÚ`ZÍç~(cdìÒão¦ãòŸét\$SØ¸@×¦ğÈ‘ib 1ÚW¥ªµ»k‹®ìø!\"¦(İ¨7 À‚€ã!eH;‚¶ÉĞ~…\"â\r.'Â¶×€P¨G§uJkÈ\rµ©Ï¶wƒL‘1!iˆğ ¯­UŞ\$™¹©ËäAû÷k%*†Iv½n´Êâ×„_øŞL¸0Q[!ÍätZõX˜	ã†×lĞd]µºt=d_œ÷º`OÖƒˆY…{y´;tË÷”/&-§v˜­°mÊ÷¯(dÄ¾ò^çú¤`ÑĞ~¥ó©Vª„áŒšf–N×’‰v@xWîB+*»¯Y¼éF’¼<J»ÎîÛ«º3a£±_a%ç ½7ví46¾šU”#€!0ä`7J£@‚¼Ùë¨@3'ã†š°ézA;,üë%*I©îkTçò­‘2vZ7¢ã¸Ë°èÉô‰îpÍ÷2Â¨¬m=½iëjbà`‰\r5kB\r±-zG@Í‡l.Ø¤8‘'‹º¡^Ú£\rŒ&šö;Q÷S‘†ÅB\"ÑÄ+poÌ“\0Ì‰·fªğ{›ÛÑ@8^ÛO'{y°­îÙ­…ÛŞŞ½H‰,:>ª€E‹Oñ\0@Ó2öÓLÀŠãá¯w´“\rú—€À¢à¬Ì\n”:~îê2Ñw¦A¾»¦iá£€•ÒÊAˆí³«®Øë )Ø#cÄw€9‰\0ŒHp‡°(ëJ'¾*\$6øÀ‘\0ÆÊô¶Õ¾-|†øÒ:ÆŞRÂÒ\0x)d€û¢K\\cŒ„P;&28¸Üo‹`‹É†(9€0˜dÔÒfmoSc}İ|ñÊ5d—‹µ—Ö¹¾šI\0&-¬…lÈ!€%LU|¾rd£4ÄÉ‡¶4K³	\0R”4-Ì>Á¥0/ÕBE)—Ø\$È\\‚j¥h9èAØL³‡DÈ\0âO%(\0ôˆ‹ì 9(É&@9İ°7øV0ĞHCD¾è«èˆõ#|¤~Y7@•ÿ«şõ-µ ¨½ã/Nš&,\"à@şg,)Ø<BSÉÓò¯lt º?`@(ş[‚w¿Z|iW‹õ©mG½Uy—lá!êbQÀ%[-Sú­ûĞÀ	\"q¬·eğ\$r]ÄØg7ğà^¦¢~Sh„øE8b‰m€Â_Í}³~ø|ˆ2ãa¦d×]T;\\úõûğR€©Êm1ôX! 2k—@t ÓòD¦ş¢9à) ƒ:‚XAx=Ÿ)„Ä¶ÀqÙyğhb¨@A€:½\r¾ò	ôÈ¸'ÅşÆº€[H`*ÃM27)i<Œ\0-”½l*ä`àiL‘`øBÎ²ûn×¼0åÀ:\0gGÖI\rÍ±Ÿ›şx\0,+<¡t§\0SzİC5/‘€\n\0ø\nü;ÈòÁÆ‚å‰¿€Hà0üø!Îà@2\0e”¦-2aÀ¹°Sf@œÂQ\n3\0iù0B¬eÈ ã§0§\0N,×ıí2ïD°\$\0'H92/ôÉìs\"ğ4¯&·¸ÂP.`ß JY¹/ö*t'K»³\"(06yÀ¾\0ñciğl˜\nÀŠà.mß&0¸\0VàbTZB¼ÎŒ8Ás'ÀÌEÅ=ùüØZài™\r“áHrà\nğ3S)fCh	\nüÖ0E=äÀY‚;€|@KF?W°\0ûs´Ù¸(°=„^Pë>œ÷~©`wÀÉ€	x4\"7	FŞ`ÉÁiƒ-ï!pi\0/Á©‚\\>0Ø‡Ã)½U6	óÑt<dÖ0X`}Á½ƒ³Xb@k-D=uÔyçÈbC¯uL›À†›NÌ¸Ñ\n`ºM¹‚ğã³Ñ0ÅºÓä‚wS@UÛ‡œ†RX#›jš¨6¸µÕãA\0‚‡9jè™I¾ÑÅTrÚD`ÀH#|¸-ÌX-ğ‚`ÁwÿX‡ª1°0àdN&(ÊƒD×/SÃlÂ×\0`›À_°•àÂ[€Ç>‚ğzá=e.ı\0SıLCa>¿ÿ…`X`ÿ¸SíyáKyéƒÁ`D%jÇâáÇf°`ˆà	øHCµ9À˜’˜ƒQ¬-~@,€E\0§…±;Î±nªÈŠa\$º&ıP4ô(KnÊ¾%Nş\n˜é€lƒeY]<>\r¾=5pà¼*×ı0™aZÀ!† à!˜X!ı`lq…›\0q€°Xğ¼5/÷…ò‘SDëıXGÇea„«*ùê…şœ0\0sÀŠ_ím~ìŞ¨.›ş ½,Ûİ?’\"ZLÁövó»C	‚Ü§|YX‹xT*>&(rKKğ÷ÌÉQ–Us`¨¥+\0í…ü8BğèFtúqEğ¾`È/h·Ï?à*+y¶ïÁ\0ˆ»âE¼—|d ÍèvéüT\$¼âÆÜ;.0‚ˆ6µĞ‹ñrü<\0§áã=‡•Ã¨Ø8CöpQaÆ#ÄEğ\n°T&ìÎğC…	’Ÿ&n¾Íæ\n¾Ç]Çsá÷H·‡äšÆ¼(ø.°î>øXQ‡~q> L<@c›³¯\n0bF¶5W6ğ\$a!“…À4¤á ïbMÚ RŒx'B3ÛT\0Šñ££µ^I™{42A#L³bÑ´HØZ©Ù“>aggÁÜ’nœxVã(y^†Ñ! ´ùá*Lñ\\iÊ”4Uˆ¾úâ@ÏnÌ…€ı‡ëÌ%É‰Ò¸G\\äÆÌØQ°íaLø	ß+L@,ûf)GaÄãˆß&'ğKX _ø0Ç‰¯@ r`@3â\"Äúï;¤ÑáÏUßÇa}š,›NÌx ã\$bEX®G†0;ã‚ãæà![|Â œh@ŠF/q‚’{Œ0XE†*»\r¯dè‘¡ZFh@<ÏJ¡—ĞÊ‹]}‚¨5FÄÉ±b’Äá„³»ÎÜN¨P@Ğ.<.Ğ\0IĞP`\n±d%ƒ§¤%BDàÓ+¦¥€1\0rCÈû -ĞÀ€Íˆ–OvÂ’8Jˆq¬WÀº\0Öq\0c‹h%eğxšŞ@bÄ’C+ô|Õ,õe…’/W(ŸKŠ|^r`öâ¶ÆƒI)¡£2Œ,ƒ\r@mÌ#àh¶U\0‡UJE¬sÀÀÀæp”ë©6\$pÀ0ofñŒãÅİŒxèX1`È¸N;MµN\":ø¡ñhâtø–ˆJ‹+\nX6nˆ\0~Z–Ü8ÌLà‚%Œë¶—|‰hq;ïÁü˜p\$[„&7®T&3páà (kaÕq\0ŒPRfĞÖğs¥ğ~c/\nnfDû êH–È‹1ovaé¸¸eDtV]ûºM¸|àQl ±ˆuIˆ,¹! Ie¬Şr=”uúG0ÀÛAÈ&bl\0” ß!çD\0×{áeJ?rwX­1Å“Ö’F€\nÀ¾v/ñÉ‰G’\n ØY	ğ=`*-¢ğêöÊ€6·¸¤±Ò’ÈÇw ÿCëÈ`\0EÉkáãnÍNšJVXåÂ®€'2	ïb‡Îq@ƒEÇSD²Vö#€ÒÆ˜\n)¾R<»h ±çJm'y3¡4qXã1õc¨ÅüË…F:õ:•¶Û•\0000Ç«ôq4z·±×tF4Ğ3Gš8ıjåcøúM\0Ş?Èeà}Ñğ¤VD8å³¢\"û>¹‰oX¿ 	 uU2[\0€Â­ˆÇâØ—\nü“«¿)e²\nÙPÈXÀãD„³±ëd,\0@ \\+4·Ÿ^”`ºQPx&B=c­†^ ÚáÛ¨Ğ.ØìÁxÒ˜½Dö+Œè«àÙ…6ø‡‡\"r¿3hD¥ìÇnPp\n-@'püƒOŠ±‡?RÇ‘tA^0TÎ,Ú„Ğ¥\"Ş2k4GC±Š(ˆ·tM,°7K[è†´…óÔ\03à\0\næ)—\n^ôìşÍ>94²À ¹;2	hÂ‡Üøè1È`½YºáàRüŠĞü¡\\\0İÄ;h„’YØäï† ~r•µ.gt—(ğ¶¸\rp\"àÍ´zşVÒ«üÑ]ï_’ÑµF|ZÔø×…MQ¨ÓP!øÏ2Zb®Pµ¤±^›š\0Pï†N’8_–%:©L·o°d+P¦Ï4:qüãV–ëª·B&Œ?9øxp„yÙZä&läquœ=¤Õ’ıò<Œç¦Ìêp¿\r\$Kƒ€½3æÜ#×°+XfÂªÙî{@˜\0-8NÀ„iÄğ,ÎƒÖo\nà#é·ÑI@ÆN öğÆ¨\0_Cè\\¨ağ‚â¥‹ºàQ“¶î2mÁ<†ÂáÉê¾'¾&vâ`=OX¸ˆx—›Ì©h†]pà=[‚¹*¨ñÊfMk%æÓ?7q dïÀØ¶¾3v`Ç˜J²œá.\0@\0û+àÁ*7FFˆÈç€…¬¢á1	(p\rÛH-|£’¤Y˜eI†B{>&<øÖ17aÖ\0G”şöi€üB\0v’ğ€-@'ˆkNĞ8ƒå[iÍÜ60æ«)j‰\$û™.òYå:ÊÜİÔ!1V^Ô:Pé•»XÖhˆ­*°¨pk•+îTl©ò¥áßÁÂ êi9Q„£±Ç{ZBç,Á…Ÿù@è\"ğª>_0zH°FĞÑ!r³àá¼£p¿&\$ÀJNË*œR+³y<ı[Y0KöŒTq¨å:Ê„.¿&µë7¾²¥Ã[w	ŞLà€4_4\rÆ~/\0M;ºE„áCÊ¥€ã(ÆSåit²­âIz(»pd\$Œ¬9HÁ2|Éù•;A¼bxrv¶%E×Š\"€¼®\0ä°ååÊ=0áœ:	\$¼y>r±âmõ.·éŠ‰ª\\åk% ì@^Á*3rñ3áÂ²°@ÑfÌMä2qåQÊÕ‡%va®\"TdÚ+È\"»˜tĞ¤Cù^.ezP½•óæKÜ/Çgòû#Ëğ¯/ÑŠ0ãs\0pËöEpŠîå‘,Ùre¤áutš¯™ğH ±É‰ÉÁ\\\$ë¼Ã3Şó~ºÓ€£0ùÜÄ 3	#ºÌ¨EVDp8•!¹cQx\\ÉM•Bë®ìÌƒá‰dÇÈE‡NRSoøûB‘‹ò­»—P˜%›Á\rùv‹0ÜR\næNM-Y«7Ò˜™š“})€>— 2îE¸`<e³Tm@´Ì‰X©0à\$–gƒÃ«—Ë-ÌpÌÆ sæ9ÒE¿n\$Œ#)®røàHËç•ş)°: ¤÷u—û'¦`\0ÀY€—¢åìÆ‰¸™¦+œ £É“fS™wvb¡()f.Êq–G\nFc0h¹-ê‚f°&°½u¨ÖL§2™‘‘fHª]èºÔ@üÉ¡Wb£MÌ£\0bÓ¦eZb9˜rfæk—”ff{_O@/@fm’r™“3‚5ÎBøó:½¿³3Âz10\0É€1dÔk„«&¾hrÄnò Òƒ¾Óš\$ƒ¨ÜÑY»³FæÍ†	¤*YÁEf ¥š‰u\n–;Ì¸ºÁ/bû€	aÅ™bËp ÜØ@Eq¬áÁÍ†Û(® !«òvà+2§P\0KøssæÈÌW›0ÌûÚ<Y)ò™dºÄ÷‡g1–XÙÍä ´ô6•—Ô^!#¯@f.š.«r[_X›1fhôîk¼ÍY½R°gRXKUt€µ€öÙÚÌŞ“¾?¼ì´ÂÙ\0R™!eĞDÉy¸ñËfP™_;voˆµÙ>Ä€2Åğƒ;˜v@\$hÍü¡W7’‡Ğ—9À,3f}Î‹ó6Vg¼à¹vÜ¥äÖ’·|áy¢3‰g‡“8r„œë\n3FâMÎd\rS5\n\\¼æÎm³œŸr>§šS(îk›Kkäò|ebÆœ¨5ÓµØæ\n;ÁÏç+2o‘JWœØù‡šà(!¡„­±+ÌLÄ™†³u&tÍ“Ïtğ/ÙóğçÑª¶<:ŒÙÌÂt2¬.eyÎ•6t¬öyÎ3§_€èÃ:`ÜûÓw™XfÚ²\0îAÕÖ|¬Ãh§DìØ'Ï¶Cw>ÖbõùºsdÌSç2Vn,v™¹‡¦Ì¤Ñ/:®z2Y» ïU¼³¶279H^¼ò9š@ï\$Ë¥Lu|]BáYÀó>d.6l‰ä[İâÜÙ¢ÌÉÒ-×9¦ULırà“\0Åšã*îk¬¬€óßb‘˜ôd&f!‡xÔ\0002\0bÂŸ…3Ne*Å04\"áÍ¡'B@ş¡š	èá5æ¨17ƒ8¶rÌÂë³cgûÌOŸ74yœ­z4çÓÌ¿Ÿ16M–\$9²²OÜ\rÍ›ŸŸ6†lÚYÒóÚh5ĞŠŒ\$	5¥	ÚfDÍ·Ÿã6î€‚\$ÙŞôç}ÍÍş-sİ\r¿´0‹lĞÆ\0÷Bn†lùyÿ3+æb	]•,v-a³±·fÎï¢;Ö€¬ÉÙ¹tg€Ìİ	\0ÄZ ™á‰ègˆÌíQÕhìÏYÁ\"/+y‘8	@…X6dØ3æ-Ğá˜Ç+ö‡=xA\0óh6–[Ic8N,ĞYÃ22æ†ÏOœG=J„¬â†#3Gh/ÎgÄ¶Œ=¢;CŸç¸Ê¹jçAÖkÆ†y¯´:vC¯¡µvŞ5\ršÄ¨èIĞÄE¼¦Í	×âhGĞ•Ğ£•zí\r\n¹t+Í¡»:‹üé0sõgğĞêÖÍŞşX]š‚=h|Ì†æÛ2.ˆ_ÙŞ4‹ÆÎù™?D>e-\0š\"óÌfcĞ\"T˜zVo2;ù½BÚ\$ÀÂ‘Ÿ=~ƒ¤YFl~hNÆÚG7¼2œğz*±šcûĞ»ŸW?ë]ÜÎùåCªÅÑ¢S>n‹Hî¹ÁôdgŸéˆF&z€º3´fUğÎ'š0+ŞŠ}&Rf46dÑY£SAv’\0tmi… ãGŸİMŸ4redÌ{K1f†õ\ryŒ4âsÑ…¤9ÆƒXèY¶j¡h á 2Nˆ=Z3¿iÒ¡AFnİ#ùH\r´Ï%›ÓDöx*nxôšbø-+¤oJ?=ì­4Å¯Òÿ™[JgÍ'¹çs@hÊÏG£CJ^zm)¹¡o´ç«Eá¯J’IµÙ˜‘éVÅñ ·0ô ”?\0_¯xmÍ0ëˆ*8li‹ÅÙÒ`\nsÎiÒ¨ÁÉób\0º¼4Ù¬\\ë˜ŒÜZÚë‚éØ&€ÏséZËÁ ËK†ƒM!\$Wñà@Í?š»JŞ•ÛAZ\r³T×lËÌŠ·F¶{\r:ñH´ñeúÑå€øàx:zt•GÓÛ˜ûOzı<ÙªìÅiÍ°FÒr®ü¸½sã\0 KœÇ5}Üm,yñ+¶yÒÇ˜–%Œ¹¯ô³Ëô2‡0‰F6SÁÕö1a4(b!PhşP¬=…Ø%ŒåÊ á\$E…ÇŞA†€ŠhÊ#\"‡(6¢0!\n÷à)¨] €‚ãòqœé\0Ê]_Ë:&JšB72›élÄå…#)àÀ¼ù–óZã‚Ğû¤CK²Áìê™P‰aáDÏÑˆ“,Ü©™Ttú¥ËÉÈw5.cà>ÀEué·Ê¿¨?+¡¥š/´µçGÔ¡Ë:V¥ø¶Z“51ÒWÑ1™—<&jÀz+ògŸĞL«R¶‡Ú–qceÉ§?[›:v˜Œs”f	Ã‹§`Rğu`A`oÏÆŞ oö©\0]¤Áá1kF¿¹¨r	¹dé¦å„lMÀ«sDn`Ü9:¦Ë¼ê•â¬‰±+/ÑÙ\\‘{‘%/@-”\\¹vÜŒHı[Iû˜\0¹¹(*\rWú°\n.\\^]•Ğá E‹¼ã©Ìz\"³!Ñ+mSØäñÊæO½Ëª°…ñ5á²tÁÌ‚¯”„0ì0ĞágŠ1’/´Õ†cVV©-Y¨+2üÚÔÄSˆ¤ñ	:ºˆ_jÇ@ª‡¸`uyôQóêf’×tTøã<R!!€ÂUËqL@MÂa‰Êâ <#vÉ¹#[Mf%ıúdOÛiÅÑ«cTè`ìç™õ3Ÿi±Ñjm¸czÄôÀk€45PO\"B S²h’Ğ½¦bİ6ŸM+zÆ1KbÃš°§5nN­+zK2àûX.	(†|iÂbòc^Ó«¡(Öi!­:«€4aÍÌ(	T.´\\ÁZªKE}N9‚ôÅ\0VRè—…KkRÕ±Û0v³MSÚÓ³ò)]M T¶¦äœÚ½-<ôÖ-ª¯[f´Íl€ƒ€<*À†%ÄbÈ]÷½\0~dLh\0ÖT!Ë à…›øI(!Ì(TGZö´}ä_t\"˜ Öå­lg —]j€ÁAdH¡)!B.cúŒZ­rºëz£›LLú½AŞUÄs Ò©½ß&‡=f™–ÄVkbÕ-]l2şºíUí…uY&H5é®Ç[¸\0u¬ójíÌ«ø|²¬àZãµàQ€¡J„FƒmsT£_E`¤1›¡-‘qó§æ?jp\nKH}TJ-¯rl’şVU™#V^©Á9–Ã>)s®ã¬KD˜\ršÛÃ°”À×e­7Yv“ŒÊúÕƒóXbñÏY¾fúõ“5ÖRPÇYX]e¹—µıfëÏı§['fUMf˜ìu›k>h¬çA†³¼úKræ¬ÁÒ:KûP§xÙ=‹âc\0ø€7¼É\0“T8¬È0Xì6¯>Óüs+äûcı‘âÆ¾5ˆúa«›c{Õx…è	jó¯úfiÎ¸ê®‰ìaÏM½Q&\rhG?È\\[K‚€Ğ„8Ò˜æ@ Ûs¯†§ÈU.¾Åp\$CÊ¶D©;Òs¯˜­5DÉ‚f\r—Ø,f˜õ©ÀâßÄrÉ¨`ç­ÒØ‡6´¿H¶˜?SÂ7y»š!lrU\$ğ’¤È‘y€2\0?Øğ\$¬v¾9y\"5òYdµìEP.È‘@r6Ff=½ñ“\\jnoıO¡NAe‚ÉQpPp]“¦UÍßìrÖG’œ™2KÀƒ´À¢&x§\"8æj\r=qÃ5Œk7Ë•Ge–À%{õ‘kÆÛ°CF¾Ë4¹{éˆl¶Ø-°½¨ôZí–:x6aaÖÙ¤»Yøm[2µ™lÍ¡³?`uÒ+”é0@ìkØ]²¼ÙÑèùºè[O*of®Ë­–šÜv&€yÙ ĞM}\0„©êhÒÊg>lw¸OğÊà}î\n†şØ\0ğÎc‹ÖÙ«;Wj/,,j›Âb,~Dmìáƒº	lêÌ!}{2Ş­JA'†ÀÈ\"¸	~7õR<˜YğÑajÃK…»\0æ³QÌXğÕgÉ±O!à\"l.`ÑpºáwSõ…·=~¥v—k8³¼1ZUğÏLğƒ3˜ì¹Ëé°+[ÀÀØz™vy±Ğwšõş`\\k:ÜÕa\\'WÎ˜†¯=¢Êö†ÄÚ;Wh€Ìµya\0‰äÖ1ÓhØølroÔ±Ìã›©š¼8V'ÂIi7Ö:¸+YÆÌ½g[5µ’Ùß­].líHØ¢üëª×syÛR~½¬şú]ô@€\"h?¯d¶±€iò#¶»iÙ§«gR\\½¯¸…–l·Ô§¢g`á<fÊóÉì›X=´Ç	Ş\$Í°9Kõ å3Ô‡HïR.¶¬gº’rİêLÖ0ÔO1ì¯ÈiÚöD£kÛ	\$lõ˜4˜yd4`e’Ê’÷,® !‘¹T6Ëê]Ùµojf÷»=ö©èæT/_¦·\r«zãèdê˜<9<iÀ€£åÚ½…ûhîÑ¬ûF6K4é­Ó‚sf[„[!Bà=µ´­îÍ®\0	ÀëyRÇ·—hnŞ¬7³”:jùÀÄ:soˆ¾aFÀÂ†ŠWºï\r¾ÀDñÍmùÚŞ÷:@æ_T5tí[Ú˜âÖ¢ìMŒ&Wš½Oé­Xöª¢/ºûòòæ–µ©šë4Ñv8XJvtíGŠE²×Ÿ¶kÙ«6j{ÜRÓ\\ äİºæ4<æ“Ø²†¿<ÿúÊ5¿ë,SG„bËn¦f×¸³uÄè?›ªec¶ärÚòuÌîFÍÓ•×e†Mrºò	këÊ×Šiz‚,N:È§äX‘Ô-Ãsä-rû™µ¥îhŠÿ‹m.]=OÁ£¶âimÃ·ª*V\"|@YÁuD@,f-œ'&§üë`u«€lÛt X ÉmF*Â¥”òİ&\nãt©C!·Êy©È8nLş­Ò;£¦×Kiˆ7T†²­Ñºä•²­?t¾Né¾ÏL÷*î©Ø”gu^İÛ6á¼’nİ.\0ûutù_€ê7`‚•ø%ïv,¶”¸!R®W?ô¢¡:=@ñ*uÛa¨²ô3Ós9¼ğ6CÊ\rro¢‚t¡ëÊÃ(š‹å}}»}”5h0¹;¹nîNİÏT76éİÉÛºÉøéÉÿœ³':5euäâÔH^4^	o“yCõTT†\0 ¸z€dÇò<¡	ÌDK-® ‹AÀÓóÊ‘•«Yc§§7\$¯ã`\nÿ[¸*ÜU¦ĞÀók\rÃ¬cz)áô:@üî‰\\Ã˜[X†y˜RZËök&Õ#·oV´}ÚÜu±ëñÚï„«]Z*ùM»36ÌíÏÕ9´¨ÎÁRyĞ5¸á\nÚ›[fôÔUùm°”mÜ¯ÔC^¿}d[Ï´4>ÜQ¼ƒÅ»Q7 ¢£5¢;1cİxšÆ·Vdİk€§{(DìûŠ±î×­­Íö´Mì{Ü7©ofÖ‘³_nŞóİ×FŠ²1S[­«XÃqÌËâñî,Ö¶ƒp¨]o0Ô©}ë“ÜÇ¹s¦'j Ñ5Îï”ê“1H—Sz˜õĞg\"I˜y¤w™ÉÎÄw¡oZ+¼W.>ñ~ÚÈw,‡Æ‚ ºaÆ]\\zq! í×ÁD@Ï[(}àu\0X®nE™¨ûlÛ,é7êÁ¢ë4<9;]Æøuà‹Iôï<ªÀ»Qğ@Š©XÌ{®ÿ]{~ağ\0h{×˜ú:ã–½\0hšô÷ËîsÜ»–+]†½MkZZœ\0fÛ¶÷lZÁí{¤ÀÆ‹î×Å²WWnàğR)¤@Òëæ˜9¯£y¦}m›Í÷£ï9ß—yŞı †»×4ÈğŞãz~Nİ˜˜w«`(àª·S®ô¥åÛÙ7¾ïrà3µ#yÆõ5—àD¶coKÖ-¼d\"ñ¾šú¶…{\0<€-ºİ››³wŞ‹&0ognÂKÛö´ËQ° ŞG`cyÒ*w6DÍæ¡@™×‚²l6q#°å»6Ã½üŠ\"1«…™m±ÍPMˆˆˆö#R2±)h\rä v'àCÛ¯¿€ˆ„é—NÚ¨] M±háÓ Êé\0ì¶.ÎL3sbøÁ½Œ:j@­Å®YîJkˆ½€¹v5jÇØÛ²œH Î^ûÊB›ìv%mÁı°8–\n6>ìmØÿ¯d\nz}›!‚Lì’ÖÙ¶C€üı¤!¥ğáMµo…Fâ\0CÅ¶KéFÙ3¦LÆ‚gà8ª‰Hn\$-ªâD`ß	=„yºWHÙVêëXgµİ¼¸ğŞ‰¸Ûg^İYq8ïCÚ÷½'p\\£[_¶lğÔá›Ã[`ÍlRûÒ8ÕèH¶‡f¦÷Î;Ù¸î7Ù±²ûf›È5YÌxê”ŞÃ?z†ôbï<xC‰tq³uå‚Ny{q‚lä/}aÕ¨\\w¡píá£½gnõm9óİÍsÊEµG=ş¡P…j±-ìıÂ·¸o\nî,+ø[\rl“3«³qĞ§Á€Ñvóm ‹¸gnÑM½|öŒã”Ú6KbÆîÑı«àB6‘a ÚgV«^Ó|/8iö¥-lÂıgÛsHE2Û±I©í5ÚO´ç\0÷{+\\RRºa¯›Â	˜¿\"™:8…ê­áïÃ_††õ»|QvèébÛ§ÄcG.Ï¡b½6«ìs\r‡µkTàŠÎ%¼NÀÒîË–·k/½¬øívµnÛñ·èHşÔÛ†­ê5ít°ÿÃ'l®±H´{^xpëàMÃã€×İêœ8nğá«Ä;‹ô~,œ4ô6çæÔÛ©_]Nt­ùÈO¨ù*qßï¤Glf‘2Y,x¸XŞ÷Û\$\n/l¦™œeµ¸ğ!Û;³zgîÜX8|ï€âŠŒŞ6ü¸®ğöâÆCG­˜¼etKmÁÔ©¶‡A'LóºNÑ¨m¨ÜL\\w°-,×Dwa1£›ºù.€‡Q€Œ·Kñâ)åºmÔ™CÓÏk±\\H˜oĞ\0¢¾(_šwk!yÍô£u>´n=Ë—8øğãå¡í@üÔõ'G·Qî—O•Ç€)”>A#yqİeÈuì~A»±‹Ê9õä\n—ó‹®AÀPyn°İ–[u~é.Cúİ±¬6İL²£\"\\ï†ò7J‚;Ó£®OYS…]¦9s|¬Ü­ª—wI]—r\"‘8UÑˆe·†\nKyÌréßãy“·‘ôÿ}êÚ5uş×şË`ñ54k‰‡2ŠH[TK.>EÑw'mñ\0»Ÿ7I|]É[–wUn£Ö·Èk #-Çü“À ê•áµºæf§&Ù@yrâqñÅeSĞÜ¸ëA—=äfyŞ-w˜é„ÇCËòrİ.«Ûtl´0;ö±5‰=v(j`Õ`âG>:=5à*\\[öÏcoô€÷!>NaÚBÜkÆ÷“¶â§şxçı{°	IYlá/{^¸pğØåSx(—*Í~Môşñ˜äMÇûY7>W[ÏùSåá†µ€»–!y`Š1pMO·7`—,p¼ªùe%øQË9²öñ\0ˆ˜/·¹o®I—æ²œT©ûêùmïÆè±œRBÑrCï|Ä\r™;,` ·õ\0>«V¡ôp\\€Bÿ–Y\0dYü´cpÏ.@@<·1Ï­ˆµ_x¾†.'5üp3×÷É×–îOPy=“XäûÍË¥+<¯Š`r©âË93g·,Üv³ooÍ!ÈÇw6ø3 ßw&l\0×u®	¢w&-ğ`³2<È8ÆÁÈ6@÷q¨)“Üİ!,-—3š”®h)ĞK‘9AYT•÷Ø£f9Ÿ¾æ„Êcw˜’ŞhÀh&óI °…äk¦ÊÚ†9fÍ4U¨‹wö£2ŞAœ9ò<áW˜ü,.d+Ñö©€Ş÷ÌŸ`#.e@æ²S¸æ‰ÍJ£w5Ni9¥»÷(Ao%üšù”éÑ¼\0‹qvå#Ìz4ä?ÜgÉ•¯æ9tòÔ=ÍWÉ3•ğ&£¼ù´ˆVËÓÍ­¢V•\r™8yÀÔ›Éıš³™`]¹Å¡7'eşÜ£ÆÔı|È¹’Ğ°ä¨Ü'?3)°\\İõS¸×uÍ›Ï5^oœÕù¿s`çKC«œâízÆAÉqÃÊQ‡hşsÒûw.nEË¹d6«}Ì8\nyêneßŞ¿˜'\r<iÚs1¹åÍ¾¿—g0cûì÷køÍ^¨tgkFQk›Êé¼tŞ®¦íïy&î@Ü›°/;’öºä	<aÕÜµ¹“r&cş|<<j8K¿pE\\(œòõ¦q•ãsÆÿTvnb±ù á*åoÌMSß<îÛvÀõtä[ÿh6áëÇ\"Ou¬íêKKpf9MR×nD\$ÈàÂ‰Ê§ ·C~b/¹<ô9ÀSĞSW÷B.6œn¹ íÀÉ?Ëk˜ñ%œ!wØó\nçÙÀÇ˜n%úƒ\n“nVÊç¾sxî’|¦ò9óòßçÓ½ÎÏDşa›Öùìn`#èB~úÍìÜ·²ïèÁ½`íæûM~=´5ó¨è‘ˆß¢ïFŞ‰¼ú÷¬ôrè¥³Ë/B7mÏúq»ç#Æ÷€¿s¥»ğÉqgæÙ³K'æÚ½HLu!\\Ûa®Ÿl'&íû{mwï½ó‹¶çõÔ‡v¿lm¼ãP°{¢7Ì¨z vâîÛˆkT6Ü±0ª€Ä©è/Å{?\$~9<>ùÃñæAÒ;¡Mª…YÁõ RÄ<JØã`[œI·æ—¿V¯¡o3,iØƒ8ıqéÑ¿nÖô}ğ[•öï…ŞÛÅÁ¸…=ÀOãöŠêœÕó¯“op…<aÅÈcô0Fµ¯o¾á^0\0öşmşæƒÓ‡pşœ‰xwtëÜ«ko à2{ƒÀÆ¼…éá¸Skp=Ã»¶«oşÕ×«<*mÄy!ù¼ÖÀ+Èmè[ß÷ÇtçÎÀnãÓ\$Åætã/Í›ŒÏKÍ?\\å·Åâ³¹çAk-Ñ[ŸuÔ×İ·#t íĞš³F€,IÌ“Î*À‰X«“É£še‚¨b.¨Â_[ÓI¬ßùc?TêMğO¸aKhŞT8P.sÓä	ñã8Rù¼Ë–³\rdÜæxqñ›è…ÊÓf+m—œ7:ußeÑ÷Ÿn²-ü8ù‡o5Ş¯Ì/xßW<jJ1àòÕÍoÁtqÍèuRuÁ  ZkÖ«_Ò4ôk`êñÏ»G‡3Ş¬;º+kœŞ;š³zş´=QÚâ·´fZŞÕ­/¦Ş´ò£\\É¸#İG½ß{nµŞmıi@9óòŞ×ÀO›˜Ãİ±¨m¶ÖÙÈÌõ^¶² D€ëa-NC÷B–“Î¸Ög[>¡Å	ŒŸ­à+\"®˜zÄ?¸[&mŞîDf@ü»¤>—èk«Ns\"Öºì„m×lE§ğclyèæ—)£›Ï‰\\õ¹èæ€d,Ù×…—àZBÓŒÆN\nlBÑÓÑßŸ6úİâú·ôqêâx›¬2H7İr%â5ğ?n¬SúºTTÈ¶› ìƒ“1c\$İˆ	t·U€0“:æ¬oˆzkYmöÚç™~'ë§P¿]ş»sA€uë¹G4N7bŞÅá:ñ‡bëÊ\\¯>n½9LúöIëŞó¯<» ÎjºûôiåË×ëŸGFî¿ û·Úh×iÖ/®`B`›îû•«Q€x°_aÉâ»îÍ»ØG°›AnÂ¥»,ã'ìÂÎß²è­Á­à‘z+ê¶\"OØ ÀÖğ¯\0;5„®ìN/¹&cäá}ÅŸ6Àô˜Æ{¡Ï‡ºÍw»ˆ7ğ’×‚£í7?.Ğ¨×÷øğpÁ{Ò“‡6»ápõÌéH|û‡ş6§‡»LÃ»7©Ô€¨¡- G:Åv\\í4\r…Ç]zºÖA¢kØÏíibÎÿ ²Í° àÂÈ\0eŸµm–êÑ¶cœ‘Û,?¼9:s™Û7Ã—«¿F.¯=€z½õuàÛ'«‡E®]d{-æ´íÊ»¨\"}sğ©“ë0c‚ÅÛ€¼vàKc öÂ·ªò#âíÀÛÆ4“3Ş\nÙğ¹›ƒVÆ(s¶2dÆ‡,1ê¥Iôg†1üd8Èİ—ôê›‘—¬Úµü\$À\\½vÿD4‘Yopì:âCtìˆf’C4fuÀ]<,ølC¦Á—b@.\r85¯®W—°ğgÌëƒGû“ğ[àí±[ƒİNŞp¶/ìâ‘õ±‹„.â7#ÜF#†ÿb7fL‘£ã‹X¥×¶/œÓ è]Ğñ©wEÏ\$(	Úa§ü9‚ùl`ÙÇÜã‘®+ÃÔÏ\n%{Û€PfÊnqÌz÷«à¸ıvY~â]Ú;±pÃá*\nÔş(Ë.W€«ûŒpY©„šóË; ÂøQœëíú—»Xmşí!z{ÀŠlØî×dGkQò›vKlš?›”>ïıÄ \"÷íı‘—(p\$Õ,\rµBh=Æ\0¸\0”0è,º¾|'Èh€ ¸ğ… Ã@(ƒv8£¼>ò¶JYÎ9'/½‹öŒŒ¼€ûËw€îŞ¸{÷R}í@7wŠã[{v:mA‘›ƒĞ¯#–¨›º4p)VQÄÜ0oFå²B÷³«–	Ë¼p™’-rP/3»ì¶H³ …ÙãˆÍÂñ²EàMDRÌGm9‚ñÜ@rÆBŒƒ\0¼uò ÕÅß.×xvÁ|.±áS4—ë9Ãı“®\$F\\o(îÜÄ–Dîû+NŸg‰6ù¨°=t\n…pHwNîàoº .èä{€˜ÕrïÙ×¯¿påO=R JŒà¯‡T«ú¡Rü†!Æ¤ T¾æ¼A{¬E®	˜<ô@~wçpÊ†¢sÛêÉK¶G-\r¯ZöÎtğ•Ãc«/¾ˆGñböíEêP:ÇÂG^ˆİpRòÅÊ©Š_€ïƒô× gû¯ìkM ì—÷siÖ\$š»²÷—îÏßª|-Q[9‚IsèñÙ#¢÷d®‰İ´z1ö\0×ÛÛ¬?H¾q| µjå—Ö	e’Ša³Ú¬ëÑo˜ n¿~º>uşí¥áûm^±…Æ°óØÍ‘à7Ÿ¿b>Û;`ø`é{è©1tC\"=îSêÿ×ã¢ç‡\\ú³õí§áÿ°fë^ÄH‰Y‡\$«£µ¦5.™º»µj†Å[5İnG|RãoğôßÒÍ şR¶¾ÌB=rzßS±ÔÃßeÁN÷ˆ1~4„¯ö“ë–>×HIe:äÀxÜÒ¹˜û?Ÿ„İpçĞúqtúÚ#Š_§>cÖÒPÕHQ.!pØğ÷îS­&:óïÛ›—§&=Ú¡L\0¹ññ­»²7DŸ[ì<ZtağùÛ/ÅÇW¿üp·Íøºš¨Õegş=üğwöò@;î\0ã8\0÷şò¦Óhì¾>;èÌ03æ¾Nşøî0…óârœ ààc|—å±Z10-´’rş=Mà}\r~ChËY\n5ŸJ/{ä{k\r‘ôhm\0D·Ioê5Ë^BãÊ?”U•RöKy-È^H`¯'Â§w,èËaågÉW•°ÀÙñXn {Êÿ–,~P<³yXòå§½şO<`jxí\0€¢û¼P?)–µdø>ê'¦·5fOäEÛ¢x…xGí‡ÄG†ï?NvzôÀª©Óƒj¦Ï¼*üG°mVÚ€‰\0o0>0ø•^\rÚ»Ä»hO¾\r{E<xmìÕÛ´h\nÑÏ&ı>a5â}´—Š\0­§<QpÔí<F ›YÆÓ)ƒx§qBÃM…¿Š*{O°ÂñW]ñ“›.÷˜î°ıY¼Éh9âÍÄ_›gL,ø>_x¶TùÚ³æ‹—›M¬98»	w9Á/Ç‡Ì{û[:ynÚã‹3~l>0×\rxÄë%ã²ßÂlØü7xÎêlÊ÷¿+H&üÏš\\vÄt¦Û}aÿŞg½±Ük;[Ş÷ñ—ÆÇL¾Àm±¼&vÈÛ?©ãy­†¸éè°ÛO—C¿ŸÜÙ»k:Jm®é,\\¿%Nlş•¸A¶ÚeÔ™è?2ŞÛ¢cı«Ãô©ÍİÒ¯¨.¦İ+ÀÎõ9 ·33Öãí“[´X–Íêi¶.>YOGİNõ?èÔ\0X 98Yßœèò…áí.? 5‰é¸Ä{€9>[él\\ç¥Ñ<âúÂj(›@¥Ğ¶Àjãğ\0”àóå³|Í@dië†ªäüXjXd3•U'`d \r‡<otŸĞ‚÷ò®iõõE„‹g·æõ·LDP€MŸûr=G¤[C\"eÑ6¿ 7ÙAÏOâŸş5²)(M°öø÷ñ Êv—O€>Jxáêèÿé^Î%¢. @=6ğÆõ=(\0 ");}
        private function printLogo() {echo $this->LZWDecompress("‰(ˆàĞPh\0„I\$‚!J\0dCÄ\0€4<®Oœaàc,D\0?ä2õN“‡‚N‚ÉÎbKØ€¨¬‡Î„’içàx¡00sBn\"I‚T<6š©ÓÊP+ScÖíæ³t€£v %r\$”PK8Ç9”â4ˆˆÔÒá8Ä2”ä1\0.DLc@áˆ£8Q¬\0	ë#…b„ÙD\n€H/d3¸üı/TÇíå{¡–¥U—­qj%îœYjÕÛõìïw¯@Q@(ŠË\\ÄT¨áê‚m#mâÓôÀÜŠÁJR­ şJ(Úç\nä”&hFæ¦æ@æOc²ü¨š\"4JáwHİØİ\\X¢Õ*ğ¶5â‰h\r¢Yj0Gù)\rƒ‰˜DfÀú;”áPv\n¥X¨WbQÈi“Ä¸ªh…\0ê;gtZb°¸<ŒáqŠ¦‚]€§Hpq\$ÙT‡Çn‰B Hv\0c1ò[ƒ¾f•&©ô(†%¡ì—\"AZ_Q*#äĞh áÉŞzbqì)\rĞ¬iš€	d6„\$Ù¤\0\"hr4F™È'pº^‡ JaŸÃ2 Ã8NJŸ£‘¦gpv;Ÿƒi@s… èôuƒai`:dÑĞr@)öA&Yâ*Â‰¼„Á\0âZEñ,rhpT‚ğ\\UŸfyòXdpn–C8†IŠ`AaØ\rà4h„8Nç8ğ\\˜ÆX~æŒJ‰AÚ]	Ş^\"¸)\"™,O§Éª+\0\\¦øy˜£‰-aqÊa1ö‘cù&\0ÂÈ°Q–Áx4c…â°\"_â :]›æ@’T!£HnWŸañ`gQpo…m\$’æ ği€0x\nÂÑœ~Ÿ`PšP‹À‘\nÅ d5Ÿfq::¡ñ¶|9În“Ç}.~¡8H\"QæXğƒéF4\n¬ºw†ĞSd`r5	Ã‰¬™à08‰áü*FA[¥ˆ._\00023’ĞLy‚pˆ]&éîxš§h¤jš¢˜ \\…gá®>!Aø:›àîR˜FÙLd”b@èj€q\njäĞ\n#»BIZz\0fIfú­Š&”x`Á©g€#ƒ'Xl0§€Êb…QÈh  @t p@‚#ÁlŠCŞU(p\0€ˆÔá\\p@\0Ä€\0B\0g†@ü1@ l\nCQ\0Z\0C˜©\0heÑÚ5„àÂ,/0tØD\r#ì{°À\0]\0Ğ(ñh@°¥H9 <\0\0p\0°~	Q€ÁÚàH0\0¡ ğÉ€#*@HøbÀ…€\0Xd\\@	 D† €(f\0qÜ\$Åğ\"¨_1¾ ÚâøÁ–=… \nÃ€nŠQz)ƒHncqb°ÀØ¶ÂÔe	P UB¤(	QTÂpbƒ OàÒG u¡¬k…Áz0=àƒ\0\0*\0 †\nt\nÀ`4AÀàâ´xŠq#Ç(o*ì\n„\0*#XRŒaê;ÄÉ\"QP'cè\0€ˆqAÎ9†PÜÀw\0üy\n@È{±…˜…âP`ş@à/‚h…ÀÊ4F¸¦â”`Ğd%ƒ8¤¡t00\0à€Xo°’Bxx\r€œx€à!Äàï!Œ_\0QRÀH«#\0,@ÈÒ\0#8\0ƒ 2 ÀÀ\"\0@H2¡œ\0‡°“c¼9\nğ4X„!`€p\0ÅÀ\0\\<a!FXôÁàNŒ!v\0ØkCÀ´Ç€Ş+aA8Z€¬8Q~Cøx\n\0ìg	1 +ˆ6B˜=…aà, ­\"‰Dƒ8à „`ğX1…`i!ì:„1€ø#C|‰q”€0i`€f…°?\0È8\"ˆ‡a„Ç¸‹8\r`\nT¡\"À4\ná?ÅĞ‹\nÀĞ„<‚àˆ\rb<T°;¡ \r \0/Àˆ`¨ˆ@\\ÀÃ\0”3±|‡À\0´K@RÄ4	€h\r1Ô8Ø2¼€`t1À1Q	ØŒà¤‹ À\0#\\\0 ª;C€ëcĞPš1Äsãx.„Ğ7†ør¢äea>5ÃX[!6\r *‚¨‹átz`¢,Çx=A\$ƒàÁøÓ¡¨`P7Å¸„`Ìx\n'\$hâ„\0A&\r€“‚ˆW¡€Ø\0hiD@Dw\r€°:ÂÈ7\n À'ˆ@°˜è`,IàÊ7ø€	 X;!lOğ€7ÈÙ¬E¼*À\0¡` ^QÜ„à8À@à	`r>Ã(»TF† \nƒ(üÀğ=ˆ¡*À ,à´6(‡ˆC`G	a*(E@0h ú)“	´@ğ2=C0‡ƒH\".X~\0Àà‰€@B°øÜ‡at=C¨ºb”_‡  3…¤Ñ\"\0#1˜3ÃhÙ„q\rÅ :T\\‚Ğ6 \\€PŠ±|4\0(ƒ¨3Q\\4€xî  C`®ÅH=\r\0Äb…¡vÆ(eQÀm´\0 Í\0ÀH\0'@1ˆĞÚ)Á€c¡po€xÀ€\rÀ40Œf \0ğÖ¡(Œ0X,AA(ˆaN˜\0€ğáôn\0DÁj7\0Ø(Ã<z‚Ğ€0JÇâXD‡`Ò8¸–\0ğ`è-Eˆn!D/qE`–aD€1l à\$‚`HX\$Ã@E`DcêÀ˜W\r!üpÀCX à`sğ”\0˜\$\"¬@PB\"Çˆ¦üY…Á>	0qã\0@qBøünÔn\naf.Aˆ‹àpo\0¤An † Á !FàzÁvÁÒ<\0’ ¶Ø`î¤a ÀÚAê€Ê!¾à0¡>\rPÁ€ D\0004\0x€É\ná(\0zàÂş\0!pXÒA^¡áöà¡`şa j ,\0\0–ÀÊÕ¡”	 (ÀX\0¡A–¡€€¢T€l€à€P\nA~€¼AfáÀ\0 Öªº\0g¡|@¬à@bÀ´\0Ğ\0ÜÁ~\0&€ø!ö¡J !î`X`€!Ğ€şà\"a¼ÀÄ	!ø@F@Z@Háş€Â`”Á(\0!’\0ÀnÀP@6\rà˜Á6a˜¡ À È!.‡€H\0ü	 \0!hÀ˜aÜa.À\"A` XÀ€¡}ö  XàÀFAĞ`®àjM€Ò ën`âà‚\0Ì!ğ\n€– ¡jB\0 ò¡¦ ø(ˆ4ÀÈaø\0`!!²`D|\0>à\"6£~àlelaø\0Á>X TAü\nA\$€üzà†0 ¾`¤¡`¾\r!¡ò`²àæ |\náx&^`:a4	ÁN€:à¨@€ €ÈAü ^\naR€~òöÀ€A!dÀÂ\0!–\0€AaÖÀ4¡Ø`²øÁp€îA\rá\rÎ€¢|\n ø\0\0@AÈá@8ã6`ÊÀâ€\0ÀlÀÁ:Á.aj\0À´à¶ÀFáÆ :>jØA(e„À\$A¸ ¾aA¤îÁ`=@ºAÁÄá|¡ 0\0Á¢\0Ö	`˜\02¡Áò\nÀ¾`ÈàøÁb@¶àæ8@À@¼Àâ¡ä J^A aÜa@áª\r\0AÚ	ÀH\rA\nÀö\0°@Ú\r€´aŞÀ@n	Á\\àp\nÀ òVÀ¾á‘d	 8Á(\0r ˜!d.á\0\r DzX`À`\n\0Äaˆ ¶¡x ,!\rAæ¡ Â\ra„\nánäÀFÀÔÀ!¦À8ÀAÊ\n xa\\€:Uá ˜€AÀ\0`T\"a¶‹fÁ<áşÁFÀª@\"@`º¡¢à Ház`H\nZÁ€¡Áğ Î ú ğ^aàÀD6B¡ZÀ6aàœ€öàDÁ8Áîàj!¼`,¡úÁN`êÀ Áf`à*	Á’á¡H¡<rÀö ìábAÜ@”É!@!” Æa:\n UÀ`xaz\rAÆ!¡š`€8€aü¤ æ\0æ¡”ÀÔ@.A<\0 à0êAÒ€ÀÁ0!!^àÀá¾ ¶ÒÄaà€®`´\0² HÔ\0AøApa¶ah	áb`‚á:¸Ab3¡áÆÁ‚N@Ğà–€Rq² d¡V ¬€â¡8µ!4Á\rá¦Ú\r\0 `Òa€¡H ô!D€<à¨\0J!¨\r\0âa&A ÀZ\0†!¤À0\naÌ€8!\\ ¤à!ÂÀÆ¶!\$!¼A@a\0 ´ ²€@ ¤(Á0\0€Ú`\$á 0\rBÀßul`â€\0îrÁ˜`Ša\ná\0ªbÃaòÀ\r@€ÁÂ¡Æ &\0!4 cƒ¡f\0!¬A@À ÌA4@î °\0> ÆÁ2¡Ü b\0¢@´dÁˆ@Ü`dá\nÁª@€ğÀâ ¨Êjx: t\0aj^à*ÁÒ\r@!u ÔàHÔ €—s€t úJ@ÖA\0úÀ€AN	A`aL:!6à“ÚÄ€:@‚ ¾À*¤á¬`« À!‚*	æ@À0 œÚÀ^aø æÀ@JööÀÁxá²~\r¡€PAŒÁ€\n\0d\0ˆ\rÀ\$`ø\0@aº\0€¶ÁÂÁÎ¡Àh‚tÖ@ÄğÀD`h\nX¢à4á¨AÖ¡jàè	€H\n…Aháä€”\r \$!Z¡àœ`  ¢ \\å!Êx záèAô®\r!^\0\0¨`¼` àì	ÁÁRP€¬!Ú êár€Şavº€Á\0ğá>@ø9œ\0öÀš êÁ A0\rálÀVhnaaÒÁZ€ôÁˆ¡Ú\rÀ¡^¡®	!<¸Ò`4µGê\0Bà’@”¡, ˜Áø	`°àV¡Úk\0’ î`ºÁ\"Àxaà– ´\r@ÄaÂÀ’™&	€öÁà FÀ°\rÀ\"¾!´\naù®ÀŒ\0~aLÀâ\0nCv¡Ü`ğ€D¡8\r` AA\"\0¡B€ä(A\nn\n@v\n! ä!B¡HaÁxÁ&\n®\0áB€” T!ÜÁæÀB \0ş\0àú¡L@^ø@¸á\rat!ä`aV@@p`Ş Ğ@®ÀÀ„ñÎa´Í€ „`|@8àîÀ\nz!\nÀ`:\0¡dÁ&€pâšòÁ™páĞ@¼DÊ`N\r! ¤Àœ\0Î¡,oN\0Æ\0\0ş 0\0T!¶ ˆÁ2\r¡>¡D`Âa!´ *Áv@\n¡Z!ÜAøšÀ¸à@j¡jÀúá¢ *€`\r`ôÁÜ¡\$É¡ÄÁN!vÀº€¡¦[€ `ú\r¡Ö ~dáÎ\0À8`Rr¡xújá\nÀJ€&\rÁª \0Àšán\0¡„ÀN!n\0\0@a|á\\Á\rA^á \0Z Aàn`Ş rApàFÜñ\0 	à4!àÁ€\0Îï\0~@ °\r€ °\rašà¼Á¦\r@>á~Ò¼7ª<`Úa¨!€† ²–áÂÁx!ˆ\0„Áê@b`Âat-ÊaZ Î\0¢áv\nA°¡Àá2AÊà0 <aŠ@j\0à²ÁjÀò¡@ zAŒa(²!Z”!Öá\0~@6á À\"À\0DÀ³œ¦àÎà `Jl!¸Á \rá \0\0¾!Ş¡rán\0ÖÄaV\n¡ tá\n`^.!k/,\0ö¡º\0ÆAn Ö!úà„cÄ`ÖÎ èà¢	a®!ªál	ˆ<@0à\$ás: 8à” â ¢Á,X¡”4á\$à*àp@ÎÀ >¡.`aB€Š À€~	\0\0\0ö!f œĞ€4\0ĞÁDõ| ²\0(A¤`r¼ÔÀ%`<\0nPS\0” 1˜8º	LàĞø\r€0\0¬\0Ï\0ˆ € tƒø\n °Àf 0\0¼ ßX¢ğx@Œpà€¼ ªyü@òğz€¸ Õ  A@}Ä@<(Ar°Lƒ4k*@b\0\0\0 .X:ADĞ;\$@TØ7Aš\n\0X`Üà#@²\n 4x ìX-AJL¸@P\n\0r‚“²\0¨ÀÆ @\$< pàiğ@Æ°{0€IØ4À´\rA`@4!èÃãœ\n`H7À0{db¼(,@¬P`,¨=@¢`bœ@,h@l\0?ƒœ@	Ø€,Và\0«p/ÀüĞw\n@œà°€0`cø\0®€€H\0 àh6À˜\0 ø@ÆØ€º`˜`:Á‘Àö Qh c‹A*à‚´`3\0@v\0p\0\nø1€ØÀ€p ¨€–Ø@oœx\0Ÿ¨©® @‚ Üø)\n n€¸\rÀÉ*Á¢©hÀ8 0€ìpm‚´	À„a	G ‚\$\0`-~³ ƒ0@>(%„`m€ÙÛ\0Ø,@€€<‚T\0\0OP3!R€}¤@èG0 PĞƒÜ@fh/\n\n@LÔà	M@ş€iƒÈ	 °\0„pr\0Œ€\n€%\0Ä€*@óxÀ8 o4€ÿĞ:Èpj‚L`ÖØ@nĞRPb^P*\0à ƒl\0ƒ¨&’\0N‚è\0à= 4€ĞĞ0ƒ\\ Kğ;A¶ô0t  >@Ì\r4 óø\n	ñ¦< ˆ@\$\0p\"„@æ\0Ğ7@TàE€œ\0y€6@ŒÇ€8 @	”\rğ‡2®\r°\nøà3\0-bğsƒ8 ˜ #È€š\0>AÊ\0PdƒĞ`Øx˜]\0Á\\\0ú`ş  €Hà•ˆ/Àœ\0Gƒ\$	€´P!Áağ€CŒâdÀ¦\r€€Ô`ÚğÀP\0Xàa¨ ì b€È€\0(Ú@AÌÀ›ĞÀt\0‚D€† € M\0 7PA×0]‚ @¨8@‚`!T\0Mh@TP>	@×H%.Ü9<‚åşZ˜\nA <‚@@ *H\ràCŒ ÛÈ8€ŠğN\0ô\$¯ß€Àb\nĞ€)\0A8P\0¥ğ6Á¤t€!@—È!À¤V`H°€üĞ{‚l`­°AdKÀP@\0Mp5€† ø`²\0x€	ğ,‚t	Àü¸ €Ö	 ƒDï0@¢5‡P`O+|\n€}‚<à½\0h\0vĞÈàÁ A:€´`('\0 ò°a€à Òx:€–\0h\0±)Ëà\0à£²J !ƒ€)P!\0v	PAƒXo @|À5\0d\0 )ÆZĞ6ƒX #€Ğqœ —\04Á Pu‚\$ ~Ğ< àK‚ü· 28‚\rĞ!”@\0ø\$¦PVìÓ\n ¦Ã 	€t /\0p+ÁP ct\r€şÒ’XHÀ¸€Ìzƒp £¨*Áj°¼`z0:ìZhÀ6ˆ;ÁÌI\$\0ø&æefh g\0pÁ ³Ğ‚p@²˜#AÂğ3D€@X+Àh ?Œ¢‰x&À.À\0P Ú,A’°)\0Hà†7p*Àd€h\0h éT<€äÀ\nª´\0@Rˆ1Á¦	 ‚¼ÀE\$ˆ2š RƒÈ\0\0nX,\r‡Ñ€h9\0ITş@µ:`x?°€TèAJá 4ƒØ\n`)¸)@¦Ûtà&ã©@&\ré\\ƒ€q8.Á^ğh€]h-Áæ0rÀÌğ?@l8\0	OPÀœ b8@êØ@‚d\0îcA^PƒÈ NW„pğ<P&·\0à\0\0H`øàø¨AĞPG€¡\0\0@Š9ç€@");}
        private function printFaviconIco() {echo $this->LZWDecompress("\0\0\0@\0000\0Àa\0\0\n€ä\0m\0\0€)P€7¡ \0€B(\0\0°0¦\0(ˆAà„&!\rËæS8ƒşm7œNgS¹äö}? PhT:%G¤RiTºKéîş}¾_ÔÊ3ú¦ö§ºÖÛ‰öÒn>ÙíªûyöÜs¿OëáõT¸Qìw²É(ï}T®3wãñşãr¾Öë×²).ñ4Å#s´ŠivÍ.Ñá³(qwO/jäeM=kgºñ¬ût¼ªÕ;İÁ ½z—D\rÕb=İQÖQß‡ó-”øC¢]åBë¤|Ms	Î¹IÒ6+º†ÅÇXĞÂì™]£˜¼Úîœâ££ÀPxx‹/\"RYêˆ^>Znwëñû­¤4WÏR¸a¶+ƒ¦áXI6ëÒ~~¾æ‘|ÙØ%	0z œ¡è		‡8v(Ğªt‡Ë¤.a«®ì¡Òwø\\9áhìxcÑâÇL@a!\nzä¡ì@§Ñ¸w7/Â€ı¢À2n\n ©¶+&éTI/2pxçéBK˜rˆ!ÉÆ ‡ç €\"¡ü\"Ã0Ü;Ä1K»QTYF”iQÈPAa1zdQê§¨‚QI¬~ô”I’q¸)ÆØ\r‚ Jo+¹ö}Ç¼}£aÔ#†gˆBxrBÕ6MĞÁÎCiÒŠÇPh-Îç`hí;‡hZ6ÁXŞñ¼¡XòxĞ'•BĞá-‘Ç°>HÁ12|¦)öv©ôºqLÉôá¶(fàœ¢˜To’céØ7Šç0Œœ5b\"‡m\\WS\\*#ÁØ–s‰áÔ3gqME	f{&ğZçÁZaŸÉn{E)ê)‘ç˜h?A8ùG”1éo·ì’g¸>Kàé4|¥éös²K[xStíèn	àùº&„Fğ”›âHTp	yÂ#†‡…VâÓ‡Ã1ÔN”§™¬mG¡ìÕ¨‡ê¦w‡ñ°E¡îç¤}Ã[÷BIá.{ƒÄÙğ\r“ÇÈÄ_­G¼•¨^Z««„fğ–›âPW°l[&Í„ˆ!ÑÆ)ŠG9@QGIÕK]Çşô›Iú>ÇÀ\\G¡\r q¼\"|„ùò”GĞâd‡¡ö½óš•ëÏ‰½GÒôûÇƒá#xĞuF’¥§w)Ùô¿f¹ö ºÉÇr;çEô@ûãeö—²¼ŞÛV{®‰Ò:gP8B(2A€*qÀªßiJ£˜~„‘L>\0ğ•#áı6ÿ€À§`¸YÁ–;`)E€îz=èø[A,A@&Ç‰»†0d Ğ?B§ q£ÂXN>€Ì*‚¨~?G³¸(ğÎºø {ª€Üq„ut'\"Bˆ… ]úE&r±>(°.*ÇàCô[r•¤5‹ğ9ñ:¦ÎÁØã¡r‰¨Òo#[y*b\$eÇ›	Ÿôuà	ŠáúÆXşÜ£H¹\r£…| r&ÈĞ¸˜ƒÈ{É	\"P‡+}¢Â(=5\0¨¯ ”\\áœ<\"ÙûSNv.À°™\rãeêİ\\@#âzZKinPÀÔ‘ÒI±ø¦cøG\rÂ4ØWëÌ\n)ä@èİ	`…­5À\nX(-‚\0Èp„5hp@‡ ø!PxÄ8ç‚|yˆ‡7Çøİ#ø\nÑöE0ûÑà\nL ,‡ğSCü{ÊB„:‡úb\\w‹+Mi ¯#ÀW	±à+Dèñ¢|xŠÁB<EX£B¬R!T)‡˜©ÌT\nª¨+ ¦£ĞU1ê9İ½'£ìû‹Ü%F°ıĞJ±ü\$Æàş£ÅšÅ]ëÅy¯Uî¾WÚı_ì°VÂX[\raìE‰±V\0á¯'†0‹ƒ G¡’#ÇÅ—DOáò<«ğùCÄn^5Æà£ÃxS\rA¾)Æ˜ßDp\n 8EHÎB¨f1V2Ç ¬C”VŒÌ+†0æãs‹†:ˆÁ\"Ära¾UE¸nb\0q\n ‡\0ãÄD\0Ü\$\0¨úú¿aø6ÆğªbÜ \n1j\rÅ\0·bp\\ƒÑ2.Áø–¡Iğ†#ÆEƒ#ˆˆD(Æ	bdĞü2‚x{D;à¤1GX·*£^`=Ä0‚ ÇhŠCÌ]ÑÿcomC€VŠ‘r/È;¢è‰¡xD¸½BL_„Q 0B8a\$DŒP–!†8Mc\$'‡ñ–CàÍ\naàgL@.\n8ò0\"`]ëÁ‹1v0`0v	pH>‡8Ë°7¸l\\)…àG·÷#dŒ•“2vPÊYS+eŒµ—2ö`ÌY“3¼ĞRà¾Y»âÜ^;D`\rB0ñhóuÏcø~Ä,½	7üä‡¢2~QÊyW+åœ·—rşaÌy—3”‘ü:Fˆø@‚ğŞ=?¨GPãœIaæ3ÄÁ6¢õÜ«jÑÈ,Å\0À	˜ ë®ôn¾ÒIìM,RŠ°úáïfçA¨„vÓà@s	 J=Fà´°›tlQj'†PÉ\\è½y£µş‘ØZSb”±ü<öÀŞò\0î:·Èç\0Dqò1Æ'hô‚ÏÒêü=‡Èï#89àP…ÃwF½ÑûIl=+¥Ê ûBÈx‰\0002;sŸ Ú|˜H1À\$€˜ß  w\r.?GĞó¯c¼zt4Cğ˜Á…óÎ¸ìçÜT¸XÑğ1ÄPî Ch€îEÉ:hà`Tn‰P,6„Èbà0AÎ25ao}¥¸xjá^+ [Ö™'%äŞÍÃ÷W<â{» R=G¨¾Ü€}ö8ûÏ{ï \\l‰p25„Ç¯@°pğÜ;Ç¹#Ôt^âŒ_G¸ñCtl\ráP.0eBäßÀ{¡u¶æø,\$`ì 1Âgiç¼Sw©{<<‡˜¿œH_P»×|¾³×\ra2Æ›£<NÁš(A8ÑálÚ ÈANÁ¾!Èá`Á´a¤!aˆ\rd ’áhì@\0xÈlŠÈï¢àˆÁ€6`À¼!€¶A	Î\$İ®~ƒ\" Áæa ¡0NOVõ¯^ 8a6Á @>!\0DBA‚`JÁL!x XTÁh`\\ZáZàbAb°0HÀt0=\r\nÀ@„À †Áœ\rÆ¤*aüaÖá ¸Âàópn’+á¾AÌ@Œï ,!,ïÜp‘	P™	ÀD!BŠ`LAJ~ÀTaQğÂ¡XbÀdÁ`V@m\rğãpê |¿à|a€	A|!îÂsA¤€!Ì4\n/¸ó©¾z¡ÎÁ–¼€nö\0004A4p–ñAQIÑQQYÑ`Ñe‘mu‘| ráo¡vaf€àÁÔcìñB}A¢¡‚Á¢:Ãïº¯B¬!à¡ÖÁ\"a^	a¢\0Pa<áà@¡Da„€O`SÀW\0ZXñj0ÒAR g\ráX€–Áœ!¼áz!öëB‘A ¡„A¤!ˆal°…(ÌÔaÊÁ!z¡@”Z„`lÁX¡vÀpAdÁt\0”á‚á”Á\0lxáäcì#ñAœÁ„Ş«ÛN°)ÁØÁâÁä¤áÖ¡ŞK®ôÊN²Ûª0oN°-³53U5d”&“]5óa63e6si6³m6óq73u7sy7³}7ó83‚%\0\"b\0Z\"¢f#o5…ÜCxÕ‚¨*ÁşnÁüÇnaÆ\\¼øƒT}êÄ!RAœê(ÁŞK¡‚aîa, Ê!Ü	àÚ ˆ2À€Üc6	á@Ôêa†!ª>“È]ÁTÚÀ^á !ì'¡æAúA^€ÎáÔf& ”9 ¢\0lD@háÖC´å €âàPáâär\nÁl!¢ˆçv.!XaÚ	À @ÌÂÁ…Bâla¬ ö\ráÔÀzšÄÒä.ƒœƒ¢DHàÈYÀÔZ4fCĞ&hÁ€BQ@nî!z²)T†  A¶	Ä |ANÁŞ€œ ˆ‚@†uÀ‚\n¡Ì@¤\0²\ráØÑ–!D!êÁF À¡æ¡[Dr¡G¡QeÈEÎÁˆaÜõH”ú^`8jéø	`Pà`€Œ¡ÄDÌ	Æ1JÜ>¨´&ãXæü\"Ò‚ áêE%Èá.y‡*AŒåO\"‡Ouv\nz	Æ³XºÁÂÆÊà®ávAêBà¢Án JeÅ\\ :r 8¡ô¨A\r¶'5İOÕàµätU‚	ìåh*šƒ!ì-ááö†‡*!H@PøAÕbbobµycçX “^ÀŠl ´v!Ç4}¡îzàöaö:„ÖZ¡R€¸áüµ¦'–mbö3^–w^õYÆ\n ªáŠö†w!Êaü	b@0À.Š€@úáÖ(!VAÚ	ô	Å@	€@À’Á¾æ¥ckÀ}Jà¢ĞV„ƒ!H!úvZÖ	„¡©ban§ÀÒÀ\r x Ì!Æ€Œ€Ä	Ê\0¢À¼\nÁÎ€¸\0°áÒ\r€ğ¡°•Ø/f˜À¨aøál€hi>¡şg®'°t¡ÚåÖ«<!Ó{ÂváØaøw¼aØ-WÆfš]Ó¨•pô.ç4Up–i9×é~·í~â:ëflÔÁæ¶¦,oj¢âaöôî\"°uşaèØaó‚x*AòµÖ¡âEØ“Ö'³N€Î!*Áê VAD·¾¥ÜaèA‚@æá~\nX`²áŠF\0¼á”!6 ÈÁÁ\$@Ób\0Ò!Î€¡ˆáğ`kÄÁ!êÁƒ!Ò¡‚\ná8\0t\0a|A Œ,°ì0á˜	áZA2(ü ! æ¼¸¼aØ€\\a×†Ú!Ü!Z€¹À~Há„á ”¬²”\n\0òÃa\\a4(!úTál\r8¸\0ÁÜ`!¡Ú€¼-¡ßŒŞaT\0Å“Œ“ùC”yK”ùR\n9W•¢„¡ÒAèàO‹À-ò\"ØÀç—È3[tá‚	9=”E”€›”ÙQ•YX(ÁôşŞÀ'›@›€\$á(ÁÌàÀáÙ’Wæ'ÄaÄAŒ¡’Ù8™Ó™9ÙÙ›™âRê@ü!AÈ÷Ÿ¡´Îş lÁ–èa¢²ÛBtÛ¡ğæ*ÀŠ!Ö ‘“ZÁ\0ŒAˆ	Xğ	š)¡&êaˆ!Ê€4_€&O\\¡2A æÀban\nÁ°Àì!œá­¬a– ê~!VÀ~Aj<¿¡2Èøà!6\0´¡Ä¡6@İ@¢¹ââÅ(¨aÆ@jNıªš¬€>p°ª°aLl V!XÁX\0dfáFÌ…Úê!Z ŞaŞn—:¤€á€¢Ã[ƒÁ± àöAHš®Á•\$¡Œ F!Jáx\0VğÂAZFAN@hn¡\\ ¼Àáê!Ü'©r Àáí³}(á´A¨Á¸ÀÒÈá\\¡‚Àtb¡vÀÁ€¡’ÁÊ(ÁÂ²Ò+Ù^)ÁÛ2A¾AŞáä²¡èsD5S¥:jÉw÷ğ)3…ÅüaÆ3a8‚8\"Œ  \0\$d SaÇ‚^y>\0~ \0adà\020\0.@\0a‚À\0öÀ \0	€6 \0b¦\0Ğ<:€\0€ê \0z\0D€¬\0å†`\0¡< \0¡ì\0\0áŸÈ€Î|ôA´«ºî\0\0á÷ÉÚ>\0Êö\0\0„üÚ®\0È€ò\0sÉÁ¾€N\0*“‹‚\0ü@2\0\0\nš\0\n,\0\0¢È\06@ü\0ÖÁ¤\0¤ –\0\0NP\$l C!¼\0& \0¼€\0\0ş\0”\0AÌ\0\0äØ\0‘P€\0áX \0ÁÔÀ\0á:\0\0‚ßÖÁ´à\0ßÉ#ïË 5ËŠÈü§Ş¡Pİİaâ€\0ááÈf=¦h€\0¡^Ü„¡EÙ¡çÈâ 4\0°}éÉÀ}\"af€\0Á¦ \0WÉÁú*”\0=˜	€\0€îà\0Âp!¸›‚ \0•Ò áåØá”Ëº*]è\0\0¡Ìœ»êÁê¼¦*1ëAŸ!Ü®Æı|Baç@ü\0\0—? \0Ä„\0ğ}jaéaá0Ö/À\0mà]ë~­¡•ã^ø½İ± \0\n`\0êĞâl\0	ş\0\0a®`\0	Oh\0„P\0ˆÿ!•íaÂ eÊaùÉú!„\02–~à¿Ö×\$\0\0<hà\0Ê)ä¡v‚*…@\0áÅêÁx î\0\0º\n]®€±ÙÖ5@\0¾`È\0İx\0\0F]\"¹È\0Z€Ùó 1àS¡íaî¡É!” <A·Õ†\n\\²áÔ!a¶à\0\npL;¨\0[v¹È€&íH8ÊP€d`\0ø@FPM€À°ø\0,@Y¸!\0uˆ4`\nx\0ò\0À08\r \0`@€&T@‚X\$€¾@)€ @R¤\0°àÈ€}0ÀªĞ\0d€\0HÀ°	pÀ`\0D\0006	Àƒ`€Rğ-ÁT\rà‰ø@n :\0ì€X ÄP=€Ğbƒ¤`í@(àDƒ	€ PÌ€h`ÀX¨1\0D 0@(`7à`gt°\0<Ğ`A,`Œà\0xA\" &‹\0 @¼?èÀ \0pÁ&XH@`\r€i\0Ø	ÆÔ\0P8‚@\ràQ`Ä°€ğ@ì\0´Ø\n&ì");}
        private function printFaviconPng32() {echo $this->LZWDecompress("‰(ˆàĞPh\0„I\$‚!J\0CÄ\0€4<æz==!àC9šA„,Qà·á„1’\nDØAèM@!!¨zÑ:Œ!£ t˜„Î	ÅÑDy‰dr ÿN§(ªtœ<p\$Np€XN¸\0€IÀÄ<t\$“H w(,(†ÄeçZ*N’H„¡a†íKÑÖ¨eu¬™çWqœL34œÃ%ËQbT1:„¦‘õ\0bO…é \")\"•Ô¦™n–!%J€Ñ%>„ShtHlm(šÑ\"aˆ¨b‰²I‚dÊdp‰cìÄÉÍ2³ºİl1ñÍŒ!!¹ƒ†‡Š•¾fY…İ‹V³ınÖ}+ÍâÑ(‰„ç-› eL”?	H,´ ˜ÀÑt	ƒÂÈÖh‰Fé`^€x–ƒh:DÁ0UgÁF	ç˜Jc)Ìaƒ@ˆ|c\0ƒ z0„°mg)ì@€#±È\"xÌ+ƒYX¦)´#›†Ù~Y†¤\0æY€%ø~)œ‡y&\"@XªJÅˆLb@\0Ö€q0€g‘|gå)|˜ä¨bÃ)p7›¡1Ü.—d)¼bFA\nex˜œ‚X:a•ax‚‚Gğø[Cy¾†È¾aÌ¦y\0+Bà'¡\0	#\$à	`(üÄaFO‹ÇöMÇ¬Oá¬LŒ&ùJK“\"Ápa\r8š€Å@aÅ¸Vvâ‰D\$HŒ€ç J‹cĞ¦6±Ğ&…!\0ÄrâaZ £áxCâqœfar!‡8QƒY<pšFªFÁ¸l0‡¤ÎGÃÁÜn…AA‚m\06€ône Ùtl„èDuƒ ±Ús@\nç0~fP7‹ÄÙ@sD€Öz†Cù0=chøV&ñ@k€ àp\nàv#\0004XsÁài›cÙr’ãJGe)Ğ\$„…Èæ0\0érƒ‡øèLáx<3”CĞ\nR€\0)Jæ8d@\ncÉYˆãèÔPœÉ `â0v|Ÿ%p¼=DĞ¬a\rFiœ@Œ&P¢F›äáqp°_›#YĞp£¸FÀ)F`'`2\$–á)|äXò/Cà>oˆ¥X‚Áñğ\")xPgFnÄùºlš¥±ğdˆDğÎgƒƒ1tf¸„K\r'±ü[#Ì€€‰£øx44GğöC”Dx/ÁĞu‚\$9\03AH‹\rãŒ8ƒ€|&Çè-€p \"\r‡¸)‚¬t„°¶;†ø’\r,> ¤9†\0…a'>;F	7\r¡	4ƒLX\0aháº‚8³ HDˆñFÁ¨@…`†…h0AÔ@ 0€p}€¨'à;ØáXp‚P'`j €Âıá=€áöÆ\0ÿh}ŒÁ*ÅÀr¢D‘4!Â0* Ì\r!ÓÆ˜ÑB(f8Fà	Ñ@3\0À,7…¸?Á*\0£hE…<Á(\\t	° .Å°ŸÁŒp66F°ºÃ´áZ	Á\0˜Áä‹¡Ş‡ÀÊnàìK\0D€È€Nè0… ¨t‰a”\r† ®bD|áDH”k‡\0>Â{\0 >AÚ\rÂˆ¶cä	\n\0(†8/C@		ğÈ‚ğ\n`Ğ\$q&Äˆº\ra\\=!ÊE³\0á\0\0/\0ğÔxsñªA@‚ ì\"ñÆ¶„\rF@µcÄ]°†9Eğ¡ÜZ\0 @(¨Ù\0ã¸‹Qæ+‡°Ä`hpƒ±Ãèøâ<ĞHÁ€ò`RÒĞT)Ähâ ^ ®3Ğ>AÜw„`JÁø#„I°…ña \rˆ\0ª(\0`jbŒTá^EX»A\0ƒ±1ğâ˜±ğ–?F@ƒ¸\\€pŞ6Á@º`F‘œ%Àd4Š!æ+À˜  y†1P) ÿb4Š!n€0j ìP„@Æ˜ <?ˆ±Ô(B¸‰Á|ğ‚/FpÂ!üp‹ñşƒ°x¼S˜\0p•ƒ€K‡J\nE p\0#ğ'…èÔ\0 m\n¾!Èí\r#À\nğ6:Á\0\"8b B>ƒøùcH]‹q&>F0W#l‚!üÆ8!DmÁ }!Ü:€Á*@dƒœD\n@†	À(•\nƒÄ~„ğ„´Ç`L°<\0Ax»A€ÌÇè\0büÒ7E¸à<ƒ1rÁàM£TkŠ<>€XÑ¡ÄDÁˆ †:ÂÀQÊ\"E¸š}‹¶;†0ŞÀ =Ñ.+ÄØö ¸-ƒÁ9Çøô\rá0W	1˜-Å¸¯\r‚°1bGàS\ràL&‰ğ2ƒÈC(ChC¸wkD¸ï@<6\0Æ+CPØÂ\0j\0001(GExr`D aU\$@Hê¡àj†š‡‚Ô D08àŒ¾RÁè‡‚PèBÀt„:PtÃeâ”\0 `ˆ-<‡NFˆ5@È©À`€0¢ÄşÃøâ è6†ğÈC0yã¼ò0[Éy?)å|·˜ó\0`;`„d<+‡¢ûÂwÀ9ğÌC`eĞ<‡ÄC€td <	ÁÆ<€`!X”ÆXÌ	#”‡\0000FÁpc+ Œ	„±&\0@A†;ğ³\n€€e³1`ÄƒŒ±\0\0JÀ\0uyÜ<‚5¤<\0Š‚ ®A€A\0");}
        private function printLoader() {echo $this->LZWDecompress("G\$‘‡“¼{€ ëdx½l‡\$¡ÉHr^™‡&¡ÉÈr~¡‡(†TT0[)%\niB¢P«­–ƒI Ñl¾­Ø³–hÙnÓ­Ú£•ÃT~ºg–h22õ˜N`3K6i}ŒÍ11ÙÆ†KLÚËj™­3³9¤yg´Í‚¤ĞB´Ú(–£I ×j¦Û-U+m¬­n5–øiªßâœP¼¨à\\›@Qp‹Ky_9›\\#2“„jSp³}(à¢â9’\\fâS™>Ns›ƒ¹UĞ{,ºåwJ¢éEİ(â¥ Dt¥HÎ¤Ù)Ô%:Ş/gc½èìy=İÎ·ƒ¹èøx¯—';©æÁa¼Ø¬‡£1ôq¹^wÓÕ¤jÆÁ´{Æùò|Ÿgáö}Ÿ§áø~ŸĞŒ\"Â¬-ÃÌ5\rÃì=ÄEÄ‘,MÅLUÅ‘l]ÆBbpŠ*\nb‚(¡\\\0`\n\0'È€`\0À\0’'€\0Aü\0 @8\$‚A\0˜T.Baà€F%‚AbQ¤X0‹G`D‰d¡•cAòQ!.„°H E Ê%„b)D\$!É %‚\0T+—FáÒQ”æa\$.02eC9–O£ø`š@(Æh¡É&% ĞŒHGÁ‚zˆà \\æ€0M‚0(ŒÙ\0n‚D)ŒkÂğfm	€‹?¢À\$X¢Vˆaà=	²F¸<Ä\0ú\$E(‘\0ˆ@aŠ!<™B@¹F\"Ã	J0‚`h)	c\0¤™cA|c`Ò%Që¡R‚À‚–g0X*‡Çpú)\0)Ü€\0	D6bÈd{š#‘ ¸^7\nI°£p\0\0Çğ \naÂÉ%ˆåàFc‘‡Y°0c!Ä†¤\0\0q¡\0p˜ZH¦°‡EánbXip’æø†‡€€ÃÁ\"â˜, h`0â ,•!\0‚Aœ\0iÃƒäá€b€°\\\0¡¤Z‚â™€2 …ÃÁN@€àé#ˆ\0ZÀ€¶\09¾D	Â\"aYp`€°½ÔàÌ&˜€(,k Áˆ&o\"€\0€á\0AbÀ;€`°<‚0—@\"-‰ñ¨aÀ‘<80•a\0:Uh\0\0\0‚X‹` Àp<œÀ&„€ÃñÙ€@&*\0\0a0 \"Á<C¤`8A	‚ @\0	 ÄHâl'\0‚€à z¬0Šñ\\ÒÀ0ê	0¼-8AJ\0P*€+¢Ø°ÀÈ­-¢¼` @ˆĞB@3„˜+€ìc@o§H'ÃPä\0 ,‡@€:Å@sá0T ÈWƒ„b\n°œˆ­CĞk±Ş/GÀæc¼!\0„‘R8\0ı&\$âA„:C“ÀJĞ+À\$±\0²ÌKP-ÀD¹2ìKĞ/ÀdÁ³LP1À„É3,	LĞ'3À¤Ñ³LMP/5ÀÄÙ3l\rMĞ77Àäá³ŒNP?9Áé3¬NĞG;Á\$ñ³ÌOPO=ÁDù3ìOĞW?Áe´PP_AÁ…	4,PĞgCÁ¥´LQPoEÁÅ4lQĞwGÁå!´ŒRPIÂ)4¬!RĞ‡KÂ%1	Á#‚?	b„Ñ0Âx¦!@Xƒp¢.Á¨R@Ğ)Œ€fh2\nƒD…A¨¨×áTmà¬6ÁhV °+\rêÈ7XW­ª´úÛ[Â½n® ¬+\rúÈ7ÁhUàº½L¨32 Ğ)\n’8µ…¨Ã€×xf	˜¡@q8‚hNAJÍ˜8ÃKllr’9°I¢\0\$a\n‡8‰¶4Úˆà–9„pMÂ4'aG0‹\nƒœDqÎ!ÂÈç¡lt>Ğ CEÍ\r÷4:G@†ã Cˆ+ˆ!mˆ…Â\$CQ!ÇŒhGˆÁ \$Äˆ¼\"dTŠD#Å0¯‚´^!d1B¸µA8[Àˆ-ÆX?Á\0ó±n2Á:âØ`Ql/	°¸&ÂĞ˜Šğd-…6ø|M“7…„Ö@ä[‰Àt-ÄóÄa\0]\n Š/xJ¢Ğ'‹ánF\0¼£a1ˆ1CPÅáÀcŒ€î2F@}C\$AŒ±’\"FhÊC8d‰2…\0ĞB”hŒ‘V48¯ÍÜfŒQ~2ÆMC8cŒ«›†èÂ£„`±È/ÅØéÂèuñ|;FÁƒ cÁœ3GQlãTiQ²5Ç Û#ˆo\rÁ¾8ğİC€pQÂ8G8ãcœr\rñĞ9†ğê€u‘Ä;XãÃ´rñÜ9‡xïCÀxáä<‡€ócÄzQæ=‡°ôÃßj¬ÇÖÙAh-mÔ?wáÜ[…¡ı¹–éİ[¯vnİİ»Ğ¢R”`%+¥”¶—RúaLi•3¦”Ö›@Àÿ ì+á8.Ä`!ÄI‰a˜ˆbÂ0…°… Aã„[ĞÆ%8Âl<!Ğ:G˜‡ÌKáºAf˜&¦‘Ö\0\0äap#q¤\0ĞEBPg	Àr \0P\0cÜh rÃèŒCP!‹`ØEXWÃÃ”kPÄC…;ã¤!ˆÁJ.Ã˜âAÄ…aP†Ğ9\" \0Qt#\0ˆ1\rÀ \"‚ V=¸Ğ\nÂ0\r°D3`P@äA‰*ÆHPBÄ 1¼*È´	£È!¡¨1‚¨”\nÁ : n1„à6€˜%`f@ Ç LR`š‚ˆ\"U…QX\\¡0‡@#hïÃ\$gĞx=Æ`#¬R\0ºÁ¸1 lo†‘?…H\\z0¬6x‹AF‡°ˆà}âHcÑ¶\rƒ Ò\0ÃüQˆ`|¡PáZ\n¦ †¸4€Îba€!˜À F	 âúà¾@\0 È€8`˜A€¤ÉÀĞ€‚À0AH	\0@Ú „ ôÁ@Áh\r&iá‚ÀæÁ:!Œ\0ÂP 4\0è¡œÁT€A\nH@‹AhA`^ †€ğ¡\$ È@Öd Œá`@º0²AHñÈ@˜á¾\n\0ò\0v\0(\0a\ràĞ€P€,€ávBÁt€„a\n ¸a@ æ€Dá(!Œà„Ad€‚àĞaÁÄ€fÀfÜ@¢!˜\0 \ràHqáP@FAj\n\0èÀl\"@4@b€fA¤@lr`\n È²\n\0p	Ä€Z\0`¤€œ@ÁN	 ZÀü@@*\r  rX\0A”á<\$`n\rÀäÁ 'ÁP@ÚáZ Ü!\"` 0!V¤ NÁ@–áÆ\0lÀjax@€. † Z`àA’Á2äAtA°áš¡b\n€ä Là A–ªÀØ L\0ÖL `¾A’ Hà¸`n Â`€>àXa”@¾`	Ä\n\0¦	`JáÁ8@K!Æ¡Dàº`P {-@’j’Ná¤ –\0p\0ğqn Ñ\0 n ” T ¼àZ@ ÈàÚÉ:“äŒI	F”¤†”à\0•\"•iZ\0I^\0ib\0‰f\0©j\0Én\0ér	v)zI~i‚‰†©ŠÉé’	–)šIi¢‰¦©ªÉ®é²	¶)ºI¾iÂ‰Æ©ÊÉÎéÒ	Ö)ÛÉàIèÉğŸIøŸÊ\0 J Ê¡J¡Ê ¢J(¢Ê0£J8£Ê@¤JH¤ÊP¥JX¥Ê`¦ ‹D D`D°h	ş	 º	à¤\rà¨\nÀş ¶ ¸Á<\0Æ€Ç`Ê\rA‚àÚ Ğ\r äÀÔ¶\r`ìàØÂ\r€ò`ØaÈ\r`øÀÚ¡Ì\ràüàâáÎK¶tÊĞ”Ğ´Ğ‹¶ ü\0êĞÀô7€î\0ğaĞ€Ú8@Î¹@À¼`²¸ ª·Ë€·‹|·A	«V	aÊ@” è	È\ràœ€Î\nÈbAÆ\nà¤tX@”5\0ˆ5\0x2 p2£Nàh\n¼`ª`j®à´@x¡’`Æ ¤\ra>@æ ô\0È!aT rA4Áh@f¡2Aj@b¡7[a=[aE[aPÃá^&!j&!r&Áz&Á‚ÂÁŠÂ!(–¡rŒhŒn@’¡’	¡|à¢@!Œ!„À¾ŒaŠ ÊÌÁ†É‚Á\0ö~’á|!”Á0Azá”P!tÁl¡dá„ìê¡œÁVaz!®aPtá¾!LnáĞÁN!l!ÒáV!p¡Ğab!vaĞÁjá~áÎr¡†¡Æ¡€!a¼aŒaœA°!œ¡ª!¢/a˜Á´!¾ŠÔ!¾áÆ’ÄAÊa˜ÁÈÎ¡ÎÒ!¢AÔaÖaªÚáÚÁ´ÁÜ·T7TvÚ×ØAÜÖAâÕáçr¡érAìÁÆ÷€“!Æ!Ğ¡òaĞÜğ×Áî!àÁæM ­¦Úm¬áòW!ôdADAÈİ÷ÌÄ)}ÿ}WÔB­äíèK´K„¼LÄL„ÌMÔM„Üá¢a®Àºá:`n€°à¼ 6à GÁâÁòAÊ*á6`Ş`!F\0Á®\0ê€¼Ááæ„Ò\rá”sa€vÚ€L Tá\n ¸!<AáØ\rœ Ö¨€áT(`è€\0a. º’A`ná¼@æ!a,Á:!zdàœÒLa’ a®\rá4\0002!ª 1-A0Ñ¦y€ºÁªzaÌ€†á–\n€Â`°À.à`øÁÈA®¼@¤`ˆ\n!F\0r\0zì€Rá Àl\noÔà´\0ÀŞXÒğb\0`ò l\n@VáŠ\rÁÄ\0XAîÁèA\0006ÁÚ@&!ÔÁb€ñj ¡(òàv\n¡\0Öà!(‚@’Á\" ô	N r\0``¡& ü jAaJ€†`‚a0` T¡\0Àà¡Ø!\nÀ¶€âà^Áø`a>p\0F \0†\n`>!è€&ÁÀàºàú=\\àÈ!œ‚@Pa6A\rˆ@@TRê ‚Ù€ÁÁì!È\0ò\0F\0va@@”Kê@”<DÁ´æÀ| Ú€œ\0\nÀP\"áÀ@B¶“<aâ†A ø\n@šâ€2¡ à@¾Al!Ğ\0N`†èÏÁbú €ÄÀ„àğZÄ!P@Îèf`<€ˆ@\"Á0`Ó.ÀFa\nAš@\"¡¦`<`Ì \0!f€NaL@ı€å£@`€L! \n öAŠa!È!hàd €A’0Àæ ˆàtA&	\0D JáX@ša¤\rÀ†àŠÀ’\\€à@”a²\0fá^Àj`A€Ö làa¡0\r€„@fàĞaœAR!ä	 L@`Áç •% ±*`L\0ÆR¼€à@’@PádA¸Àd€pá8 P``¢e ¨|­ÊA8lÀ\0€.\0 :ª!L\0¤”! \n@ÊÜö@|Ap|v@Ğ`ÌÁÌAª\0|`\rì@ØáÚÁ¶f  H“fI!û6Â7t)=7³83‡8³93—9³Ÿ:3§:³¯;3·;³¿<3Ç<³Ï=3×=³ß>3ç>³ï?3÷?©Şiâ‰æ©êÉîéò	ö)úIşjŠª\nÊê\n*Jj\"Š&ª*Ê.ê2 t*J>jBŠFªJÊNêR‡Ô:J^jb”B´FÔJôN	>,	^0	~4	8	¾<	Ş@	şD\nH\n>L\n^P\n~T\nX\n¾\\\nŞ`\nşd\0²\nàÔ`®@Â\n¡\r\0¦\0Ü\n!\\€àø	¡`¤!®¡1DÊ ¸á\$ÁĞ@Ä!\$\raÔ\0æAÔ€ò!«\ráÎ ËR€¼ â/î€Ò!È ´`´aÆ\n€°@œ\nµH4@Š4@|4@r1àj\n¡ÁVAÀ4!À?«&\nÁÀÿ0«ÁÀ«Á¼\0°€dA° º€t!œ\0ÎÀ’\rÁx\n€î\0Äa:\$Áàú!X0`ÔtAN\0¸Œ\0á\nf€–Áœánàˆ Ar €=\rûÇˆ@t¤Å¤Â!œ(!”(!ˆ'ÂpÌ4Á`&!NÃá<Ãá4Ãá.&!*'!,L?Œ <È'€| P@AØ)\0ˆÀS\0\0LYĞ€\0+ \\,ÀÂ0Z €·tÈ\n€>\$ÀÇÈ#`X	U“x »JÊAv²bƒ0ÀÓh0²À`~ åp0ÕPwƒ8àóˆ0æ\r@a’P€ôĞ1 ©`mƒY‚Ø1Û* vƒx ë\0004Ml\rCTƒ\\ äĞ9Ì\rÓT€@è9Ô`pšÔ¸]p;Æğxƒ˜\0òP<èĞyƒÔ;Àö”\"Aàm xƒÔëÖ¸~Áî`|å{€ú^é¶Í¼ Óqœİç…\"ù—Ö¾	ˆLCFòÚüMì¿C|¯Üß‹ú7ø›€\nc‚ÀŒ\0hÁL0+°€IA*0‚È\0;„A0!‚ÌÀ™˜P%ƒ\\\n@`È\0Æ`j\0p	@T`@àĞm`S \n@0‘f\0€Ùp&\0 bf‚@ *\0000*®P#ƒl \\\08;A˜	1 €%Îà 1 @¸ÈÁ.Ğ(„@ X6\\\rĞ` æH.Àn\n&¸ô ¾hP%ÀÜ 4\0° ¶\0ÚÔ‚Ğ3\0hàI-\0Â°7ì	€ÉP€\"Àq€Ü ğ\0ÀÁ®\r\0-€¼\n`°=@T\0À‚t`7 <À–ğGp °\0000ÁP5‚p ˜¨–[Àà+0€ÊĞv À:@\$\r0J€(@ğ,\0á¬‚XÀ7ğ\rÊ {.Ø THF`z‹ùê€šØ.€rğ\\`yØ=ÁH@ƒ(À´ˆ@œt‚°`LØ)\0Âp7À\0sh\r@	 Iƒ8\0ëX¨2€Tüp|\0¯ã4\0PvÈÙ -\0Œ 5 ğA¸#‚,\r Ø¨@nĞv`/Ø;È°RP\0Kx5@Ø	4[€œÀdĞ@îÀ4”FÄ(´&¼`ŒEØAÖ@Iƒ,l¥@~ |‚”\n\0;€Apä€A(%AŒi€¸ À\"Àğ\0ğNM€æ(0A	°A  xÚ\r %ğ I\0 \$Ôàà‚ €w)T\0V\n0D5°àæ\0àÏù\$¤à	¨ Á\nPQ@@¤0Áx#‚àf€P´K‚œ\0’5‚ø­l@(@˜\r o@@>PZ8À±€”@_àĞ9À(  Ä	\n8\$ÁØ	 &‚H‚à¨\r P W\0œ€Ü2ÀlÀO À7Ğp\"ƒ¤€sX	Áè i\$Àî8Àš`^ƒĞ@(&€ w(œH/\\¤”@ah\n\0002oØ%ÀXÄ¯L€ãhÄ@IÀ’K`,ŠĞ<‚Ì	`-¨\0XÀ \r`¸Ú	°Q€`h\"@H	\$,ÀŞ 0ÃB€L\0ŒÀ7è.\0ö@P€ô ä»¿\0.\nÖg¢ ô˜@\nJ\"P\0\$”@òu@“ÉPJ§V’¸–²%¡-‰pK¢^ø˜Â&!1‰L¢f8šÒ&¡5‰°M¢nxœâ'!9‰ĞN¢v¹?îÒPµ”í¥»y@îâPK¹”î¥»¹AîòP‹½”&ï ”ußŠx†^¡·„¥¯\nPûÃ”D¢E ÑâÀIxÀ4·1ãÅ™yÈŞJòw”¼­å¯/y‹Ì€±8ğ,ÎDS“X€´ )hàPØ\$Àš°O(`§8Áf@-‚ğ [À´hÀ;UÈ@´\rPiVàZÈ\r€´«@-p€Zèœ>ohà0È7À_<Ùç•ì1@.œõJöwÎÏ`˜“æ PÇ²Ä±Èh€x`p\0003ÀDÉe@´Y‡¿@ ã°AÈÀ2” g0ÀÔ¥ 6ƒœÀp@@ä\\ú‚ è‹¸]Eˆ\0è:HX\0˜9Á[ĞF*1RÀƒ-\0” <)3€Jğ\0˜\r>L`ƒè'A @‚\\àg ã)\rpW‚@@³è-0PZ‚Z §V¨+Œ6`]XÁ>PA†ş0a@&4‰`/z€^‚ıè Á¨0A‚ aƒ\rXx2\0Ò€d®\nAP”¸·4\0ÍhèøĞg0\0Ï` A ĞBƒDà‰#(	 kLÀ×°^°\nmƒhàİà+ÁÀ\rğ[ƒŒF~/ÁÊP`Rœ24†`s–\0Å89¹Pfƒ„ÀÓè6)}KÀjƒ<@Ï@9A”@tÔ@Äè:Á„vÀí\0.ŞÁƒ ğ/Áâ–pxR Ø°<\rB)Ét2Ğh<iÜl“e›@`÷Â÷	èPÂ–ŸKç79a\\¿3|/Øßkù7òÿ\0000\0]PtÀ£ĞàJƒÌÀÒØ\0ä`h`x€¦ø	@\$ƒEÁ&`È€hp€˜	J€° \0\$€n0wÚ	Àö˜\n\0¢\n K€È \"¸Á¼7‚LG\0 \n@Ö\r\0%‚`€mĞ\0Ò	À]t\nàğÁ®ÙP¼\0Æ\\p9\0Z\rĞ\"à W\0Ğ\0ğ.\0 c\0ı#ò\0n‚Ì`ë1€6\0<L`s\0€7@J\n0\"€(\0b@<XŠ	 ]ƒ\$u\r`\" \"¨\0;H9ÄÃ°¸ »-\0””P^€0\ràD˜&Àn\0Ğ-àaàAŠ`f‚x	 JÈ1\0èĞ\0@c@„P`6˜àP>Ú6Ÿƒğ\0 GÀ\0j\0RÀOø@Ö;‚Àˆ8A¦phƒñöHÀ^\r°J\0¤`Ô`\rÀ˜u-‚|€çH,Ñî`D\0Ø@,03\0Ì ƒØ rP)ÀrI‰ƒ,(\rÈ›°“`tÀ`0ø3\0-@_\0 hÃÄ²€ Às€\n€Ğ@b dè \0˜\0‚°rHà08-A†æ-\08€N\"À( \0„÷à’\0èÁŒ@g\$àä 4\0vKˆ@e\0{\"Àœ uÔ Ş\0ğÀc€l£1±`Áò O€¤ D Ú!ÀP2‚`àÈ)À4À0 \0d\0h1€ä\r!ƒ¸\0#@%Àè\r ”€ ÈŒ,€.Åà½EØ(\0÷ğC‚„ *!~€D(ÀÔ@9ÒPmd\08H:*\r\0—Ô@ÊX0ˆÎ h\0ä€ü\0æğq5¡­@¼ñ	PY‰X@3@²\0Ğ^‚Ì€-à+™B Y\0Ø€¢+\0\n–²\0²0+° &H\0½˜p	ğ(ƒ`+`-¥kÀ\\s³P2€ğC& €®X:T°e€ÀOÎ\r4Q“°c‚à ÑĞ1AÈ\n²,‚Ì€R\0X/ 	 &T€\$˜Áˆ\0°3ƒp@4ĞA¨àƒ€¢ˆ0ƒ¢Ğ#½±€Œˆ9ÁœÊaÛD`o8A’; °\r€8\0h\rÖÀa€8!9\0 = @´€Ñ@À(ê9”‰\$-uTÌ	Y3\$àLÑ8“6NDÎ™3¤èLñ:“>NÄĞ¹4\$ğM<“FODÒÙ4¤øM1>“NOÄÔù5'h(ÚŠvÂ¸ gp(!ÜŠ\nwBƒØ çx(AŞŠwÂ…&Ò¡g(aàJx2‡&ä¡ç†(‚nï›È&÷7Ù¿Îpo sÈ€ò@(<˜\n/(“Ê€¦òÀ*<¸\n¯0³Ì€¯8ğ,ND3“{èÀ¶Ëì” \\pÀº`*t@Oà&À–°TÎ”àBĞ0@|d€ì\0Ñ¸áşpm\0Ğ\nÏ¢+öÁ¼ù©çè«sË+é^Ÿ(ù7Ç<\n¯,0egÄÀp€à4 :\r*ªAÂĞÔ\0ü5\0@\r@CP Ô\n`â`kÀ¢Y@*–\\—áeÀÀàq“\0hh9\0ØğrÈ	ÀåÀÁÊâŞ‚\nÄ’5¨4Êğt‚LÀê¸ƒ¨ u‚lØQA\n Mb\0„è' P‚! Š)Á U0@­-à,@Y<hÊ	 \$ª	%lQ[\0–VÀ'°\n‚& ª	ˆ,2a`_„øÁAI„A˜IÙ‚á®À¹y’ƒƒ=_`ÎXT„Ú5ØàÌ£x3‚Ö4 Ëj)ÁšPf4ô|É£ÀøğgRà…x#©\nJx@Ú(7º\nÁ‚Ì\0áÀ8ÆÀüƒ\rÀÃ(9psƒ,õµ¬Ğ\r;ZƒT ç²ñÁ°Pqiq0på'Á²ÈĞk°\0Òx5Á¢kƒH`ØœªP¿Ô Ù¨7Á¶CNË&(<MjlPv/Hğ^¨=r,m`|/|A0 7>ò=‘ãy†Ÿæ÷_©¾—ğoÕıœ\0 ¶˜-è(`Cƒ @q\0Ø\nAR\r dÀ >ĞiŒà€Ô@DH	5 \0H,(\rTœàp	Ø	p9€€ H˜ÀTĞ‚\$`º<„PA\0 ¤ &\0~‚(€­è1@ê è\0`×hAt0uƒH «#šD~ğ\nÄ	`)ˆ&#]Œàp7\0\rÀ\0ø€‡qÀ PO\0è€ç\0h\$³€	\$@÷€7@dàP€ÈàJ°€D¡	õ!›Øˆà2{,\0] *DøĞy< 5ÖA r€h@èÀ%@ôğ\"\\‡¹Ø *0\0Cp(@\"!  ˜P8Á’zµƒ”€\"ø\0À4è,€¾	à!@…a\n@¤\nP&„@ˆ7p~p@w€Ajyà” Êh¬\npL\0000@¨Ü…¦È)z0\0À`•Àbğ\0Œ`-PÆP!ğ x\08A„PZ\08Àğ¨ w‚’Ã`:fÀ  R‡À	À%À\0¯¤y‹ˆA¦\n\0 t 4p*@1Æ¼à@F@3à&ğ„\0ªqd@p@H €0\r F€äÀ €b\n€\0 	€\0` [¸;|à #`np`Ÿ3ÁhE\0´@D @@i‚€q€,AŒ\n0?€‚B Š\0A&âpT’åûà\$€ûpÈ`k`œ	ğD`K0*¬r€!›\0h@(À}9 ƒx€YD02P	&´‚ª7`dUğA¤€T¬€QHL\nY€,\0«À,\nà‚Ô\nuí@Š¤0v€ \n`ËT­šIûƒÈ\r\0@ H	\0\"ĞX@Ûl\0Ì	À”º²ÁÚ hƒĞ@Êı»€d\nğèÀChÁÆ@!‚¤ÀÔ\0@4Á°W®À`Âj8€hğ(€2P9\0ª\0I\0T	 #`\0%-àx€´àÄÀX\nû°àv‹ªQ\0hğ!Zh`^ €hPjH`ÍØ/5æ°v på»`ôv\nğlJâ É\0\$Áæpv\0ı€3ˆ*ç¤ö€  ×j“X	pT¤8’D¦\$ÔÛ‰=Õi¼»ƒ¬&jœi›§*g)Î™Út¦ziŸ§jh)Şšx¦Ši£§ªi)îšZ|¦šŸi§§êj)ş¼ŒÕ/'5kÊÍbòóZ¼Ì×/75ëÎÍ‚óób½Ù/G6kÒÍ¢mNıwûÀ^ğWƒ”®õót½Œİ!7‡ŠMéâïxÛÇ^?8[ßÎøs‰¾<âï—8ÛçÎBrS“¾µõï°Ş\0{€@_à 8¨à`CÀ\0ô°00ìÀ¢\$x,€’ a\0ÌğÁ¦£ó€à\nÀÚµşÀjVS¶²|® 1á[æ8Xù¾:|†zªÁÁ` †”àRÁ'L +@`\\â•2ÜÌ	€å¡H9@öP?´\0H0 @pAñ\\¼W@Ğ®/3‹Í, ŸÁs‚0¶\0‰aqÀšpB<à€.\0€¨`<*ÀV0\0®`9d pß@Àp;Œàz2Ÿ@Ò€?°\0€ pÀâ@Ü ‚à:Ağu€ênLA\" sT`âÀ#A¼	ÚG„ÀÉF0.uÎ\n V‚T@±ÅiA2ı9‚ØÀ:°%\0lPIÄ*ØŠ¶Á ­°H„È€µ˜J(€J„ á(ÈAl	ŞoˆÃà©	n`œ‚ğ'ÀÀ`p®w7ÀÏ\n 4Æ\r>\0í®Ø3F²È\n-` Ê¸0‘F€dt€È01Á”<V Òx5i*\rmÀ­4ø-Â ^c8XÎ@8ÄŞ“LÔàÛ58A¸\r titÀ× <A¨\r\0yÒD¡xtğ6îjá’2 éªìÁæA›*Àô–E¡%OClpÛ«åÈø„„0o ~äÉ<-*\0PoŒà¯\0ğ1Àò\rƒX`Wppi‚´`™§0.€Œ\0001Ñd ò\0ğÁ`)\$€ùLh/A`‚¨`¹˜0y\0°Àƒ8@¼<ªD@J¨*@QhÀrë¡'€O\0Ø-@®\rÀ¸Z)ñ@ˆ\0Èˆ à\"à\0.‚l Ë\0°\rAN\0°w€4 DCfU&àpàÍ\0Ğ+\0°P6ƒD@Vx8Az°vH ò\0Şä \0 n”`EŠP7@®€‚Œ`4\0È,TĞ	H`ç\0«jAˆWô\r ¨¸@\r°Mƒ( &ÁèX\rpDÄ\"©X6À† r€¼ÅP,4\r¾ü‚h ‹±õv€~‚È\0À‘\0ğ)Á¶\0%€`\0¸&Àhğ\\È ï¨2Ê´à\0tÀû Ã*ÀxÀ ÂpÀ°\0{‚ü\n\ru:\0 d4ø\0âÔ<€¨8õ‚ø	€Ó\0h\r¬\0	ƒ0`Ûà1œ€<Ùœ\0à)`\0ğ²ÍàØ=Áˆ\nPL€Ô3àp´TDY¶x?@º0LÌh@´ØÊ€Îü]hôp<U¤€÷Ëp<H q€tÀ„Å‰Á¬@ƒ0 =P\0T`€Và)ÈqëÀ 4x\rA€@,4\r\0è4Áà>ƒÀ-iğ5Q*4`À`<Áš0(4 £Èın \0Ó' O¸4\09Rø`È\"\0&€áÏíñP4¦\r€ ƒÀ\nè€0À \0000G—© ¡HAê0d\0„` 5ĞƒD`Í‚NRD	ÀÄ å0,Aœ\0 €p\rÀìˆ€vü£‚4€Ã\0€Á˜@m€Ğ`Pè:@Ğ u‚Ä€&X¨‡Ûf€Ì >`@T‚h6ì;@=ŒPƒˆ å\0°Á\n x‚3¹ 9è¸Ár‚°mµ¨ \0fĞƒ&˜6\00'òQ`JçÀ\\ú\0Œ	 TT·\0è	ÀF\0ÈÎ h\rÁŞ\0qÆĞ º 	À¤\r`\0ÓÜ“¸,AÏ€1Ì@o†¸3Á@^¤ˆ¡à¡°´Á¸@<À`\0@d°ô ;0À:ğƒh\0SIÀv0Ğ€å)Şd˜'\0ğ¸ƒsà:€ôĞK:iÓ2`3@¡¹¹”’‰Ôû uNè®Üêé™§tî³»³­·Zë›¾:óvn¿¼°·rì[Ä;#wîË¼s³¦¦í7j»]Û.Ûvë·İÂwÜ§sÒwYİ§wâw™Ş§{ò(Úôéµ¯R›jõi·¯Z›Šng…xyâ'‰*ßZoÍö§ßz÷©Ã/‚œBø©Å/’œbù©Ç7ô¾›‹ë/°ã€\0.¸ó€¸à@P€ÇØ€Ú(`:€Ì 3€Â€*ì\0²ğ`)€¸òsàc\0Ñ_\0,¢\0£\0®È +¶8  \nÔá(î‹|ÀçÌ\0p\r@Ï\0zØ	÷€„Ø@\"'Ş*} \$§Úiê\0) ¾x`-€°ĞA€Â{ §º'*T¸\n¬€¨-D <€ªTˆ\nî'9ã@>€´Áø@s\0şä( .8ªä(PRA:P A€°8À*‹¶,!p\0E€˜F°	J	€’ ³‹ÀHAwòƒbÓ ¡` &¨\n``0Èèµ>ø\r\0CJˆ\ràU\0ô:Ğ€\$dP\0Zè¦øÁÂæ8Ê\$	  ˜&0øÀ˜ª¤°@aPyN	È œb	È Ÿrp'Àa‚8Â eˆb\0€fXh†²Iè d\0¬.…’\"¬Š7¨êP@<˜XŒ\\€èªCRj¤@eWø@f‚Ğl¾Øàstp \\Vp@VÜà w–¼¦º0`b„xÊÉ¢Ç\0u¸â€p\rR¥–\0o1¶è u òhÀw\r„6 ¨FŞ›©…ìö§ªì²:Èëªª}„*7Z­*É2j‚²T0`s€\\ñ€€¾ˆ`#\0¤­rA@mAB^b“@K«ôH@cÚ\0Ø€JŞĞ }€p¨è5@€C€b\0ğ`9œğ Pp %¯~À]®(€Gâax\r`[Ô¨@9\0\n>è`Ô€T¯	è`\\\0\\V €V€¼è@jÔÈ <Bœ(`CÚH€%¸p@&\n\0È`\\\0æ CŠP€!x¸ÀNš  3Œˆè`€¼`@V„h \0Ú€¢¨\0A\0\"ğ`c\0˜pÀbb  5l° ^\0ì¸\0t€lh P€v˜ L\0002X\0 ¬È`9\0ò0 6€¤ H€ ˜€s€Ğ qfàa\0\nlB 6vÈÀ€X8	€]€ZØ€g\0006˜ãë(€\08 =®j8˜ ]Jà [Äb´Xà!Ü€`%€ô86æ\0à`EœBËø 7æØ f88	fäêÆ€t `7à`p€„H\0Y€>p\0{Èp\r r¸\0 \0@1€„`„¸\0\\ËÀ(£B  c€:m `p€bØÀ1.\0R\0l\0€À?´V8 ##Vp@\\È`zjæà›( ÀJ=fKÀfĞ=`f\0èBÊÀH \0#äÀ<\0* \"à	\r€I…Œ`àI\0lâØŠ7°\0@@\0l²øˆ×Š€@%€bƒéw€+\$…ŞH\0˜ÌA0àR\0T\0O® 	D£\n\0F²,\0 `E€^(€€À 2tP :ğˆ N\0È`f=,\0\0`4€œ\0` <€x€y\0004HÀop€!aè\r ˜ÖèäWÚˆ€Y€è€ #€BĞ€9€Và<ÂĞ \0¦\0p`s€\$  [€ê \r`ÆR\0€@0\0tˆ@z\0P¹È\0\r¤H€\n€ŞØ jÌ\0 \"\0èà`\0`€8šA€r(˜	À\0\n*€ÂP\n0)Â`\0€H€v\0À\n@\0˜Xô2\0 	´P€#\0N(àcT@@*b`u¸à\n1²€hšè€\0n˜@7Ú\0 ·À¾à`€€–Ø \n\0á`6ª˜ b:vÜ©5`l%l»;s„Úô»cûÄİ¦bMóü'X®èu£ug[®ôuÓv'^¯\0vvça¯v3w§d¯vcxgg¢ÿÂòı¦²ÿòóP\0¦¿\0\"ô¦Ë\0RôÍí¦ØŞòõğ\0²öMó¯hßJöĞ/rßrp·ã~p·ë;~ğ€®¨úà*\0Èø\n@5\0Äp€4€–ğ\rÀ\$à<€|ØàFàT¨€D\0fx bX&ì€a\"”Àh€\$nh¹¨	 j´‹ÀŒ¹\"ğ ‘B	@®oRæğ£b0øÀ˜•Ö°ew\0lYˆÂp‚l ÅñÄpÈà\\j	€!ppÀ\\RpàQ\0º  8`x U\n° =P\0ş\0M€ÈxÀ-®ª{\0H€¬*ƒàt(C« D8Ş(· ?8îÌ :¹€E+ğ\"~e´~è³p:\0Ÿ 	i÷À§ª}  §ŞÖØ €²ÌŞ<ÑñÀ»ÇÆ\0©(\nª£»Nà\0m'\\¨ .`\0d\0¨(À/€Ø€\r :DàÀK( KĞàK€àˆ 3v ^F àU\0Æî¼h`\\Œ­  ^€Ä(%††X`¨o(¸èo8É 5£ª(†H–ú€Bx¥½,ÅøE¼X[ÀCJº€ g[`\0t»î¦rš`_dx2£€”`éF :ˆ€Zâ8ày–Ğ@g – z°`€o¬„\0xÚ„(àvê+©€x”^¬,ˆCêêhÙÌ‹\rš±{iº ^ë#L„ê¢ŸHSË Ş@zÂäÉ*J‚2Rp`e¶@àQˆˆ V\0êØ\0ÀW:8 y´J\0f\0ºX¨MÀ€´  m€n° wÒgHÄ]\0Î`\rÀn¤¨€‚€ \r€j \$\0006ÀL°\r€l@Ù@T\0RÀ&f€W\0b	@[¢¨\ràÂÈ`P`@W\0ÌÀf`\0^\0Ú ˆ@mvº[ì˜9Ø@[€à˜\0”\0à8©K\0}€ì˜ X:À 2¨\0 Àa€2¸@Aâ‹°`e€îˆëzÊÈ`\0Ø\0hà`˜\09L\0€À[ØÀÀª¾¢@Xª@ !€8\0p`Bäà E\0ø\0h@\0Î àX\0â˜À}6Ğ`p€ú© )H(à\0ü\08`o.xà7¼\0 2¨\rÀH€HHà#~àáJ´°À€bÈ r\0D)˜2\0	> `B¦\0€€f†–ù .àá X/7€l\n€)&ğ\0Z‘`€0Dxàtp\0a4„Ü¨ &\0ä\0h\r@Œ¨pà8ûH@\0µp	@€d \0C*òXà!–] f*š€`b˜€C¨ƒå€E‘N‹”@\n‚à€*\0† \n €>`‰I’\0€	 €œï\0mG\0Í0 !ä€`\0”X\r€q\0N(\0,†ˆÀ\n4`@’\0šb`vè0à&#CÀ\0Ş‘`\0øÀ C…Ğ¡@ ²\0`K€xøì\0Ré&Úœ`w\0üÔ¸€\0p¸\0I\0!šH .:NP\0€ô``=\0(\r\0!8†MÀjE\0H+R€„H\0’€°c˜€€Œç@x0@4€RX`s*\0UˆÀT\0Ğà^ŒØ )\0h\0Å˜0` Æ\0ØàG(\\N\0¦`@bph€OÄ¦àó¤­°\0p 1€`2 €qˆà@\0;å \0¨àb\0q À.‹i“A\0<±¸Z 9¡#.\0ˆ€„\0@à\$¬¬£j§BrX xì_ ?î\r€Dˆ\0€dPªÅ€¨ A Èdr¿pp‹¤€`8@Y€±®Š2€h€”X\09`€ÀGZØÀD€ğÚˆÀwà¢)À=€F\08 p\0¸\rÀ|Âm	À<?°u,ËQô&_Ríñö.âÿîmÔ.êİRîíÖ.òİrïmØ.úİ’ïíÚ/İ²ğmÜ/\nİÒğíŞ/İòñmà/ŞñíâHš«ÿKË?úšÜ\0Î@›\0ËÑ@›<’	@ ¤R7Ç Ã|Ğ¯j½»}‰ÀH@½â÷Ëà/„¾\"øËä/”¾bùËè/¤¾£°Àcœ®\0Àà@0€ÅØp€È-€3Pì=\0004PôM€5Pü]\0006Qm€7Q°\rE-€Î`€2\0ØX 56ààT\0® @)«FxÁï€¶d@j\0‰Ø\"¯Ÿè’m€tá©U@ÊÉ°\0òkŸ¿¨\0Ièrf‰çÀ!ÉEô²`\0‹&\0ÓŸ\09ğ	'¿\0–E	Éù€¨à€-Q `r€ÑØ\r‡©/ºT \n…H€¦0oFø@)|€·9-Ğ7\"-ĞEL&ˆÀK86Ğ’M€Ô;0€5Xø\n X@@E€ğó0s‘°VÀem‘W\"ù[njãœ.oB°&\0[f	°©\0[1#£@ Æ0@(€eÆR\0€d€ƒh€]ˆ1Ğhˆ£D£¥†€Ø@È·’ê€CÒp j&˜ L¨°Àj°Àl–|À\0k¬È€jŠc\0f²2±q cÔ@ vÄ³Ãf0àfp\0fØàu¨ˆ\0k±ÀØ\0sÀƒrQÍÎ5¸@r—š8àvÒ(<…ÕâÈ˜@ï\r¸Ù`|ê¢4 {™ÄÈƒl„HÛğ¶BÚ…,°Aº±,:*²P…¨€r’Øàg€>à\0wùÀ\0F\0p,h`sK’†àP\0zØÀV\0¹,ã¦ÀFˆ\0ÀÀLTÜĞ\n ~€8Ğ‹ö€êŒ¸ y€ìªPˆ\0°H²j\0ªÀ@t\0Œ(@R€hè ;6h@-\0ª\0à FŒfH €”ËL³\0`j€P†°Ü4,h b¬Ğ \r\0˜JĞ\0S\0<h\r j€P‹àà 9\0ŠÈ@U€À¸`s\0¨` YN‹ç`F¨@:;œ(àh€dÜÁ@[Ï\"h >Î\$à iÄ\0H`B€vÀ_\0 ,€RıÈà5’Ğ\rÀİ <]€'0h€sÑè\0ğGè€ø°\0004Ş À€F0`p(\0V\0\"x\r€v€ğ0à`€Ìl¦ 6Qj€@B\0šx\0007¨¨à¤>X\0}\0\"ÛiÀN&\0ˆÀK€¸+Ï˜ˆ\0q[5˜`\0`bJ¡`F¤\0 kh÷ğ-¸\0¤[C`,HpêØ¦€\0a0DO €£ğ €´ÓÀ\0•à `€x˜ E¶Jà!Â\0ƒ¢\0~UHm/\0ú@€@Ò\0¤Ù ~€`)\0‹°`2\0„,N\0%€\\èU\0„]˜	¾  '€Œ@N@s€`` ZU(PÀ2( +ä\0ÄV€F:à@D\0æ0 y\0° \0004H C€G@.€†€Âô`€<¬Nà›\0j©c\0Jiø c(¶Äa€KtU^`l0P G\0Ë” ¬à3BÄ\0`@®Šâù\" H0Ğ\0001À	@\r±r³€@˜ów€HX@Rl4ó<\0½GÜ‰`Cà#˜FÒ3P\n`g\0`ğÀi\0004Œh @©hàQ€ø *‘‡ zà`'\0‚°\0…€@\0000\0`	'ğ iÚ( B\0Võ0\0RÀ\0¸` @D\0°Fö	r\0r83’h@€H\0YÆ<Ø	Ñ—\0DAÁ€J`\0˜ a€Ö Ò¸@\0D˜0€ N¦!¥€V‚8@R\"à }8HÍ‹Òà\0y€8€ihH\r`OÊûi5 6ìM\0â-€‚\$ô|¢Qé@±7/ï·J»‘ÖMÔj»Á×\r×v»ñ×ÍÚ‚¼!Øİ¼QÙMàš¼…mãĞzŞUíæĞ‚Ş…méĞŠŞµíìĞ’½C{©·7À½t©ºÀ0ßDi¾Pª½Ô,·åBäÔ/·ñCkê·ıƒ€.¸½\r°@yí€2Ğì5\0003ĞôE€4ĞüU\0005Ñe€6Ñp\rö.€ábø60€åcv2€éc8¶4Ğ`H¹,è€8P@\rí\0Üèà6äXÀJ<°\r m00\0MÖéàM\0¾8\0İÑì@À*¢: ãÑæH6JÂ)¹\$k\0¦âúƒU×².`€/Îì@:€¸ä•`5Q %\0-ö€\nï½4Â€!€¬'Â\0€| à*Äğ\nŒÙŠÑ0±ÅTPñğ Qft»àÒ€r\rh4´WƒN|x¯àÇŠ°À\0(¸6è€]€’@+€Î`\rö0\nĞÀJ\08ÀD€|h€c9S]¹–æ¡[`K¶Ğ\$`O•¶!\\CÈÚ±\\ [dÄ8À^Œ,Â bŸÆA^€cÂ@	€_” f¦pàb@\0h±V’`j€ø[¸àg¬¤ÀÀi²½) R®È`l²ìy–ğ.¬o p fŞ¤8¶ãKhJCæ˜\0yğp#^\rn5x uÔh×Ãc)Ü¥¬ƒjº¢„ğÛÔÎ¡GLù|ÃyÓM#«2€B2¡A6@@.¶2#à]\0 V†°`kf@ [ZØ\0à€H \rÀ\\¨ €. \r€ml¨`l èÀ.\0d(@QX@<\0aV’Æ€©l0`Î dh\0À!O‚(\r\0'\08¤¬ 4€Ö0`u€˜h\0¢å\0V \0.\0x¸`h\0’X KÀ¶û\0\n\0¸€€@AXP€‹t8`l€ê° 5€¾ø c\0¢= KÊø`A\0Jp¤Œ®À~€,à¢P\0)#Ø0À\r\0b@Y\0: àäÈ\0v@\0H`\0˜\0À\r`=Ô\0 ¬À\0\\€€ÊHà	\0®à g€ËT0À€ \0Ø 0\0Œ\0˜@\r(\0à\n \0æ\0xà\0ŠÀ&°˜@\r“à'¸€Aˆè€[à@6š;œ€[\0Ú@{’(`\nœ&QÂè€b€`˜éŞX#˜€>€J\0¸	\0€h`fÛâ `L€Š˜\0i€:J‰\r\0Ü@€Y€˜° @È@ğà\\Ğïµ€˜@B¼Ş\0@d@\0\n€L›i`e€XÀ\0&\0  \$\0æ\0@\r¢ô`\0\n›8‘\$±^ À\0R\0–`\0¦3\$ğ\r@AÛJ}€2\0pI¼¤›\0|5Ï J\0Ê8  Ä\0  \$€HmBµ\$ƒIé`\0Rp`r  `'B.\0à/àëÂ\0àTæ€©\$àM£„KJàH\0¹\\Ğ\0ú€pP !9pØ c€˜ØÀhP“im€”øØ€J9˜\r„©€J8@r\0°¨v€\n BûÀ rØ *-vV™YmlÕt‰€Y#ö@€T>nix @\0öH R\rŠ\0PÓJöw\0PÀM\0X	 X\0@H\n`R\r6\0JP\0\\o@\0aÔp˜\0L¸ Z6VP`TDkL‘¢á\0×tÈ	l9#ªgà\0~àà]€±ğÀITL”6w!\0„Æ `€˜@JPHàN`Jê¶@¶ÚÙ°‚\0HØà\0ÒLĞ`9£_dÈ@\$€Àe¹`J8(\0*j\0007Úéİ‹´€;û‰—X@Ãü\rÇİ@ô~ÇåA\$~”ÇíATÇõA„”ÇıA´€È¼“ÿ)«¿ø¼Ãÿé®À¼ô\0©±À½\$g}·¸½S{é¸7Åak|¶È7aŠ÷/vß…PPºßµ/£aí­ÿØƒ¥ˆt5Àqb56\$PëC½4=ĞûCı4AÑD5‹ 7Ø¾…Œ 8ØÆ•Œ 9ØÎ¥ :€íXø€ñxà9€ì0\rà:\$˜  1AªÈ\r@Z;è	£#_m` h€~Ø´[°¼œ »Nêy\"²Ù·fØª§Èƒ,lf¥™¥TY‹f\"}6b€„| ‡Â\0‘eÜ;ÀqÙh\nVZŸ\0Tü—%Èéï\0001€® \rPF€Ü™R>\0ôĞà?¹-”ÀE‚`\nì#¬{Øà2³„h\0I¨À[2qÈÀ6\00@/Z-{_Àh©°§şm…2ˆà[+\\¸.w1\$9ü`ZÆ°(€\\\"ĞÂ ^‚\0Â aÚ\"0ˆ êŸ¥}¿¬ø *‚r\0g¨Öƒ¡ h˜\\@f¨5¦6š²¨€lB´G\0l¶»¸ €eÒÑ hŠ‚H`xœ( gjˆàyªR`gìøÀ{ÄØªÏæÇ`u²#\nò×ÈBÉ+º¬Œ„LÈEñâV¶2>_N#…õâ:_T°´Ò\0˜ÀÀ-r(LÀ8°€l€pH\0À52 `v€dTœ\0\0´h@YœáB€–¨@3:à ¨À#áT²Ç\0&\0Î˜Às(ˆğ’‡€|2@\\(\nQŒ(\r`S\0U\0àYDúàJÂ€h0c .€šÒ¯€k( †¨`K°ˆ³È€JÃØ\0\0¸À`[\0úØ\n@K®XË€¨àp€¸H€\$Å`°€W\0Ò`@U†°	 X\0ˆ¡¶\0tœ¾X \0ä=÷`9\0ö\0X@/`L@!ªh@d\0Ñ¶ 3\0¦\0`4vĞ@€v«Àà*Vˆ 	€rh \0¾ğ\r\0O<0€W€h +€xàx€¸ @`\0\0(`;\0´ø6¤€ğğ`r\0à U\0Ş‡x@eœ`‚a\"h÷ 'j¢êmp‰ëpü Ú\0 ¡m(9ªP:€¨Eà`qY@\0àT€àl\0î\0Dí¬\0 g\0°0 ¾´h	@ôˆ`&\0àJ\0p !ÅW˜4Ü\0À`N¸½5à³ïœÀ`#”À	Ğóè@À)Ú @€ê\0Zà&\0{\$\0&EÊÈ z\0phÀ\0ò	@¦€-\"\0àÀ\$(„@J>”€1\0¸ X€&h`b\0@û8ñÖæh@˜ MT\0`àh€hXƒ \0„H\0¹œZ \$rÎ=!€€<.à€*@@¨`3€P8 :\0006¦/\0Æ( 0‡È ClèĞà#¨€\0<ø\0“{8\0TR‹\0\"\0œ˜ U°¨¨\0\$\0\0H–8gyÀ=ÄvĞ 0\0004¸ày\$\nW@A’’ÀÀY€öà‡*  	€v(\0U\0LÀ ’TD•‡P#)Izu8¨ U\0H¥@F€xë{€À5Õ :€¦H@\0^à`–1\0R\0\0˜€\0R\0€àN®±÷\0w\0n`ÀNNP@<\0ˆ`j™(€rP@€Tú(dY€èI8àJ 8 \0’À 4æ\0à€h\0n8à€\0@¥x`³Vµ\\G~ j€\"‚*GÊH¶\0007GYÕöGŞÿ-ù¿Ó`d~ïöØ!Ûş6\nGşÿ­ƒòHAõıĞ…d´#_á …úH+abö¯g\0Ó}V@;B´òØm!%‡Øu!U‡X}C6t4Ø‰Cu9X“b]‰¶'Ø£b˜à7€Ø €7€¶äÈ\n GXÀÀ!@X SâY\" W#14 YAÖ¢\$×j •¬VÁÿ%l•°½!Æú\$o \0g8@G\0lÃp€Vƒ˜@ &DP€L£ph I`TH 6!pTa±‚8t[»‚`ø'\\ñóÆ‹f,šÖf§ÌŸ@vb”PKë‡•×Ä\0²€3‹.c\08€š€şT€µàs’/	‚ñ°v8\n@rÁZ,\0rôÀ1Ì‰ÀÀ=§şÀ\rî&\0à @8ÆØ@p€ø<°¥%\0ü¨ec’äø F°àM€úø\0002¬zxŒŒ8Nî°`èØÀÎy\n1`º:^j.Cà•ëIXt––çø	`§Š`\n€`˜£@+\0bá ü¸\rËÊLgE¤ 8’{Áš¬Pg  @ÿJ˜ÀY:,à¤•³Àg—&X`ex€eZh iˆÀiàà vÆ\nÖ¥ã—<6!EÙš„\"n£ÚÈ6àz¬°@ìŒ¡8Y{åñZÔ7›\rŞ7j€ƒO(\0—ì€øÁh\0rH 0rHãĞ¸Ãç@€€·ø ’€¨€ò@ ’’€ €şğ\0YL€ \"”ò€¦b€¸	€ ~Pasª ÀS3™-À O0ˆà4€HP\0€rX`8ÂP`€\0øÀ?ìàÀG\0Tïà9¨Øªî`÷ø\n&tl\0ğ 4R©kÈ`zZÀ2m#\0x\0\\põ60®\0~€\n@€NhÍ–P\r 6\0î  +ŸÉi( \\œËÈ`dLh\n`#€à\0à@[\0øD\0`U\0Ñ€B&È`.àĞ\r`WtÍæ ;€Û¥éW€UO’À\rµø€ÌÀL€Ê8	 Cì(Q\0x€k€Î¨  hPGâ€tPà|\\\r@\0ğ`”İ= kÕ¼xÊÑL( \n©”#CÀ¤û8Êä€&Hƒñ\0À€RF\0``e² 	 €\n0FïÌ\0Àl­Â‹Q«P€àÀ`\0ø@r\0 ¨È™”Ù@ \0:À—–°\rH`€hp\0`Ş¨	oÃ€°,@à-\0I®ÀàY\0è\0p@mXÅØ eĞ=Sõ†l@8\0‡‘ˆ dŞ€\0à‘¢á¸\$\n\0¥; 2\0bP¤{\0p( \"”x (€ˆÑˆ €(\0p€Y™¬•òäJ@.†ªàDçxøU€`À@|`\0oY„\0‡P%²Ù€3fáÇà\r k\0r`X; à*_^`¨6’HÊ@(`Nğ°`jÍÚĞbû\0Ö8¦€(†ü¦\n@\0œä¨JW€lH€}ZY\0aXÀ eÎ¨€]\0D•p`^%—Ø¹j\\mN 1Üè\0-\0` K\0Øl‰áişØ`nx\0:óm;ÄàqÑš¨€yV`\0xJ á€ÖÜ\0 –\0À/Á¶X Vdp\0®¬Ô€V€0` eÎ\"-àM¹y(ù@Ÿv M9dB\0 '£Ò¶p\nÙ€d€n8	à\n\0ÂT v‘\r¢t	‰B\0÷ä®Û~e€tX@ı€ôXA-tXA]ôX#A‚tX+A½‚ôg,ŞD€ÍåÈŞtMèÈŞ¤ÍëÈ ŞÖt0_ë\0şùÒÈ3B”ƒ”*gWa¦uĞP¶ß¥ĞPÃ]/°k€@>¸T KPâ`VØœÀ\r\0005‡b v*£D\0ô@¶`\r@oXœ\0€4Â\0 p€¶@*€ÀKèÅR	- !€µ&„™àÉĞ3`ìá+ ÑW.ı™àÒ€z\r( Ì/‚\nÀÒ€¸\rx¢Ê0@'Â	àn¾fÖ\$ e~H@5èTJ˜ D€—X`Z¸°sï‚V¨ãçÚ³™`[9’¶· [(\nÀ¢.Úã[ê·¸x€PŒd€ ÈPW ˜abè\0Zˆ¹ü`]Æáü`^‚ˆÅ€bY Á¹ä~¨Â\$ÍnˆÂ'óo¿ñü ŸŒXàø\0iï¨™^€ihõ¸@hÒZˆÀ^[¤è %|Š€h€œø0d¤È`hÚBšàjZH£¢\0kZV¹œàki:  i¤¶–ÚE’Àb´R• bthŠkˆŒÏ BW\r˜jÇˆ@fáK°î—Ş\\05Ş0àyĞF àuèV©%ĞëˆPÒÚ]9t…ÕB¼5ğ<´bêBÀxß „hÙ z\r˜6pÚhI!5¦Œ¯˜‰²ïåïúXÁb3¦Õ­¡)ÚŞ7ª ÄSLPŠç¶ `\0¨f2€F0 j€(\0)k€x€@Øº c\0úÀwØ\0µV€`\0êøÀ€65¼ '€Ä €>€¦\0¸ô¥2>—€R;–Àó+\0ˆÂ¸ŠFLPÀ\0\nªW H†\0è \0š¨HÊ^ô,x J\0Ò€\$€¬\0ø *„p`7†c6 2¹ŠBø j€ÌP€+\0\$  G€]ÅPü`kZ¨íàN…SrPÈo\0(j€`àc\0M;¸\0à\0¬X\nÀT’>\0˜O€àÀo\0ŒĞ€K H» `€dèZx\0èx	 %I\0\"€ê\0ØQ\0ö¸\0007‚\0ˆ@€ªÀ×\$B\0øÀ},X W€âP`Ä?° [\0JÀC€``V\0²€ cö°\r 8\0ÂWğú§ğĞ`1nMä@¤r€õı€n* À‚ˆÀ\0Æbè <^Dø`ÆÑ› NFğ ü\0¸à)\0è`e€ÜÈ€#€ÄàvdŠˆ =?%PXÒp\0Hr€bóò@˜\0€\nK\0//àÆ`\n`	\0o‰@F¡æ €r€¤h€t±ª€H\0i¨ +1¸*•y€h˜ ´âÚ8€x¼—tÙ92ô €ˆ˜@!€š±€Hµd@	 éİÀ2Íä a¨ìq9jÌÀh€\0Ğà6˜ \0h€n€\0à@ğ@4\0006‡E €ê\0±\0€ ©„ T–@e\0X\0^€¦u¼&à/XP€=,¬à2€dÚ\0PQS˜c!ÀQ€ü\0 @fL¡¡ïä¶¸€à F€Ğ €^òäq fr8@]Hˆ`\$\0B`Qà|ğ`\0<âì`w|à	@Şà€d\0øYƒ±~é–9\0<[@€S€ì  €4à€VMÄØ@)2¸¡ø&v€ŞCÀˆ\0Ö@Q€†ğ	`F€jü¦`\0f°\0 €ám &íˆ oÜL\0F\0006h\r\0€\"©€~İŠ(\0006h ¦F˜`&Q@¦p;GÒue9Âî=œ=óPFÿEÏõPRÿe÷P^ÿ…OùPjÿ¥û7‹ &æ™Ì7™Ky÷õ·¥h`äP°5€lĞà€zH\0€æÀ;İ\0ˆÑDd€Ğ\nà\$ğ	 g€ÈPÁ´h	 o\0ä¡P %ÄÀ´@=8–ğ·@Ap÷s ÜQÖmP	 t#  '¨&€\0DÙ> ¸`?€¬ P 6Qª r/½ƒa &ÉŒŞ p€ƒf-˜2hPÀÃåT\nÀ ±‚Â°%E€\nZ€§ÚPÉğ¸|X&r¨è\nÀf§ÔğÀZ€’èMèÄ\0Ö¸`8\0³3`=\0ìx <,\0@N˜À>RHÃô\0¦€~é\$ `Î(€B–¸àh=Ú `E°è`m<`ÀO¹`qü\0G¶å`QYÀaH`¬32ÃHvz\0â?H²'Ÿœã…eöøø¹wr×w;à>U	[â¹‘P&'ÿnæ\0Kèõ;ø`M†²î OŒz¸àQ\0 ø‡¢LpÀTüR€Uğ,Y_`V¨Á\$@€Xn”! YÒV°Æ€\\Ê\0à] æ@`è¢¥z˜W åz9ğ~Ùü(¾Æ€i ÕX`i†²™` j \0b¨0Àj€Ö8ºIªK°¦HªÈ–•i.’ıÄ­JàOÜOªXà\\˜ğ`kˆãÀnªĞ`i2\\Ğ;}ø‚`@hÛáÒ lğ¦]`€pÂ¸ƒ³eÍğ·jğ vØ@×`t—T]±vî¤Ü§v™ ïè„|®chBÎ§²ziÜ¯®©©óÄD.)é»Äc\$”Òº»°MÀrš\0Øà78Ê\0\0\\ à\0004˜—\00060€lfHİ’œ´\0r¯ 0zĞ ]\0ËÈÀ -\" ¨@{\0Xè¾@y¬¤à :.p`\0Ò\0 \nÖ¨`2¦l°€3€\\’vòîKàK`ø@2zĞz jfˆ\0`r\nØÀF:ğx€I\0\0˜`eÚx8@P\0ŠØÀyªÕ#q;'Ğ \0ÎĞk\"Œ¸)\0B£à cl˜kA\0`Ë•\08»ÍX	 I€â àh\08è  €*Ø €¦H\0²È`-€ØÔ‡@q8¯ €&0	`Q¨¨€#‹èˆ€†\n¾\0dq3È\0	tXÀ\0000 àNğ Q\0Ü\0è`€Ìh\0]¶ÂàX\0Ğ` M\0ö°€6Êæi \0Ôp€HºìÇ®¨@f€˜\08®%\0Î²¼q\0º	|Oè™­ M€øÔëªÏM\0fh>€exø\rAièÕM¬P `F€Àˆ0Ø€xˆ0m½ô¨€f†Û´x ¬p\0ÀüJ€*À€¶À\n/¡\n\0Á# WĞš/Àu€1Å€ 290«\0Â( B@ào\0˜ˆ K€Ã•@\0ÀL°\")àL€¨`dYÀ\r B€CEA´ÀJº\0à @Ò` 2€0 R¶\0`búMW\0+€XÈ²{”w@‚ ğÀtØ 2CÉ\$ğX5\0xà@ú\n`€!\0NJàX€Ì\0¸ñ\0Œà|\0ĞÍ@	`<\0ÔBµàPF( +\0(·\0007\0¤‹Z¯ˆ¦h W€\0\\xÁ÷€¦Ğ\n@Ol>\rÆJ±‰\0iàã\0Ú8	@,B@À8rë`Bœ\0€C-EÍ€	\0(€\0 \n 	5\0ˆ x r¯ˆ0àÀ6\0FP\0o€¬ŒÅ`\nj #å \0¢€@&€(©2„¾x€l6–ú +€x\0  €îa¢€z#eñFÇÀ@b\0€\0 hÌ’˜˜\n`\0Ó“ğ² h Zæ\0phÅõvƒ>\0z¨\0’dù¿€\0¾p@‡x\" ‰‚*¸# ‚:ø\$ ‘‚J¨\$øS¹´	@à”T1\0% —•¶Q[r(SH°& ™‚n¢@`œ‚v3dàE‚|	è'à §p\n(˜\$‚Š\n8) ¥¡¢()À¨¤¹Ğ*`ªŒ`*ÌP®\nÈ+`®‚¿Á@,`²¤¸,À<Ò@@?‚Øp.\0¹\0º‚èf€. H‚ğĞ Nö `¾ü7‡\0V\0000@Àpèà`´  Ã\n81 ÅƒJy \0´p1şNƒ&¨2àËJĞ3€Ï'™t™àĞƒF\r(í ÓP\rP,¼˜ ÌY¦\r€	 qF(— 	@Ùşšp´@7‹n\r¸6Î9\0Öz¨7@Ü€º8\rª\n€ğ Ğ`-ûûà,‹d 9 åƒšx: #¢=0àzƒ¨°;aşäØ;@î¼Èˆ;€ğ¦FºdeæS8 ^vˆ¿Ãzà=?ÃƒÔˆà^ƒØH=€÷‚‚p>\0ùaòj`}6T‚˜@gê¸@rğĞ?€ÿ¨ğ@¦íiØ@!²ØÀn¼ êzsˆ’ |â\$@Û^6ãm3Cn\rÀ£rğş^\"èQ¡G,°eö„cşŸúŸêÿ­ş¸D#y\0ßk„.’Å!j\0Ç'ÀYè8àO€~°5*Á €½]p !\0P€\"\0°©ˆ\0005€;À'˜ä\0006¥À@|\0Ìx\0Pp’š(\0à`%¼P\0 ;Àp:÷&”*p€&\0000€‰H\0 À\"€<\0¨\0Œ®êõ@Š\0€\0hğEÏ\0\0†\0<|@ (@\n€ƒ\0¾\0d	a`@1À5€Â\0‚\0øà \0\0p€X…®À	À\0º€2€à\0À\0ˆFø P`\0€B\0\0Äğ \r@€@€‹T\0À@\0\0  1)9®„˜ŒĞ @Z\0-0\0€	` @€k\0Rˆ;O÷å\0\0Dµ\0%`\0000\0+\0}\"5\0@ğ ?€\n¹\0ø\0(\0 ÀÀ\0F€Š\0,\0©©\0@R+¯\0Ä @‰`\0|\0L`°\r€7‚§,`7D5 4À\0\0\n\0`¸\0\0ÀÀ\n\0Ä\0ÌÄà£`@:\0(\0ÀöD(\rPi€)“~8²€\0A” P€€\r€ä\0R„ğf1mÔ\0¬¬\0<\0¸@\0@;B\0@™IÑØ	@	\0#\0s\0rGaí@€À+À\0”\0\09€€!\0\0\0¢@ `€-À€‡\0º à @.ù€\0\0 /°@ <\0(\0\0@ 8Éd@\0Q\0†¨ q`@V1İ\0œºè \0`\0A|© KcP 4@\0€ìd€p @*€b\0F\0ˆ\0º\\¢G`\0(\0ª \0h\0ñZ ÃÄ\0r\0àRi8’\nlÿ\0:/N À\r \0002\0`d	Ğ\0@€\0Ê\0°`*\0t\0y\0¢\0„ØÀ€@6€x\0\$ˆP\0V@€¨\0ÀPX \0%½´\\‚\0ˆPĞ Ù\0%8ºD¸È€ €(€*n\0Ä`€€!ˆğ>T8à\0éõ`dˆ\0<—f¼\0 À-@v€Î\0BvÜ˜\r\0 Ü€–h\0  \0ÎÛ€¾,\0001D` \rY\0\0006ñü\0£_P`[,\0L\$r4ø \0,B€Ä\0Ğ(<@â 1Äp\0F_Š&ÌşÓ8%€JÓ(?Èë%œJıFqmÉ™ÆºÑg¿qœ‹rærn¶YÊ›Æ_Êo%;­ösKÎ7<g<oaŠ\0ÕşmĞ™Ñ nŒ¡Eaƒ:•ÿ¬ëÿ¤\"`•€B†\0¦şX³¶`±’Ä\$Ì–\"¨oX¡Í€šÄÅ\0€1\0elvğ .€\"€Z\0üÁ;0\näî°N¸\0Ì+SA Ê,Éì\09'°½ÙÒLô\08€\$í\0˜‚ŠËP\nàB \0Ì‚LcC–g Î;€u8şá„\"€ÆĞ€e–¤pÃ	 €è€oÒZ±à %À5¬¢UdX	3€\$€(S8<x\0·€5€F€T<û|À\r (ÀK€|lğ“ì'°@*†€¶\0Ä¢ø0@ÀZ\0Æwp\$èfÆ¶j>Ôøh  ÂÀ0ŒDP6t0ø\n£›É@[\nálû°	Ã›€HÕ2\$[jaé…: ç;€M€;rp\0°\$Ä|´X3  .\0.8C\$Çp@H 1\08p1`\$Øàe!€fµàœ-¥²ÓPà\$”¹Œˆ¦l4 \nÒ€*J\0«ê¼AnÆ%—:m-À˜`7‹€åˆğILğ…5Jk€·àÀ`<\0c€½Ş<9Pà;ß€î¼ñ(\rğ×à9\0v!YN•¥+p\0„\"ÈH‹Ô)èœËM\0ÛI_Öµ¼p!LB¬™\n×‡Vßî©§De yRĞ€ÀGF\0ä	`5Ê…©çÚ€H\0¢ 	@8tØsÓÁÆ€gĞ¸!è(€Ì‰@j€´\n†t€	à	\0À\$¢œˆÙà @.@€k;Xh\0 @Œ¢Cy¼\0 È\rÀ+—€³\0<1œ8°¦Ñ€„Å€¦\0\\@\0ôæ@ Àa\0\rŠ¡O6¬À#€\0fœ†È,À .@=™.DÈ\0XĞ@ËÒ\0,€ÔF«o‰Œ€uŞ8\0àà€²€<9–”P0oC@j€…\0–Ğ8@`L'\0h6¨\0€ \0ÀS€P\0\n\0ì8\0 €7<Ğ8°à(@8€¦Dr\0HPPíÀM\0\0H¤h	°À@f\0›ü\0Üx	0\0€a€wQæFU\"@\0KÀu,t||„!„°\n >@Aå:BàÀP&¬€:\0\0š\0è\0x	ØËŞ\0T!œ\0Ş§¤à÷m \$À,\0¬„hCô€À\0+\0@\0IV!h€4€.\0\"d¶\0 ppÃJ+\0ÈCôé¤(	€ê®\00T\0É@Pğ@ŒG¦;s–“ì@15\0002€X\0VSUè(€@8\0;\0Ç\08±ğÀ	\0&ƒˆ÷Èø¬\0À§Â\0’\0â¹ä\0x[…Ä€\0c,X\0#kÀ0\0|\0x\$6hààÀE\0Œ&€x\nĞ\0000ˆú\0¿p\0H[dS¯§€o\0ä\0œ\0ZN¦€'€Ú\0Ò\0/P% Àw\0\0R&h8ÁÌ€\0s\0ü\0ä\0Èà´ Àt*ÊFÆ\0äê«0Àü‚Ì\0­fV‰¤X0\0€\nÑP\0¹\0(n< \0P À'\0!L\0¨³­\0\0Àv\0´\\È\0Ğ\0)Àì\0>@ À'€O6\00000€@X\0%¨dğ€>ÀC°¬c¹¡» 	&hàûÏ„\0\0 œ˜2ºÒê\0*\0~€8&,€`\0£€\$\0èpø\0à\0€\$€ÀÆÀÈ\n0ìªÀe¢}QÀmîĞĞ\0?d\0‘\0Xø°\0†²A\0†A\0¨Ğ #`§€\0˜È\rÀÚÀ€]\0)4``\0€\$\0Ø\0H¨	\0åşYk\0Ğs”th0à@C\0004%hPiğ\n\0À\nŞ (Cr†å‘ñ	®ìy¹Ğ\0°BS:Ã7IÑœ2ıq\rÈ™Å:Íg¿aœsr¦rµYÉ/àg(ÜÊ39uƒŒæ[œ3›uÆÎua#9öè– XWXß#:dƒLê³ªH>ÎµBÃtæ\0K[©0PÆ€µaú†xEJ”63¼„^Îı€¢ÄæË˜,V`4±i€âÅåŒ–2,fXĞ±¥Xf Ø€x\nĞ°	  @N€o\0èy( ÀÀ_€b\0Ú(Ğ \0003\0×\0šØ€EZ\0000\0Ş\0‚xøà€7À’\0lO`;i€ˆ¾Lbú\n¤><ÀrŠĞ3\0á0D`ÁòÃˆk \0002 \0ÚXÁÊ	0 ‚Ô\n\0ô*8ãCSÙ 9‹x ¬8Úq±%P²‚ÎÈ–M€ç\"‚lˆÀ\$À/€Ö(lh	Q‡ %À/\nÖ,àxP`€4€©\0ú@z)° \"€ÙAĞ+`©‡ĞÃéh¦6^ø°aş\0.‰ïo¨\$\n8w 1Z@€Å ,X0°€€1Ê7‡y³¤h•.\0#Ô¹Œœ¨|4¸\n @6@¾€Ú‚p`\rĞ ,CP€¶\rBì5\0 6Å¸€Øœ`[ÂÕ@!–@t\0É´0@à1\0a\0ğÀQàà2ÀxÜp¢\\p\0`;CN\0í\rL´\\¬2Óbí@t†ÁÈÀ§j˜·NŒŠÒ2.\\„è “ªD'á<o†|¥¦ãıÄ±0Ñ6€ô¸ğÀÀ<\0ìÌ´ÛÇĞ¢Å@u\0004\0¼J°\n`€/€şUZµ=˜p  €€#¯èÈ	`\0	TU€(#L@0 À+‚Â\0^|à	\0 \"À#€‘¼é„Ó{¡\$À¯\0ÀĞ0\nà@.€6'VD ¤Â\0Y@Ä €Àá£€‰j¤Ø\np†)í€A»\0j°p@\r %À\0)\0­øˆ³™ ›@K\0L\0àV,@\n€À\r\0-€	\$œ@	\0À#\0Hd˜²à€@jM\$¬HÀ!X`\"€(\0^rL\0	°(Ê†€\0\$®Xp\0Bi€N€B\0îĞè(W*ì_€<€Ù\0œ8Ğ\0)@\$\0€\0@\0ŒcUğ €;\0¦øè  :[ç€>DÓ  .À3\0—,±	à ÄÓQZŒ[ PbE@L€\\\0ÄÆ“P`€u\0†L(ØÀé¡\0l¼‰Xœ uW\0À>J\0b\0<pÄ\0\r@À(€x\0CàˆA¾À/Ñiš6ä‰4HÀ@À\"€î\0€ŒtXa 4\0F?îÈ1@@\0F\0\nNÀŒ(\np\0Ï\r€E\0:À\r€:\0r\0DØ\0¡W€€Ù!€U\0¼!”8\0ÕE!ñ»€£5¨èHÀ @f€œHÙl``€bÙ-‚Õ:º€@\0Î\0Rx\0 © @i€Ì\0Z@Ö0n1\0001*°\0\0ø Ğ\0&\0G\0’\0\0R€^€\n\r\0vÍ¼\0  À\0ôš\0¤È	 @3@!\0Öä@H\0\0\r…€­n\0\0™A` &\0i\0ªl\0¨0\rc@~€K€r˜Ñö€Q€]!ØXĞ‘’@&X\0ÈØ€€#ÙW€í\0XĞ` 	`+}­l`\0 \n`À€%\0F¸Ø \0Hñ\0\0bÌtƒ€À™€w\0P\0aä\0À0€\0·\0µ_P À8M€¦Ö×\\\0ğ@—Í€H\0Œy¼À\r ƒ‘\0€¨\0àl`À\0 ¢O?é\0H\0Ì(@ª¡À\0L\0Ò\\hÀ\0000\"*'›\0\0\r\"€e\\\0#\0>\0rŸ¨à\0ÅŠÀ€Ó(­Õ/\05öFŞ\0L\0ƒ¢Mõ ×ÔÀ\0+½Rª`év…€>àù7Šğë+Ôˆ¯~¢¿Aü‹ú, °€¢ÃB‹ëq`Ü –æëİq7;XF¿¾ƒ:²Œè”&³¤‹2İÌYØCmÓ\"Ğ¤#n™ºŠB–êp‰[ªÅ©grÀ6-kwĞŒYàÅ³X¦±UbºÅ•‹lX°!\0ò´€À„Za°€¬\0¨ˆÈàà0€\$±›„z?dÀ\r@ùİª\0Ú\0xUÚzMÔ\ri7\0o±ÑQ`“\\á©ÃQñ@g‹ùQnŞÖø}†	À°m8€>è`d±\0d8„Ğ˜£\0EL€o8Ğ¨!Ä €EK>ƒ8œ\0\n\náé¢5€ß\0ŒHÀ`k*Ê¥\0âqÄ£Ôx\rPÀ€f€˜\0àĞ@@'€=\0D?€€+ÀP€NhÀ–@ -‹õ€·t;¨àà/â\0¿o¨\0\n #À1ˆABÜøë`-ÀY9cÄ-H	ˆ\nQşÇ7–€>¾9Š‘xT‹@#À‘·Çoš\\¸…µ .^„¥\0 \0œ-ĞgjÀa\0n”³¦Òa•Àh€Ì @ğÀ·ê]€T†\\ğÔ¡0âß€]\0Ş¶è\r \r/©\0Ó Ê42€N›@vYRØµ)K˜âœ„@n©y\"…dºp× <@rp¼B(§‘OD¯a¶N¿ÅS9òòò»Cy\0÷SH\0ĞÁPL€	€\0N¸èÀ~\0À\0l‹¼PCR \0@\0y†â\0ÜhPÀ@}\0È\0œ™¬¸À@?Bú> H€\\° <€M€¢2\0\0¨@.\0I€'K&|4PĞ`:\0N¦Ô)®§  @À\rÀ;€ò\0ô*›T\\€@@€¨\0U¤\0h07@f)ù‡æ\"8(0JÖÏ\0”t§”@\r¿ @tÁ\0\0hP€3BÃN”Ïpà€(À`\0\0P\0q¦ÆÒ @û€Ò\0Ä3P(gP`0@D€{\08Ü\0ØÖP`3€\"€ÓxäsÏ` 	€€C‚lÀp@7€\0b¨\0004¨à\09ÀK\0±<˜\0Ğ @€J¶Pk0\rà#À;B\0°8À\rà¥9\0 \0ªä0ğJ@.£u\0èjnà±ö @\rµ€GË,H0	C‹Ó‰HDlhÙp€‹\0¢\0¸\0 öQı :À\$‹º\0Ğ à ãÖq€-²8ğ9€½ ‹‹¨\0vtÀNĞ CÌ‰64\0´œ^`RÀD€'UŞ\0Põx\nÇn@UŠ€ˆÄ&@ 2ãÀ+€Î\0¨a*€&\0D\0²{HY?¨ĞÀ	€;\0ÃØßh(ğ &€x€µ,¬pH! 0F•€f^à\\Èp\0Ù€v\0„°øÖüĞ4ò\0M®\0 \0qpÀ@e€Ø4jDÈÌÔ5ğ\0\n€\0Ï\0fLÀ‚ˆKLÀ9\0°£Ì¸	ÙG`•u¢\0˜\0èáp`€[ˆàsÄÔp‘ık¶Ó\0.v`˜@À<\0t€@¦)_Éä°\r@%\0O\0Ø\0\0xqª.€	`€'y?\\è.j'Ùq‚Z@\0¡\0¨¡ø(\0P«ª\0€¼& ËJ NGNç\0ã\\tÉë¸ l¡\0Qé	0‹ô¨ÉĞn‡Ûğ€Ã\0R\$‘h¸rD¬@Q\0¨\0€¤ 0`Ğß¼Ùtğ`	 \0@v\0ŞÌ\0(‰@\0`€\0\0Â_`i€\r\0Q®\0(`\0“j€1Àa\0@Ò\0¼8ªÀ€\0ÉU˜ãpàAÑPÈ°ßpGD\rp€™ª€Ó.X0h €;Š€	fĞ8 \raHGÀy€:@`€\$ÀE`X)¤‚K @\0Ê>Ö°¨\0ğ>|@iÌ;\0`\0È\0…ô€^Ÿ×Wú\0\0Ì×X‹ó`ú·_¢ë)¹ıWYÍÉ×ìºÒnV¿uÖ³rõü.¶–Ânj°u×s•„#»@\0006\0hÆx¸Ğ@€7½\\\0ş8ûW@ '€*€Dr0ÈP	\0001@(\0Kœ(h\rP‘)@€QQPHbë´±UrSGÆ\0h`\$ÀfÁ`gÂ@‹ü>Za@\náu\0ã\0²M ªrX€4˜8’àP¸Tˆ¨àfÀ>…ºŠ˜!ÆĞîÇ@E»ìˆsø·ìÅ\0t€\0Æzh8ƒ’@!Ê¬k.xi› 9Ñİ&x   !€F\0Ó8h  1@F€”â`X	€\0&Ú2\0H¸\"¢R€8€²LàPà €»\0RxDà 	³B@%\0»hş@Ü ¨Ê\0.IØFN9Ş?ŒH@Â\0Nƒœìnì	ğ„ˆ<’7­Š>¬æğ\nu\"Ğ®Îo>ÃDÙ#8`°\0”\0P‘•…P‰8 &@”\0˜H¼‘‚:Eàrru>(ßP¸ &€%€¼—|ü9À€@b#Ùâ’Tºt0 \0f€Ç\0”øğ*O€e€‹ìAÁÀ	`.JR@i‹>†Úìğ\r0Âİ@g\0×›\0P9®@9A¨•y(¦€°êY@Z€ğ‘6Yx\0 .dQ\0¼aà‹ğH§ <~\0ğüáD‡ iĞ€7Ào†¶Ä¤5T~B\rÅ)ÊX8X1¶ámÂØ¹°Âç€s¼VÎá\nèkğØ 9C`€ä:m¸lDò`<@q”îşd(„hb®@z8\\›Š…HlĞa³I‘€öà/`„JW„¯Bö¥\r†ÚJû(}¦¸nyD,G¦îìb>Ä˜2Ñ¿åe‚l{šBÈ\0\r÷–@SéF½ÆÔ À6\0€Œ\0@\0¼\0µ @W€s\0BL°@À1z„÷\0ª(\0ãÔ° 2À\0½\$¤Ø—%`.@0(ËtšT0\r€	À'¿\0\0XXÎ\0€@B\0&ä\0˜À€š[Ÿy€º\0@0@\0J€J\0\0”°  !\0J€iåğ¸&°­ôa‹\0l34,À¹`@H\0·\0ÆlØq@.@/\0`3„\0@3„€1Á\0Zœ…Ğ€/@U\0H²\0p\0( ãê€H¸\0À'\$\0004€Ş\0¸\0h\nğ@:@=\0µ\0Ê\0H˜ß@c\0003˜tq¦À	@â\0lŞ—\0¨\0pÎ%@\0v@8Ğ	`@)€‰ŒØaÒçp\0\0€ \0vÈpà`	\0\0Æ\\*‰À	P%ˆÀ\0AQü\0\0Hp €1€;\0Ø9@€\0€×\0®\0D à`Àd\0‡\0±‰ˆÓpÁèÑİ\0001¶šÜ`\0 €\n\0K\0<È\0I€`À€ó6h`\0	€å‹\0\nlX¤’SÊ€\0&\0\0@ª#ˆ À<\0‹²p¢Ùgÿ @\0R#¡;ô\0è\r… „3„¸|NQå Ò\0/\0’\0h\0Hşx\0!ÀF€•UB\0Xp‰T\0&…ÚƒDàPè¡ÀãÒ€¸B\0Y”P\0Àà€>\rÔ\0­ª¨À=@,¶NPešp‰à@\0\0Z\0TB[¦Øô€0\00019\0ø\0`P\0p€_W\0È)å€%\0a\0Jn¨Bê@\0 Hr\087€àà€1È£&@\$Gp\nğ\0]\0‚pÈKÚ€ @:€\0K6Ü\08Sç€}·€`\0rF¬\0`áÀÀ@Z7\0\\˜\0‹cğ`\"j€I\0RRd@\0\0001\0®´\0Rk1¦í‘\0Pœ°	P	 \nÀ;€\0äŠd \nÀ@@0€ù ø:Iá*iÇ•r\0K\0Vª˜à å‘Àe¯L¬Ü@	À€+ª\0‘ˆŒS€(‰(€…Cô4(d@‘u€k\0ÄÀ*,àRK#\n¤\0n5µp° ,\0v&!0`\00£ñÀi\0€ä`p	G>€	±\0°,à\$„n£°ÄSdP€¸@i\0Ã\0Ê648Àâ@\0€™ôH¨F° @€¦\08mÌà@² 1B©f•\0 æ‚\0Y¢Ê5ü\0\n4È\0ğ Í¯\0á\0D8ç°@€\n€)·i1eÖy‹mÇWçÁ÷nB¿MÖcrUúî´”¯Ûu¨Üµ~û­†æ+øâÄºİ7”\0ÔHÀ \n‰í€,\0j¤h  €\0‡†‘”0\nç¯à/Üå—3\0¢—l˜\rb|ş@*€İ/ôx¿ÓAó\0à'ËAøŸBNgH\0#\$À\0–yƒS27¼\08À,ö\0¸€ ò~Ç*ö\0ĞOØU	½Â•À/6\0¶h÷@h‚¥À)Š—Œ%¸–à´¦€;(è\0öa	Ç ¨-&a»%An)¸¸Ã@#(]Æh-±Agc \$‘B„ø²H¸ &@.€é6=lğ€ 'À6€é‡Ø §À\"@P\0zÎxèPà \0q»r¿¼ğ\0³ €g\0—\0ö¨@\$‰x€\0ğ¬`u´ÀÀL€††˜À\"; h‚\0~¢şˆ@\n!@ h†I˜ÆJ\"@!hŠ\0†¢-;88Š#@\"hX.‰\0Ê\$@#h’\0¢L>‰@	\n%@\$h–\0’¢\\N‰€	J&@%hš\0–¢l^‰À	 &ÀF€œ@ ø\n@ )@U€cTÄë`P	À,@\\\0Gfê#¿`²k’=´nŒDÄXa à0Ç÷9šD?È	²‘ÀL@RFCD}H c›§0oBÈAÖÂŞ,, ' &\$èŠÎ“¤\0èÌ,Ù:‚½\nî€ºd‚íò\0à(2@\\Âİ|C}z€pêMÃ…”w“ÂQôh\r\0à3€h­0¤9 ¢ª`¨àZÌ®'ÌhX\rÀ`,Ào€İs)@Ø /¨Õ\0Â£TP@7@h\0ß:dRô¤à\r`/ÇÃKà1ø°È\r8d€5@x?â-øŒ¥	·€8\0w†¬n¥Ã;Åe\0FDCb­d)æB]¦Œ3¹DZã[‚'\rå7‰Ä`9¸Ò§ìÃ}®Øğˆ°MK/(\0àJ–!Æ°yÀHã@u©\0ÂÜ\0ø€@!\0\r€Yd–|*Åğ\0®€f€–*%h¨àOV\0\\\0\$\0dÄ \0ä€g\0p1Ú\0 H#Õ\0\0\0:ˆ\00À@(€ÜPÈ0`€\0ƒpäjü·ÀÀ0œ¦\0Ô˜`E|@0€Á\0ŒTĞ@€@<!?\0º„	r¬CLÆ]Ä\0ê¸Gp^`À8Às\0¦Ş\0®H\0	`.€nbÔ ° +ç—M(Z\0LÈğ¤3\0@¼\$ÚD i¨èîÀv€{LéÛn  À€¨°‘\0000ÀbÈ)Ã4 \0pà1À+€!Z\"\nV8\0`€€\0p¼\0ìAË0à@8\0Æüô°Î6çÇ€”\0>\0P%¸€ 5À`€]\0ô¸\0p\n``\0A\0İ(\0\0ˆpÀÆ\0÷\0:xè@\0-Ó™\0İuI9Ä8\0c‡@\0€È\0004\0Ø@  Ht—R\\\0Ô8‰€‡ò€”®\0`ªS @v€X\0†(Ë	ÆÄ`@j€•b:x \nà\0ÇO{hUÜ\0\r€·@·\0(¤@—Xğ\0>\$ ¤BXÊĞ]#Ë€\"@0\0 K(\0Ñ‡ »à<\0F\0ê°@0€\rIP€2š\0E^pPÁâk\0K£êŒ  \r¥¾€7\0Ì0ˆa#¨´+†i=g°\r	 \0€\0h?”0ù¨¬\0R‚\0hõ4ˆõk`&SÏ'›\0 (¢  À-\0ù\0°\0L`ğ`\r\0q€é\0‚lqà \n ?@v€œ \0¼z÷aÆÀ€D\0hp„ À€]+\0`õÔx°`/ÃÊšWŸ!24 €¦ü€²Ed€`3\0Kj\0F\0¬€\nP„D\0\0æH¥4 \rXóÀ\"åì°÷\0\0 P€H9@g]4>Pk`\0\n	İM9\0V%Ä[^I!ô@€L\0P’l@+Š@•\0\0¾\0ì´ïDq€\r\0§ÄÀCÀ\n`@\$€ÓøH‹€²òßà\n€\0§\0#\rı°€\0P;“d;€X#FÀ3ÀN\0#Út!øÃi`0@^\0l\$È\r\0…À\0f€ºfG¤Äx@(ÀG\0iòbNØ€€Q´X\0{,ˆáO \\\0¼\0Ì\r (b\rÈdôú\r@\0003@h€¥\0À\0ÕüW&ã1]h!EwuA`2?üë‘úŸëXäÿzÁ\$§üÖ\$?î ğ'¸AÜÀ‚Ay\0˜Ğœy§\0`\$ÔA\0 XÒ¬ëÀnßÀD€‡Œ8/p	Gá@&	U€œ.î`Ğ@`'@s€¦@¥–Ø²@;\0V€¥¨ÈŒdğ`*€M€ëR\\P\np`:€S\0Ô”:‘j  '\0?€é<=œü'`Ó4HµPÎ\0¹C, Ğ	s %‘\0”Œ4MÙ¬gcÄ €H’‚äPX”âP@#IA\0Œ\0Šõ[Ák¥{\0~-Zà-8@6NSÀÙ´+0o%¤âT @*… öp{ğdçÀèC\$À\0ŞH²uË3Iì k8h`,ÀI=Â{\0B¸ÔÌ¾\0ÄP \0,€İ'|lÈÀ\0\0/<\n\0Â+à P	 €_€LjŒ<P0\n€¯\0n¨<Å(ü¬À%ÛPö\0ü(ø*\"ĞK§gQDpÕ@ÅŠ\$\0\"è‘È¢KD‰Mº%´Kè˜Ñ3¢kDŞ‰À	Š¢A|ÀL€šª,0lØ	Ê£@'j\0ª<ƒ<ø	@n‡\0S\0¤\0¨0€€*\0004€°¥eGüxB2à-F«\0¶c£pb™á…€K6Œ‚ß0VÛ>³™4* #9ªÂ•õü‡y8‰y\0r€	ğ\n0( )À €«9İMÄÃ@\0/Gñ”WpÜÈpÆ€1Å}ÒŒ„iÀè¶\0Ó1`´¨x\rP€4€m\0Œ¨p‹Êp 6@n€Ÿ¶€!\0à7€p€«¾ˆqÑğF3‘a\0ä)Zñè‘£ŞÀÕ¡ªŒt#Ÿ\r@¾Oµp@<äü¥a¸døS`=H:€ö1ªá|„(ºpÙgRÖ²%y\r SÔ^àma|\nfâ‚i¯Gr5¼¢GQ®ƒ}ƒÆZqdğSĞ€\n€5\0<ğ4@\0ààÀ!€Ò\0ÖÜıkÖP@@c\0ƒn¸àPI\0c\0ğ´0À`8\0)û\0¸ÛŠ@2'\0r\0 ¨°P@1˜\0¦éMCa)Wg\0/•Æ ¨0À\n\0M\0¥\0ê ¡2P\rÀ1ÀH€.BHˆ À3Eh€KÎP“¸  -À\0[p’}X{`K˜€p5o˜\0Ø°¢×@M\0‹&\$h0Î`€v\0—\0š0ˆd€‡rçJ€,Ìà\r€1:À]\0ÃÚèp–-`3‡È€ßÌTäzĞJ‚\n´€<\0j¡0\rh¤ 8À>3E´#±—À†€3~†\rØÍ@€m€O(`€À2&Î\0o ø` <ÓMÿ\0Ø\0ÌÈds@-À€[“LÀˆÕ·à=€€Ù\0úÌP@\$À€Z,\0ÊÎtÒ \0s\0°.`À/”À)€ b\0mVÁ¼`\r×¢\0ƒœà\0 €C€)\0è|šCq·¢¾Îv@b\0\r\\‹3`Í\0%1¼#ğ§PnÀ8š€\0Í\0B”ı^À`dE€0p\0H0ÇÊ \0003\"°` @‹ 9@\0	K¶@	€%\\ç:£ø7¼h	phœ\0G\0‹\0 P@6X[\01HP`;ÀD\0004Î\0ÎAïàÒ\0\$ÇÍ\0òX°€	\0¥ËÕ\0¾\0À»Â_8Šà €:\0:\0è¡tÀ\0`\"¥Ê\"ÃDú­ì(ÄP\rç¨\0&\0¡\0”°ÙñĞ€€€-èÚK®€@1M\0002J˜Ø¸0\0%'½€¯Zz‹Pwp\"Ğ€À7ºš\0š \0x\n`3\0W€­\0lXGD\$ç€\0|\$\0cd€ \0ÀV€«\"â6H°\nÀ\n@rLE\0 LQtP€>€(N­\0øH8s³ó]\0y\0/ğiPÊ&@.€}¦„– ¸µ¤_ˆÇ€^€\0æ\0,€\0`\nä8€×\0xh±8\ràÅÁDÉ\0ÄTL(\0P´ªÀ0À`\0]Pm¼0@ \nB-€bâ,ğ\"ç§a€.KLõ `-\0E:èch:X˜ĞÇÑG/ƒ½h8P: )\0P\0ŠFÈçÏ  c\n–À€®\0ÀÖäØ\0šæ\0002“Z€ƒn\$\0è¸Ç&€\0¡8?¨'‚GSqssõ	åĞC‘ßP¡¹B§YuëT.nQPÁÖC%ûÕ\r˜T6u·PâB@uú’…\0x;¸ˆ0\0\0µ\0j\0ü `@\$@Uøn ¸€“ş\0L˜\0Üp\r0\0Àl\08\0 nÁ2\n 7À×Œ\0£`¹E…‚ğ±vØ-»H0\"³É>Š˜ÇæOOt|¸ù®¡XB»u\0XÎÂ@W+	A[l#ØE°œŞÂ Uà¯aYGÏšº>iÑ€«3…€6Iî€×ÚLá8ôEü@,[\0´İF(\n 	 &B\0c^œ(P\r`À4€n\0òFå;˜¸1pââ€y°üêÄ\0»@,BQ\0ûDõ€ôB€@QD:ˆ…\"tEh‹Ôı¢7Dv¨\$@&\0HLö*Hp	€¡‹@K€¿>d(\n né&€§R’¤ø\r†´-…€Q\0ŞSà€x\n0@,[=ÀÌÔ:¢ *Š€u\0´N|æ£5‹“o 'Àv\0¤8¬ø	p`'\0J˜Q&®AË :ÀI€ˆ¾{³ÓI‚\n µ\0Êì•e0€¹#ÓBâqÈLÀsŒ<¥{\0‰Ö H\rºF¢\0'Œ®\0¦ß8Ë,Ğ`ÀP€p\0Èœàà€±@W\0ˆ\0|‘<¢©dŠâµL=\0µgĞÏš©°+ -ÀRÉ¾|Ü\n	jx’mõ\0a†@<)@¥PŠ€Ä1DD£°\rbÀ6fr€Ú&XØ	¦@'_ÙÆjºüPôgà,@o€átt|)Ò`Aªö\0å»VP\\`k <@qU¡1Ğ.üb–®ÀuŠŞ8§˜m7÷Êf*Û´Ø†z¦~QP@JgVa¡¡svux,qf0rçÀx\06Æ´áúöeQÀ(\0é§E\r,p\r3aBÀF€ê¤å3 @€€9\0îÄèÉ\0Š™€ºH PdÀ>@)€T È  4@>€’\0j¶Xp\n`!@4\0d¨ÀÛHÁE\0ê:9HÀ¨6Àß\0’b€\\À \n@1i€QÜ\0‘;¸p@+@€«pxh\$<àÀ\0V`xPXiàJÃ\0§(ÿÉ÷À	@à/ÀS\0>ú\0 {Š à\0\n€7ˆ(@@/\0€)NèeÃÃà@€²cøÈ\r à5€€¬xúP)‚Ğğì\0z€¶¨\0¬x&gàÀU\0–¾Â\0Ğ€+\0\\\0ñ®‰7èxÀà2€W¶X\034ğ\r 	@)\0D×D“®\0Ìbæ` @,¦\0V§Sè°@ÀR\0@²¼õ”`À@Z€‚UŠ¬lˆ@\n *ÀX3û^ğÕì\0Jİ0 8ÀE\0{}‚O°DxÄ‘Ç`€€ØL‘Šm /øÏš\0Œ\rÌ0P€\0\0ˆH ÊÙÖz§qOÀ PÀ\"şf<¼b¶˜°²)ÀP€Œ\0ô˜À`4I\0™\0¸ 28@8Â\0005KœPpP	à„“€—\$3òEHÂ7€\rV#1±Vğ˜q”óÏGÊ›€è1ÚS\$cèuñ\0d€(WÔexm3òE‘ù\0î\0È.ìøGÕ¦€<Tš	æ\0À\r@]®µ WIø€ ‘|€;¬ÉÏ ¡øe˜Sr\0˜\0*{™G€`*À\$\0¬\0ä–8ñv /¨û\0d„ \0\0C\0icR\0<-5€  €/\0Š¦v\0®¥5n@à+\0€æH–\0Élğ€XÀ›L\0XŒòè\rà‡—\0€×<® ~¨ èÆ€\0)>Tœ\0°	 À\rO\0Ó<Ä8\0 \0Ä  Ó\0Í>kÔ©gÚÀ7\0º.\"\rd=øx \r€€V	l¸Ø\0`€€ÖK¤\0000à``?\0F[\$%l´p	°ÅˆÀL\0\0²\0e(\rP\n\0(À€5¬ \0òcİÆÛN€–aO\0`\0à\r@B\0ã\0pó\\9 =@j€R¨@úªGeıµ	‘öŸã¨R¿J¡[rJ…®³ê¯Ú¨bÜ²¡›­z†«øª¬_Ìî‚ÀÀÜm\0V\0Ş™”`Š²À5[\0ZëÔk¯p£=€€Kf\0´WğĞ\n\0001À€QLÀ\n€5“€Ù\0V*Ì+£’À¬Vu@o“·z¨˜°®6ğmâs°ŒÄq=ª™dö\0Eğ'°\0Ñ¼|ÊÁlï¤Ï\0r¢äØH° Rh[Ü\0~“/;\$6ğ \0/€â\0‹o”+xÍßÀ#Ûì·Üˆ0ø¿Àå€^Áo¤ı¾w1€Àn[ç²N@ 3À#	\0§+È@	à@W\0S\0ÎˆÈ@@5€‹­f°xğ\r€@=\0p\0åaæ.Æ®Ù€>\0k(	@À€N\0Š\0°ˆ`FzÀ\0T€\0–ÍÔ \0 €Y\0‘\0t#`»\n2Q¨Nj\nÕ*Ò©ë\n &À'À› õRX8à O€UAZDxF@.õ\0¸~?2X`(“FC67Ôxä‚’° ^´‘Àü™FTéÉ·Ò‹¼®At°@4ˆ·g˜D£ğ\r'€3À?€ÌEèİÜ5.m©´È€¿¸„ô  \0+€T•<üZÇê\\ @n(ãÀ,¸b„b¤TØò8Ğ0ãAóÛQ…ĞZ\0‰\0¢ì” Ú8 \"€0LÍ\0Ô²`Ë¸	pà:İÈ\0ìä¨ZjD 9Õ\0ã„üJàÆ.\0o¸2¾V«º•%¨%9\0m€Û¥È˜iğF3€j\0äÈTÆ¨\rg’ 6C“€ÜpÀ}ˆPkxÓÂé\0t\0ì\rv.¨€la°CdÄbB4„hD,´\\<”ô!0BhÈÉ	’‚÷ßàÍØ›²¦yˆÙ\n—ú@‡eÕ¼xî–,kâ×@ïV\0\0vØˆˆ€À\0€e\\\0eÔØ`\0p\0Ú\0BQ8æ¡KW€ØXx	ñàıÀ;0¤àĞ`w€1„vÈè	ÀT²à\0ÊÄ”îèà\0€8\0z&—„\0c‘Ğ@\0N€¨Û:Àì“XP@@>€8\\fU‡D ›€-cÚe\n°@ “#€„\000Š\0x€şôh €	‚ÃÀ@\0m\0Bğ(°àÀ!\0šWzœø €J\0=Ğ\0¸ñW €5@\0\0D\0Tˆp±€€†\0®kôÃØ„ûÀ5€\"ÇUh½rhğ 7€1	\0V‰\\p\r€•\0C€\$–\0004p\nâ´R€\0\0.\0Ş` \0?\0003Tºp`hä €‚±v \0ÈÈ\0¸Î@?@\"€l3˜VŠvh\0@\r )®*Å\0p4\0üĞ€	\0›\0r\0D,áP“7À\0’\0à ˆg\\À\0ÇNC”é³ÕÍÀ@|\0007\0\${ä 6`À;@T#c\0˜\0Á‘ğ@@\$…è|v™­P‰¨À€AFê8x\0D Äq€QÑe#Œ!eëª_àÀ“i\\E<Ğ\n°\08€€!{NJA`5z`\n˜€¤lXğ0j/€?@@\0Br÷° \rÀV \0P%`xP\\à€^œ\$ix\n¢>à9@ €å JZ@L‚\0w\nãp¥ÀVF ä±\0'H¤Âh0 )\0d\0NÈ˜BãĞ£IÀEK-äda±€\0+\0>	Tº«.€h\ni:@@UJCR´ @\0Ttùà0Àm§ê*S†\0‡>ñ` `	(f(h(@Ïçœ2ş\0r¦«R4æ\0€Ÿ\0 \0ˆ@{v£€L£…¢\0¨©`Iá™j€-\0L€ó]°`+¡Î€³œÊÈx‚€\nôÉ\0L„8Ä±¡  -,Ë€3·BPàø ;€e\r+\0RI³\0€À\0)€Râ\0lëÊ}° \0}\"¸¤€€ÊcÖc6î &Ï`7bW+‰Uj¸P@'¶\0'>Ğ\0+`)\0I€gä\$Kğrè[€é\0h˜cu#¦GM€íl¯ìM”W%ùûÁºÅ¶ûÚÛüÆ¯´«ûLeƒÿA>ÜLÆ¨°ô\$€[nm`\r×\r€U„Kû’¨H_äMaRÿd‚ë…€P\0”Óê@€€#€nğø€´óH€I¢…\$È5)€a€‚\0(âÜÎ`	\0006€E€H¾4°ñRh1@s ¬‚ÑGˆõ—dÇ@H ¹q˜5Jï \"jQƒ»QÀ Tˆ©`µ 9*U¥,½újC``BÏ€^4\\,¹Ãğ	†Ì\$ÍY–¡¼šÍ`1æÈ%~|áqÂâ{DôÉæÚ4\nñ~”hÇå`dB»_©¿\\V9 €\0¨€i:‚m&  À?\0a( yË ·%@D\0nÏx£\n3êC~#9¶+d		·Ø\nE@[IÂl°	²Ğ±f…u\0·š<+¼0z’px€2š=\0É\0„è=ÆÀÁ€b\0Qia”êô‚Ã\0`\0eÔ>´Î ƒ:€m|0  2€b–%Ø×«Í™nŠÊ(È€‚Ò9 ªÓXêêS@N\0×¶ŒÈ\ràU_Ê¼\0áfAÁøØLà7@aÆk˜Y¤Ø\r1Ëd@p\0Ïz˜`ik ,§œ\0°RÄº9I¸qpÇ\0XFhÃÉü8\r4_`6s€İ¬¾³…0Í/ƒTÀy\"cTQµ«ÌB€ò³†f¹+XğÂôƒgM¹ôu86’W€Ú…=Ş¼m«oVä7bšD`‰XÙmqt=şø\0PÀÀ;™•\0tP,\nĞ`.\0X1\0Ğ0\n ô@@‘<¨Ö	hÊŠé@2€ÈR(˜…|Ø €I(£\0”hğ×&So\0”z\0ÆŠ0	P`˜õ\0005%œË®lX@ @\0o\n®ä`—é\0ÀO\0£e®¬\0¸à)ıÀIº\0f1€ \0@I€å\0¶æ‡:äq“´±€M\0AWÈ+‹Æ@D€û\0ŒæÌÈp\08’ÉÛ86ø€\r \0,@8µ5Yš¤@\r°À@€ò@ÈôÈPà@=b\0>åÜ #È`!Læ\0r pBÓPÀ#SÙ€‚´Á–ãÏà\rÄõ\0S\0\0çKè k1À?\0\0W,6†\\©ŞpÀÀ¦Ê4\0d¨°€€O€/†qúp\0° \0.€õ\0lÄğà3ƒï€B\0ö¬˜ŠÒ\0À+@€ÍkÈ%7ù°ï<À%Ô´7\0hÀÀ1À=+	­æ\$„G¢¯Æ5\0K€&Ø à\0 €y€L\0”¤DĞ„+@‹\0Nm¸•ø\0—€€€c\0 \0a\"P\r5`\$¢¬šE\"\0z\0Usà	 —À	»6R\0LX¢“À\0€“€ÚJ„F(şÀÌ¨\0\"»é{Ø @>.\09\0j\0*vH\0½ @s\0\$*aTÀ`à+\09\0Ëó\\shğ'Ş\0^\0”¬äˆ äÀ€(\0Jqt¤íáş\0+7ŒP\n  €a\0	\0\\\n]ø“â¦@€›\0„|\0€õ\0:üğÉèäa²ÎD8€ş\0BªÌ€	\0\0\"€d€-²ÎXĞS·@%\0000D7”X\nöøq^sÁ,\0Ä`8ÀVq(€/r\0‰±øà`‘j\0/ 4Hà 	\0-”B,¨? i¹ƒ`(@\0m†( \nğ„p@5EÄtØ\n@@ÀHÚz9\"¤îÊPÀ\0m€Ğ\0ad•ğÀ#4‰F\0Ğ%5RX—f\0	€L\0^ 	 \r ,NO³-Ì´ğø fP @€ªc†\$°àÛÏ`)îS3@ˆ[O‹EdkÜÛ²N\0tü\09ğ \0\0&€\n\0_|\0åÃà >’'ØĞğbx\r B)\n÷€FKDèêk	ßXQiB85U€ñY`QBŠÔ\0œ\0d°\r°€&D[±´\0†4„\0 ]*À€®ğˆ`êı\$Ê€\0\0ü˜µmñ~u·êş6à+ùÛ‚¯ën¿½¸jÿ6â+ıÛŠ¯ûn2¿ı¸ë\0007ÍÖXnt¡ùãsëçĞ„¯ E–\nN\$ó³°Ãt€€F\0ì\0ìHPX‰-t\0\0n\rP\nğ©\"º@)€Â\n *ñò€4Â¬\n¸làù Ãç¬\"bå7‹œ|èù€«`dÖl‘‹¼ÀXXP°a€Ò…‰~‚ÁqfšÌÖòÀiL\raˆ0F(¡ƒTË\01ƒ(\0ä^À’ ’SÆìË	˜0i0 B€yAVì.0ğ¸piì»¯z#X9Aº’Àc\$%Œ›¢ÖÇ£–“0ŠiÙX	 ¹ &À.€æ6£TĞ÷°)3Öë\0Ü+nÌ P %\0-\0Ëœ˜0rbÀX\0™\0ô”àP@'€G€…28\nPm²\09€µn Ô€ŠLÀ^€Æš´`¬‹¯´!€.€e0Â”ßQI1Îö\0P@cTrì	°®pÓI[x 9½4ÆiÅı*-@Hà%ÙH¹=TcšI@‘€9 s0	².ã˜¡‹\0K‚ààÁ–@\nÀ/\0T€g~ºt(à¢ÅÀc\0ÉZ€æ@ €9@_€¹ºpàX°Ò’¦Á€¹lé®l`h8bïã\0b\0¾‘DyÉÀĞ 3@v\0Û¨è¹Ğ9@p€Ù\rRuKídà€7\\-€Û<…7+†pÁ°@oHÁ6éÃ‡ğ`9²\0è›”dY~ÜàÚd%’½\0ø\r¦è6›–52Áµ\0~ÖS.¦mãpn6#@‡ƒy\0ùº…Öè€\0pÌ€g€\0v¨#à€À€šf”:›°`€v€\\ø•^\0wHï\0‡@­½ğ *Xø€¨È7UH]ÈÀ@!€g\0²\0Ò\0p±§@4\0ˆZêÓàP@ 1I`\0ª\0¼\0ô‰€\0…Ài\0¾\0Âè0Ğ *\0	\0å\0Š#ÖÖÀ Ãõ€c\0N<è\rğ@À#|\0g€À°\0 :Àd€“nÀ\0ğ\rYPã]€5€§\0000šÿÊläD@ÊÜ/–T˜p ²I[\0IÈ\0\0h@Fà€l\0p\0<N\"hğÀ4ÀOFXw¶…æ\0\0)@?\0û²êÌ˜;âÀï¼´_\0ÊWŒ0\n°À@L€4\0ğ\0@ˆ\r	 0À|‘ x˜ğ\n€ =£¦‘Í»6¢ã# \0Â[À8@S€Ä\0mÈ°\r€X\0F\0	f\0àÎØw\0À/€4òÄ0\0a 1\0SÅ]ê3°\0ØĞ\r@\0k\0\0.`\0ÀÀ€HÛ1\0ˆÖ´õh @3@IüÄ\0ˆ\nØUà`&'\0èpIÀ4\0 5 \0Ğˆ‡² À[€ôÃJ(à	\0\0eÇ-‹VóÔ ,8°\0à0KÛª\0„&_X	¬CmpM\0T\0\0ì”»\0à`\0“>h˜èW/@4€+Vñµxe€	\0¾y<¯ ‘ÈúC`€+€X\0©NZøHFƒ\0€ŒD°\0)÷À*À\0\0#‰æ ¥‚`h\0Ï¾RPÍ PVRÀF€.,È/@T€5\0B†à\0\nPÍ)\0+ô\"Ê*kR„£ö\0˜\0òŒyd1úğNˆ\0Iº’é„ s\$8š‘¸¡…Pfx\0PğÀ/€z\0¹,BmĞXf(­º®ê\n»xàÓª@q\0§G \0ôÛÀ\0y\0­<\0\"ÛwÀ€Äw\0ÂPƒlkP@@ğÎ\0ˆ\0äÏ0%³@A7\0#“•\0\0å–€ã¦’4@ğ 0Á›˜ŸWÒa[°\0…®ìû\0˜4<\0[’ºÎ€€	\0h=¸?˜àcG2€€ø%	85•Éü0[P+ğ}@``0éƒ€ôˆÊN`bõBP¤\0 ,R\npTÙ€&€Î¸á5 \0€m\0˜?n°¸\rÀ†_\0(Ğ>’Ïû8Käó¢½ĞH¯é1íÛpó\"ÂĞR¯ı1¶,T+ç¡É¾z°šùú„»è-Ñ/¡¯ùgN¡M òÿä\0\0(\0N\\ <–Qéì—c\0*÷ËX—-amêuf	À_€N·V,…À@\n\0005Olaç°N\0øÍhÀ\n\0007€€P¾\0êÁ{ô[lØ2°^¨,Ff{ø¼Ø,E÷`«‹¯XùÁó„öÇÇ“ØkoTŸI>Ğ	CÔ\0(~	q{òrÊŒÀ3Æ\0Ò‚<\\`À\rÆ\08I\0– SDÃ¸â¤À@…­rœ-„SáµNÉ…;„ÜF	 F@<€ê*øÂ` @&\0C˜QcLuËÀ@;\0O€•¹|a\\ø	° *@Of»>´P\n\0À*ÀP€îŸd¸€\n0é\"‚KÎûJ“jlD1™±`<	8IwôhéÏy¶€o\0ÎrTh–~\0f\0º.(¸@„Àc\0³\0Ølk \nĞ\nà0\0T´5½'P1`­@ÅPh¶>¾ª‘%\"ıüà\$sO‰#šm\nôæğ®5±@LSe‹0lğ	È\0'À0ág\0Å Hà‡À2Ç¢²Y‚E²p\r\$#€i­+.H 	U¨ (.\0ª\nÄƒ‘Ò€1\0r³\\Î¬qÃ2Å0ÂìÔÂ§\nš/<hp< z\0x\0ø\r–àêa	PÙêdÒ¼N«]V»	› I_&ë-hK 7Po øÿßïl\0L8à\0€ßì2¡€\0æFA\0\n¸¸7LÇ^À\\®Ä[”\0H¥’@4€[€wf(Øa( 3‰(\0\0h\0Ğ€3/€f\0If|0 €!À\0001*¨ªQÈpªâÀL€È\0Ôf7˜\0\n €B\0h\0 \\¨Ğ\0\0€-©A‡^”;È €u%\0ã®¾è\n°ÃÀq\0Qù” e¸j@\0@€\0T\0Lä5ĞÉ<€\r‘G+0T;İĞ\0@	\0˜ˆ¢qÀà7†0\0ò€T\0Ñõ /\08€ 2\0Dd`\rà @Y\0½¡²âMà\n` €x\0x\0004\0äU(	à\n@+\0/€øùQà»\0000\0dÑFlÒ‘‚rà @9€F\0lÀ€ø5@o\0ú¶ ğ±j¥€ À\0\0=™l\09F€Ê¸¬	€¥\0€ˆÙÚ €@œ\0x	€\rÀ€Ä\0ö\0&u’¢ØàÀB\08\0h`\r@\0àÀŒ6\0¸%}Áà;@\06»È\0pÀ\0€Â5é[”H7@R*DN€4&Œ°Å¨\0\r\0k¨œ8pà	\0R\0Í]VˆÄ¾¸ƒ€€ş\0X@À\rà±€FCg\0Hô  Uä*Ï¦\0á–ğà9p:M‘]Ô \08ÑƒE\0h<\0€	`'Ÿí€µ´¨të—à	€\rä\0“@êÆ§0\rQ`{€6øºP!à\0e€2\0Il„h\nS0M·€\0¸ˆ88¢@@@)€\0|d\0œH¨\rêm&®Àn€ÀÅ/®½(5O€1€Ÿ\0†ÛÜqĞ\0@m\0™BZ7(€\0Àµ×{^\$c¢Ÿ°ø¤ë±\r \r@m€,0m1\$‡ğ€Ó°\0‘Œ\0Ì` •ÀF˜š4Ø°\r¬\0€â\0LŒÄi0\$Ë²@4\0[.ˆœp27 6€P;¢\0jLà	0æ\$€zg­\0l\\‘° €(Àˆ»\0`Î”° \0000Àv\0EÔ~°ıp`(Â†€ìú0·/ #ÀIB.ud8¹\rÀ\0e€Ş|pPÜx<QàÀé„ó Ø°`ÀÇ¥\0F°ĞÀ\nº@6,æZÔ°Àà;@:ß }UŠzÛÕòY‹‘^h#Wò˜Ãî‚]¸YŒÑ`è(Wü˜×ù®+9‚ä‚â¸7«\$!¹úÂy!p…\$†Å˜P ¿éõ!:Q€I¾\0n\0’,¸ 	‡Š\0&€ˆ\0–6á°àaf@Rªƒ\\^öğğ£È€b—½–ª€À	à54Õ\0×CTi\rS¦ŒËj½@o®ı·ï`¼e\0`f\0!˜\0€A&nœ\0Àf\0˜\0vc3Ì×èÚrÌ…Š‹óSüáÃÃãÚ\n'Ğ¢ØÀº@@Àq€JÃ L@`EN€fÊ(ì*Ä  Éü€i\0Â”U	×eÔ(;€o`îÅ®0à7§”s\0€{3.\"³ç¨.\0Mø³•RÀ9 \0â3ˆĞ0-M\0]€ \0×X£·`€*ß€WÃÆ)Ÿˆ\0Îj7À&‘Ü•)Ì 'À&@Ÿ\0ƒUH¸\nŠª÷€¢7è¸¸?ŒõÆ\n«£øÀcã˜\"ä1\0y¨Ø–€Á(À€£i@\0/Ğ‰€ÃRh¢tB€IIˆ·€Ê˜Qí\r!¥ÿØ²\r:Ğ	8µ0Ñ®.mPÀi†šZv\\é\rP0JŒ\0ÉÖ6²0°7·\0_È¢zÄcù à/€y€ÒJ¶T.¨€`0V©NîËCˆ `4@y\0İ:j|ªş<Ğ\08Àn8UÇº¥ü‰t¬À:ÔÆ­bJÜu)#‡r)]Ch?ººä7\"»,ø&ïÍÜÇûr5à&à1%…\0]\0ŒWˆĞ`€V€™g)tˆPÕ`;’…€u¸xÉd` @Q€›­ìD×°\0Ğ·`€u€š\0zÈ—‰Ùğ !ÀI\0\0dĞ\0sĞ\0	€‡ i0\0éÜÑŸÖüÁJH\n@B6¡\0004\0Ò\0ˆ£iã@\n€á\0º\0lXW€Àkã¢2Z˜¨uÕ\0-@\n€ôy¸*8±Á` 6€Z€[\0tà\0(®@	Û‰6\0¸À`¨Ş [²\0“EÀ\0Èà \0àò\0Xº €p€Ğ\0(Ğ\r \0t	`Hk©¬ ¶ \r\09€*sŞ\0è¨  Àf\0Æ\0ØÕ’¸à0À;\0R\0006Ä@\0@À\0007º´UØ³?-µ\0€î‚|è€\$Ş?\0³\$\0Q2P7§mì†Á€u4T\0õPÒà\nó€wæôˆ	ğ€@VGª\0tø`\r Ê€?\0@Èà\0u\rÂ'\0\0006@S\0†\0006¸˜8pyDÕº€.\0ŠÉM°`Q3\0|\0'9oYc&\0=CÍ€•eño@ÆZ£•‹h€c‘²Ø˜ÀÀHg€\0LÎF¥€e¾;õ˜,s(€@3\0:–m8 ú\0i\0áª6B0 @A\0dêˆ\0‰0¥„€3\0ô\0pBn €>\0+ƒO3eô€\0007õ'p\0@ˆaXp	uà€jdĞªPQé ÔóÇİ\0êL\0ø\r°\0	+Á\0\0Ù\\Ò@xÀEª€p\0h{QÁà@ÀÔ£D”=ú°P€0\0\0§e\0\0\$òù \0q€&,äçx	°@:@l\0rJ\"<ÃiØ@ ™\0000Hf 8ÌU€€¿\0 €@Iı™\0Â–˜¹Xt@!Ãíõåg\0002JRt½ )€N\0‚\0–:\$2(À\0.ïÿÊ—Yt\\®}§9ÉÔM€\n\0Ye[ĞrEˆåâ€Ü\\ø `€ºl\0DØÌHÃµ\0@\0øÄp0\0'ç\0ğE4\$Pæ6€\$j€ÎX\08JG°@{ÀZ\0OÒ¾;à\0000ˆ¨êòjzj‚ƒ¿ à	\0mıY`7ÇZ(p ,€v\0dÎ\ry_ª¿p\r\0ÑòÙ\0mæù\$:„ø¨¦/Áù “nc\$X	Õş¦4Â §n6cs­é x­¦9ÅÓŠíBTYVèj—ü\r\0*è\0×ÒÍ±€_#Â @\n \rêA\0:€H‚ãE ÏV€\0kÑê·G¨èÏP\0D¾QêÆô8@ò½€A0`¶\0!€Ü\0†(:‚¨.ÉAX)CÚ&g€‰„Ü1Éã‘à I\n~9¹ˆÁK@\0s\0j\0Ì˜FÒõ\09[¼(Ñ×\$İ¶s20	-àRr·ˆÂ_È,e¶‚¤÷­æq©¥v\$P0\0007@€Y°PNŞ/UN  ,@,\0i\"À à@ @&;¢\0‰Jdğ\nÅW—QxGÏªª0­°§7Œ>fû>+]€+SLú1V|€ağÃ€-…t†1b8Ä€<CŸF%•Öwpá‰ ©9À_\0Ï8è(æÉËH3œ>´kÁ´¶†(À:¥'\0¤ì@†_@2\0D†V^­xp!”\0OŒL¯\$€ Ï 01²ã­ -x¨àx:¢.Â×º¨€àà<&¨C	6xğ ,Àwì¡i`ì§àQ~\0Äá5Èˆ\rP:¤\0f¥:ppYÄ{z¦\0y\0à²ÊŞŞ€€<¨ÙÍ¼½·ºÓÆ=ğÔÎÀqÆ ÊñH]«†t.ƒ`H\rˆ+…Ï Ù§SÒ½İgÛ:Àn­V³w¦îêµ²	NQ6çÙ\0ï€\r\0	ŒV«õî \nuÏÖ5Cìø\0’¤à;@Q¥»”ØH£HàÀK0«ĞH(Ë`@q€¤úH(µÀ \nÀ)\0’.•½yhj[ °S\0´ÁlˆÀàH‘d	è\0P	€€U€Rlà’\0€€WŒ\$ú¨È–À\0·\0°8”X° \"€q\0v\0ú\0dÛÍ à)À€É\0Úì«ZaXÇ®€Æ–\0`Àe',±-ø5\0ÁÄ°0ëİ\0\0]\0Ø@à`;\0s€ÂÁÂÀ ³dÀïh\0R€·FŒĞ\0à AŸ€‹ÓNÀîi›à ”¢\0;\0Á°	ÃLÀ-f©€A\0Üºëx  @\0E\0ä¶KÃt€Š×LÌä=ÕàM{@x\0K\0ªÌp\0€-À4€ÂC‚˜pÁÀÅ;€fr¼ğ!q\0€Õ\0X\0Ğá#ä³G@\0004€´Bh\0àn\0à1À\r/d\0Ùp@r+‚€\"Øì`D`.€HVh\0006+ğˆ?À5\0;\0›\0€¾ğúOğA€€ª\0ibpÀw}Z÷Àh€xU0˜»6‡Kr\0ø\0 Hƒ­ !—b€ÒE‡®(\0À­ãÀ€c\0Ü\0P@¨0”F€GÏ6˜E]rhğ@7À2\0Ö&Ü 	-€„·€J\0Bˆ¼m}‘ 1À€8Üìäø °€@#€zBµñ8`L ‡2\0{¸\0 `\rğ€D`€°Y \0èğ\0000€3€AK*k Xp €E\0\"ô³Özè{ùãÂ=ÀrÅ \0Ô©ås@@U\0Biú‰t\0˜ñà+\0C€¾2ğ¨8°\0`.˜É\0005R\0ğÒõ»\0€V€´\0<X\0 €dÌ9›ÆÀDË\0èÀ7=t¨ À\06¼8\0XPW’Éƒ29\0Ä}áP»No@)¶£@µxw  	é€©%¶8+øãğ *À`C\0n`sã'QÈOAÀ˜íW>­óèÆZà&@:\0ò¼d  @32«§‡Bnª˜qˆ°à€y€Ï1=\\G pà%½€šËŞĞ0pà€/‰‰\0úŒ\0â`€(„ª\0šØ4!„zhPº6€8&C\0ld`\rÃğ 8ğ±€51ÏTˆtÈdÀ¹„s	ÛªÌ,¿0ÍòlT7Ê1Qß*‘é|³Eòé7Ì1Vß2‘ı|ÒÀÆø±’A/:änz°–Î!‘eÛ¢É7Ì<%ÀP€@9€D\0–	Ï6Op	à&ÏlA_\0¢èğ0 À*\0ĞQcAÈU˜v×Ÿì‚º“Û'—Á¶ı	Ã\r:m¡%¥ßD\0ô|\"Ë¨J—\0&B\\\0œ²ÔŠ0\ncŞ\0*x/y©Ä8K€jZ€_©j¹@Áˆª\r€5Š™¤ –*Ş³!@< ~Â™ÁÇ0¸À!\$½Ã‚ÀoHõcà\$Ú—(Ä\\£\"ĞÀˆå\0l\0“\0î†`\n\0öÀS\0y\0Ö©\nĞÑ04]\0|Ï¼ÂErQ:dåÌ·UkXPş€DÄ\n+#ºŒ@œ€¬|À\nCÀ-À¢7ìq-¿•UûŒa‹\0e·öĞsÔ\r!\0i¡\r¨Üt6”÷\0j†@ˆxzds&`|Õ;€È\0X€ 3w¥\0Ñ2,ˆ\n\0{Œ.?—ì(hb\0/…ˆ¾‚¯\$ÿğÚ€^Ju‘\n(ĞÀ6@_\0¶£VR„¦x .@v€Èw¿Ì&ËTÀe9˜Ílğ š¥Àk€ïbğ5ˆj`Õô•\nZ´ÀÒ…aó†	A¤.t‹§Ûê©œs…U\0D €x©‡)Ô•¹34®;€Ãf´ÍiœdYLl~\0=”Ê€ü)éÕLÀ½Õ31¬£Y-ijâ­Îà½V¸Eù<uZãt8\0\0ĞĞ÷%\0ÆTà©û@ŠT\0000¸¼\0Z\0NYè0ûÀM\0\rwBÿ)QPà:\0·Êº,hxĞ@#H€Î+\\Ô4ë6¥ÀÀ^XZXĞH \0bñˆZ\0#;`à \rÔË\0Ù\0æc0t?ôZ´|[ı\0âE˜R×0ğ€À;Ú¾ÕiL2\$°àÜ\0£ÜæÛ‰qg`-î*¹”\rX8\në¤\0ò]PË&\0X\0`’±€\n@a\0Û”\0@rã€˜ØÀ4²f²¯`À6\0a€o¦«tp`à¡e€ã\0004ÈÙVKö\0\"ÚJ\0\0d:›@!@k1ç\0¶\0¬2DĞ >€#T¶¤â!›\0,\0°\0Èp\0H\n `%@D\0‘ö00@Ì¡€Ù4”IÆ œÀ\0t]Üà°\0€\r§®€,\0óMyš¼WÀ2¤ÿ\0Š\0[1à)µ€\0\0&§2p<vóåPx€ÓFQXÀ\0001€‘oB(p@0g\0èxHä8\0SøÉ*ê›@À\"À	\0f\0ãÉL©` #@€6D;@¹Bu0À+€ZC\0v  \0œ\0\0-\0˜\0C)‹,Š8ƒæjW³€´\0-Î¶\0À@p\0ÔÛĞ6¤€\r	˜Ài\0^\0E¨\0—(p@4\0:–¥ŒÏĞš›@H€_•6\08“ùoãgà,\0h	:\0Hvn@è\n0@,@a€Û}(ºõ@uƒú\0©¾„l•Ğ \0z€q\0¸\0D8 €\n\$€+Á*¯NqD”SHã€9Ì*F\0H(P \0€Y\0v¼õkj4×é¥€G€éH¾Hl˜\nÀ@+@†gĞˆø€‘à\0X€`\0Ä8ÄÌT÷Ú !À\0o~˜ ĞĞ 5€`^XƒˆÜH± \0À	€<Ø‚ÚP b¸ÀE¨“\0›##·FÑ¹€\0007Â\0\$ˆh\näĞ’wÀQ€Ğ\0ä¨-.W›\0	€–[T„\0¨=€À>ÆWİˆıÈ\r	ë«èYjëçàÄCĞ\nÀcôà´–ì†îÔ•ŸaÎEÌÀt€Xæ¶JĞ`0y±t‚\0)\0\$LÜà\0€~æ\0(Íñ8pÀ®µÒ;˜<lÅ``“¹Âs…ç\rÎœG8q\\âùÆsçÎ?œ‡9r\\äùÊs•ç-Î_œÇ9s\\æùÎsç=Î:t\\èùÒs¥çMÎŸG:u\\êùÖs­ç]Î¿‡;v\\ìùÚsµçmÎßÇ;w\\îùŞs½ç}Îÿ<x\\ğùâsÅçŒ\0\0");}
        private function printSvgDatabase($isReturn = false) {$svg = '<svg id="printSvgDatabase" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M10 6c3.9 0 7-.9 7-2s-3.1-2-7-2-7 .9-7 2 3.1 2 7 2zm0 9c-3.9 0-7-.9-7-2v3c0 1.1 3.1 2 7 2s7-.9 7-2v-3c0 1.1-3.1 2-7 2zm0-4c-3.9 0-7-.9-7-2v3c0 1.1 3.1 2 7 2s7-.9 7-2V9c0 1.1-3.1 2-7 2zm0-4c-3.9 0-7-.9-7-2v3c0 1.1 3.1 2 7 2s7-.9 7-2V5c0 1.1-3.1 2-7 2z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgMuplugin($isReturn = false) {$svg = '<svg id="printSvgMuplugin" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M13.6 5.1l-3.1 3.1 1.8 1.8 3.1-3.1c.3-.3.2-1-.3-1.5s-1.1-.6-1.5-.3zm.3-4.8c-.7-.4-9.8 7.3-9.8 7.3S.6 5.5.1 5.9c-.5.4 4 5 4 5S14.6.6 13.9.3zm5.5 9.3c-.5-.5-1.2-.6-1.5-.3l-3.1 3.1 1.8 1.8 3.1-3.2c.3-.2.2-.9-.3-1.4zm-11.7-1c-.7.7-1.1 2.7-1.1 3.8v3.8l-1.2 1.2c-.6.6-.6 1.5 0 2.1s1.5.6 2.1 0l1.2-1.2h3.8c1.2 0 3-.4 3.7-1.1l1.2-.8-8.9-8.9-.8 1.1z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgPlugin($isReturn = false) {$svg = '<svg id="printSvgPlugin" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M13.11 4.36L9.87 7.6 8 5.73l3.24-3.24c.35-.34 1.05-.2 1.56.32.52.51.66 1.21.31 1.55zm-8 1.77l.91-1.12 9.01 9.01-1.19.84c-.71.71-2.63 1.16-3.82 1.16H6.14L4.9 17.26c-.59.59-1.54.59-2.12 0-.59-.58-.59-1.53 0-2.12l1.24-1.24v-3.88c0-1.13.4-3.19 1.09-3.89zm7.26 3.97l3.24-3.24c.34-.35 1.04-.21 1.55.31.52.51.66 1.21.31 1.55l-3.24 3.25z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgTheme($isReturn = false) {$svg = '<svg id="printSvgTheme" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M2 2h5v11H2V2zm6 0h5v5H8V2zm6 0h4v16h-4V2zM8 8h5v5H8V8zm-6 6h11v4H2v-4z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgUpload($isReturn = false) {$svg = '<svg id="printSvgUpload" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M4 15v-3H2V2h12v3h2v3h2v10H6v-3H4zm7-12c-1.1 0-2 .9-2 2h4c0-1.1-.89-2-2-2zm-7 8V6H3v5h1zm7-3h4c0-1.1-.89-2-2-2-1.1 0-2 .9-2 2zm-5 6V9H5v5h1zm9-1c1.1 0 2-.89 2-2 0-1.1-.9-2-2-2s-2 .9-2 2c0 1.11.9 2 2 2zm2 4v-2c-5 0-5-3-10-3v5h10z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgWpcontent($isReturn = false) {$svg = '<svg id="printSvgWpcontent" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M18 12h-2.18c-.17.7-.44 1.35-.81 1.93l1.54 1.54-2.1 2.1-1.54-1.54c-.58.36-1.23.63-1.91.79V19H8v-2.18c-.68-.16-1.33-.43-1.91-.79l-1.54 1.54-2.12-2.12 1.54-1.54c-.36-.58-.63-1.23-.79-1.91H1V9.03h2.17c.16-.7.44-1.35.8-1.94L2.43 5.55l2.1-2.1 1.54 1.54c.58-.37 1.24-.64 1.93-.81V2h3v2.18c.68.16 1.33.43 1.91.79l1.54-1.54 2.12 2.12-1.54 1.54c.36.59.64 1.24.8 1.94H18V12zm-8.5 1.5c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgInfoOutline($isReturn = false) {$svg = '<svg id="printSvgInfoOutline" class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M9 15h2V9H9v6zm1-10c-.5 0-1 .5-1 1s.5 1 1 1 1-.5 1-1-.5-1-1-1zm0-4c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 16c-3.9 0-7-3.1-7-7s3.1-7 7-7 7 3.1 7 7-3.1 7-7 7z"/></g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgWpRoot($isReturn = false) {$svg = '<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.096"></g><g id="SVGRepo_iconCarrier"> <path d="M13.2686 14.2686L15 16M12.0627 6.06274L11.9373 5.93726C11.5914 5.59135 11.4184 5.4184 11.2166 5.29472C11.0376 5.18506 10.8425 5.10425 10.6385 5.05526C10.4083 5 10.1637 5 9.67452 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V10.2C21 9.0799 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H14.3255C13.8363 7 13.5917 7 13.3615 6.94474C13.1575 6.89575 12.9624 6.81494 12.7834 6.70528C12.5816 6.5816 12.4086 6.40865 12.0627 6.06274ZM14 12.5C14 13.8807 12.8807 15 11.5 15C10.1193 15 9 13.8807 9 12.5C9 11.1193 10.1193 10 11.5 10C12.8807 10 14 11.1193 14 12.5Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function printSvgUpdateAlt($isReturn = false) {$svg = '<svg id="printSvgUpdateAlt" class="svg-icon" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 20 20"><path fill="currentColor" d="M5.7 9c.4-2 2.2-3.5 4.3-3.5c1.5 0 2.7.7 3.5 1.8l1.7-2C14 3.9 12.1 3 10 3C6.5 3 3.6 5.6 3.1 9H1l3.5 4L8 9zm9.8-2L12 11h2.3c-.5 2-2.2 3.5-4.3 3.5c-1.5 0-2.7-.7-3.5-1.8l-1.7 1.9C6 16.1 7.9 17 10 17c3.5 0 6.4-2.6 6.9-6H19z"/></svg>'; if ($isReturn) {return $svg;} echo $svg; }
        private function pageActivate() {
?>
<?php
$data = $this->useHandle->activate->getData(); ?>
<h2>Restorer Security</h2>
<p>
    Request activation for this site using the license key to use the WP Staging Restore
</p>
<button class="action" data-action="activate-license">Activate License</button>
<?php $this->useHandle->view->printProcessLoader();?>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageBackupContent() {
?>
<?php
$backupIndex = $this->meta->dataPost['backupIndex']; $data = $this->useHandle->backupListing->getBackupFiles($backupIndex); $metaData = (object)$this->useHandle->backupListing->readBackupMetaDataFile($data['metaFile']); $extractPath = $this->useHandle->extractor->getDefaultExtractPath(); $totalFiles = !empty($metaData->totalFiles) ? (int)$metaData->totalFiles : 0; $sortbyOption = $this->useHandle->view->partSelection($metaData); if (empty($metaData->databaseFile)) { $metaData->databaseFile = ''; } ?>
<div id="backup-extract">
    <ul class="breadcrumb">
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-action="page-main">Home</a></li>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="extract" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">Extract Backup</a></li>
        <li>View Backup</li>
        <?php if (!$data['isMultisite']) : ?>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="restore" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">Restore Backup</a></li>
        <?php endif;?>
    </ul>
    <h3>View Backup</h3>
    <div id="extract-info" class="info-block">
        <div>
            <label>Backup Name</label>
            <span><?php echo $this->kernel->escapeString(basename($metaData->name));?></span>
        </div>
        <div>
            <label>Backup File</label>
            <span><?php echo $this->kernel->escapeString(basename($data['path']));?></span>
        </div>
        <div>
            <label>Backup Size</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->sizeFormat($metaData->backupSize));?></span>
            <span> ( <?php echo $this->kernel->escapeString($totalFiles) . " " . ( $totalFiles > 1 ? "Files" : "File");?> )</span>
        </div>
        <div id="root-path">
            <label>Root Path</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->normalizePath($this->meta->rootPath));?></span>
        </div>
    </div>
    <div id="extract-block" class="action-block hide">
        <h3 id="extract-to-path">Extract to Directory Path</h3>
        <input type="text" name="extract-path" id="extract-path" value="<?php echo $this->kernel->escapeString($extractPath);?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        <label for="extract-path-overwrite" class="checkbox">
            <input type="checkbox" id="extract-path-overwrite" name="extract-path-overwrite" value="1">
            <span>Overwrite directory
                <span data-tooltip="Check this option to completely remove the extract directory before the extraction process"><?php $this->useHandle->view->printSvgInfoOutline();?></span>
            </span>
        </label>
        <?php if ($metaData->isExportingDatabase) :?>
        <label id="normalize-db-file-block" for="normalize-db-file" class="checkbox">
            <input type="checkbox" id="normalize-db-file" name="normalize-db-file" value="1" checked>
            <span>
                Normalize database file
                <span data-tooltip="Check this option to normalize the Database file by replacing the WPSTG tag with actual data">
                    <?php $this->useHandle->view->printSvgInfoOutline();?>
                </span>
            </span>
        </label>
        <?php endif;?>
        <input type="hidden" name="backupfile-path" value="<?php echo $this->kernel->escapeString($data['path']);?>">
        <input type="hidden" name="total-files" value="<?php echo (int)$totalFiles;?>">
        <button class="action" data-action="extract">Extract</button>
        <button class="action-close" data-action="extract-block-close">Close</button>
        <button class="action-cancel hide" data-action="extract-cancel">Cancel</button>
        <button class="action-green hide" data-action="extract-retry">Retry</button>
        <?php $this->useHandle->view->printProcessLoader();?>
    </div>
    <div id="wpstg-restorer-console"></div>
    <div id="extract-list" class="action-block">
        <?php if ($totalFiles > 1) :?>
        <div id="paging">
            <div>
                <input type="text" id="index-filter" name="index-filter" value="" placeholder="filename">
                <button class="action" data-action="filter">Search</button>
                <button class="action-cancel hide" data-action="filter-reset">Reset</button>
            </div>
            <div>
                <?php if (!empty($sortbyOption)) :?>
                <select id="index-sortby" name="index-sortby" data-action="sortby">
                    <?php foreach ($sortbyOption as $sortbyId => $sortbyName) :?>
                    <option value="<?php echo $this->kernel->escapeString($sortbyId);?>"><?php echo $this->kernel->escapeString($sortbyName);?></option>
                    <?php endforeach;?>
                </select>
                <?php endif;?>
                <button class="action" data-action="paging-prev" data-value="1" disabled>Prev</button>
                <button class="action" data-action="paging-next" data-value="1">Next</button>
                <input type="hidden" id="index-total" name="index-total" value="<?php echo (int)$totalFiles;?>">
                <input type="hidden" id="index-page-total" name="index-page-total" value="0">
            </div>
        </div>
        <div id="paging-bottom">
            <div class="left"></div>
            <div class="right"></div>
        </div>
        <?php endif;?>
        <div id="paging-table">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" value=""></th>
                        <th>File</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$pagingData = ''; foreach ($this->useHandle->view->backupPaging($data['path'], $metaData->databaseFile, $pagingData) as $data) : $hasSqlFile = !empty($data[4]) ? " data-is-sqlfile=1" : ""; ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="item[]" value="<?php echo (int)$data[2];?>" <?php echo $this->kernel->escapeString($hasSqlFile);?>>
                        </td>
                        <td>
                            <p title="<?php echo $this->kernel->escapeString($data[1]);?>"><?php echo $this->kernel->escapeString($data[1]);?></p>
                        </td>
                        <td>
                            <p title="<?php echo $this->kernel->sizeFormat($data[3]);?>"><?php echo $this->kernel->sizeFormat($data[3]);?></p>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
            <?php
if (is_object($pagingData)) { $code = '<script id="paging-data" type="application/json">'; $code .= json_encode($pagingData); $code .= '</script>'; echo $code; } ?>
        </div>
    </div>
</div>
<?php }
        private function pageBackupExtract() {
?>
<?php
$backupIndex = $this->meta->dataPost['backupIndex']; $data = $this->useHandle->backupListing->getBackupFiles($backupIndex); $metaData = (object)$this->useHandle->backupListing->readBackupMetaDataFile($data['metaFile']); $extractPath = $this->useHandle->extractor->getDefaultExtractPath(); $totalFiles = !empty($metaData->totalFiles) ? $metaData->totalFiles : 0; $sortbyOption = $this->useHandle->view->partSelection($metaData); if (empty($metaData->databaseFile)) { $metaData->databaseFile = ''; } $extractData = [ 'total-files' => $totalFiles, 'backupfile-path' => $data['path'], 'dbfile-path' => $metaData->databaseFile ]; ?>
<div id="backup-extract">
    <ul class="breadcrumb">
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-action="page-main">Home</a></li>
        <li>Extract Backup</li>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="content" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">View Backup</a></li>
        <?php if (!$data['isMultisite']) : ?>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="restore" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">Restore Backup</a></li>
        <?php endif;?>
    </ul>
    <h3>Extract Backup</h3>
    <div class="info-block">
        <div>
            <label>Backup Name</label>
            <span><?php echo $this->kernel->escapeString(basename($metaData->name));?></span>
        </div>
        <div>
            <label>Backup File</label>
            <span><?php echo $this->kernel->escapeString(basename($data['path']));?></span>
            <?php foreach ($extractData as $key => $value) :?>
            <input type="hidden" name="<?php echo $this->kernel->escapeString($key);?>" id="<?php echo $this->kernel->escapeString($key);?>" value="<?php echo $this->kernel->escapeString($value);?>">
            <?php endforeach;?>
        </div>
        <div>
            <label>Backup Size</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->sizeFormat($metaData->backupSize));?></span>
            <span> ( <?php echo $this->kernel->escapeString($totalFiles) . " " . ( $totalFiles > 1 ? "Files" : "File");?> )</span>
        </div>
        <div>
            <label>Root Path</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->normalizePath($this->meta->rootPath));?></span>
        </div>
    </div>
    <div class="action-block">
        <h3>Extract to Directory Path</h3>
        <div id="action-option">
            <?php if (!empty($sortbyOption)) :?>
            <select id="index-sortby" name="index-sortby">
                <?php foreach ($sortbyOption as $sortbyId => $sortbyName) :?>
                <option value="<?php echo $this->kernel->escapeString($sortbyId);?>"><?php echo $this->kernel->escapeString($sortbyName);?></option>
                <?php endforeach;?>
            </select>
            <?php endif;?>
            <input type="text" name="extract-path" id="extract-path" value="<?php echo $this->kernel->escapeString($extractPath);?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
        <label for="extract-path-overwrite" class="checkbox">
            <input type="checkbox" id="extract-path-overwrite" name="extract-path-overwrite" value="1">
            <span>
                Overwrite directory
                <span data-tooltip="Check this option to completely remove the extract directory before the extraction process">
                    <?php $this->useHandle->view->printSvgInfoOutline();?>
                </span>
            </span>
        </label>
        <?php if ($metaData->isExportingDatabase) :?>
        <label id="normalize-db-file-block" for="normalize-db-file" class="checkbox">
            <input type="checkbox" id="normalize-db-file" name="normalize-db-file" value="1" checked>
            <span>
                Normalize database file
                <span data-tooltip="Check this option to normalize the Database file by replacing the WPSTG tag with actual data">
                    <?php $this->useHandle->view->printSvgInfoOutline();?>
                </span>
            </span>
        </label>
        <?php endif;?>
        <button class="action" data-action="extract">Extract</button>
        <button class="action-close" data-action="page-main">Close</button>
        <button class="action-cancel hide" data-action="extract-cancel">Cancel</button>
        <button class="action-green hide" data-action="extract-retry">Retry</button>
        <?php $this->useHandle->view->printProcessLoader();?>
    </div>
</div>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageBackupList() {
?>
<h2 class="backup-list-header">
    Available Backups <span data-tooltip="Rescan available backups" data-action="reload-backup-list"><?php $this->useHandle->view->printSvgUpdateAlt();?></span>
</h2>
<?php
$listBackup = $this->useHandle->backupListing->getBackupFiles(); if (empty($listBackup)) : ?>
<p>No backup available</p>
    <?php
endif; foreach ($listBackup as $fileIndex => $arrData) : if (!$arrData['isValid']) { continue; } $metaData = (object)$this->useHandle->backupListing->readBackupMetaDataFile($arrData['metaFile']); if (!isset($metaData->id)) { continue; } $backupTypeTitle = !empty($arrData['isMultisite']) ? 'Multi Site' : 'Single Site'; if (!empty($arrData['backupType'])) { switch ($arrData['backupType']) { case 'multi': $backupTypeTitle = 'Entire Network'; break; case 'main-network-site': $backupTypeTitle = 'Main Network Site'; break; case 'network-subsite': $backupTypeTitle = 'Network Subsite'; break; case 'single': $backupTypeTitle = 'Single Site'; break; default: $backupTypeTitle = 'Unknown Backup Type'; } } ?>
<div class="backuplist" data-backup-id="<?php echo $this->kernel->escapeString($metaData->id);?>">
    <main>
        <div>
            <label>Name</label>
            <span class="name" title="<?php echo $this->kernel->escapeString(basename($arrData['path']));?>">
                <?php echo $this->kernel->escapeString($metaData->name);?>
            </span>
        </div>
        <div>
            <label>Type</label>
            <span>
                <?php echo $this->kernel->escapeString($backupTypeTitle);?>
            </span>
        </div>
        <div>
            <label>Created On</label>
            <span>
                <?php
$dateCreated = (new \DateTime())->setTimestamp($metaData->dateCreated); echo $this->kernel->escapeString($this->kernel->setDateTime($dateCreated)); ?>
            </span>
        </div>
        <div>
            <label>Backup Version</label>
            <span>
                <?php
echo $this->kernel->escapeString($metaData->backupVersion); ?>
            </span>
        </div>
        <div>
            <label>Size</label>
            <span>
                <?php echo $this->kernel->escapeString($this->kernel->sizeFormat($metaData->backupSize));?>
            </span>
        </div>
        <div>
            <label>Contains</label>
            <span class="backup-list-tooltip">
                <?php $this->useHandle->view->printBackupListingContains($metaData); ?>
            </span>
        </div>
    </main>
    <aside>
        <div>
            <button class="action" data-page="extract" data-index="<?php echo $this->kernel->escapeString($fileIndex);?>">Extract Backup</button>
            <?php if ($arrData['isMultisite']) : ?>
            <button class="action-disabled" data-tooltip="The restorer does not support Restore for Multisite backups">Restore Backup</button>
            <?php else : ?>
            <button class="action" data-page="restore" data-index="<?php echo $this->kernel->escapeString($fileIndex);?>">Restore Backup</button>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php endforeach;?>
<?php }
        private function pageBackupRestore() {
?>
<?php
$backupIndex = $this->meta->dataPost['backupIndex']; $data = $this->useHandle->backupListing->getBackupFiles($backupIndex); $metaData = (object)$this->useHandle->backupListing->readBackupMetaDataFile($data['metaFile']); if ($this->useHandle->wpcore->isWpMultisite() || $data['isMultisite']) { $this->kernel->addBootupError('wpmultiste', 'The restorer does not yet support restoring backups for WordPress Multisites.'); $this->useHandle->view->render('page-bootup-error'); return; } if (empty($metaData->databaseFile)) { $metaData->databaseFile = ''; } $extractPath = $this->useHandle->extractor->getDefaultExtractPath(); $totalFiles = !empty($metaData->totalFiles) ? (int)$metaData->totalFiles : 0; $wpcoreConfig = (object)$this->useHandle->wpcore->getConfig(); $wpBakeryActive = !empty($metaData->wpBakeryActive) ? 1 : 0; $hasExportParts = count(array_filter([ $metaData->isExportingPlugins, $metaData->isExportingMuPlugins, $metaData->isExportingThemes, $metaData->isExportingUploads, $metaData->isExportingOtherWpContentFiles, $metaData->isExportingDatabase, ])); $restoreData = [ 'total-files' => $totalFiles, 'wp-version' => $metaData->wpVersion, 'backupfile-path' => $data['path'], 'sqlfile-path' => $metaData->databaseFile, 'searchreplace-backupsiteurl' => $metaData->siteUrl, 'searchreplace-backuphomeurl' => $metaData->homeUrl, 'searchreplace-backupwpbakeryactive' => (int)$wpBakeryActive, 'searchreplace-siteurl' => $wpcoreConfig->siteurl, 'searchreplace-homeurl' => $wpcoreConfig->homeurl, ]; $restoreList = $this->useHandle->view->partRestoreList($metaData, $wpcoreConfig); ?>
<div id="backup-extract">
    <ul class="breadcrumb">
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-action="page-main">Home</a></li>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="extract" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">Extract Backup</a></li>
        <li><a href="<?php $this->useHandle->view->printAppFile();?>" data-page="content" data-index="<?php echo $this->kernel->escapeString($backupIndex);?>">View Backup</a></li>
        <li>Restore Backup</li>
    </ul>
    <h3>Restore Backup</h3>
    <div class="info-block">
        <div>
            <label>Backup Name</label>
            <span><?php echo $this->kernel->escapeString(basename($metaData->name));?></span>
        </div>
        <div>
            <label>Backup File</label>
            <span><?php echo $this->kernel->escapeString(basename($data['path']));?></span>
            <?php foreach ($restoreData as $key => $value) :?>
            <input type="hidden" name="<?php echo $this->kernel->escapeString($key);?>" id="<?php echo $this->kernel->escapeString($key);?>" value="<?php echo $this->kernel->escapeString($value);?>">
            <?php endforeach;?>
        </div>
        <div>
            <label>Backup Size</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->sizeFormat($metaData->backupSize));?></span>
            <span> ( <?php echo $this->kernel->escapeString($totalFiles) . " " . ( $totalFiles > 1 ? "Files" : "File");?> )</span>
        </div>
        <div>
            <label>Root Path</label>
            <span><?php echo $this->kernel->escapeString($this->kernel->normalizePath($this->meta->rootPath));?></span>
        </div>
    </div>
    <div class="action-block">
        <?php if ($hasExportParts) : ?>
        <h3>Available Contents</h3>
        <div id="restore-table">
            <table>
                <thead>
                    <tr>
                        <th>Parts</th>
                        <th>Path</th>
                        <th>Restore</th>
                        <th>Overwrite</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restoreList as $type => $data) : if (!$data['status']) : continue; endif; if (!empty($metaData->indexPartSize) && !$data['hasIndexPartSize']) : continue; endif; if (in_array($data['name'], ['lang', 'dropins']) && !$data['hasIndexPartSize']) : ?>
                    <tr class="hide">
                        <?php else : ?>
                    <tr>
                        <?php endif; ?>
                        <td>
                            <p title="<?php echo $this->kernel->escapeString($type);?>"><?php echo $this->kernel->escapeString($type);?></p>
                        </td>
                        <td>
                            <p title="<?php echo $this->kernel->escapeString($data['path']);?>"><?php echo $this->kernel->escapeString($this->kernel->normalizePath($data['path']));?></p>
                        </td>
                        <td>
                            <?php if ($data['restore'] === 1) : ?>
                            <input type="checkbox" name="restore-<?php echo $this->kernel->escapeString($data['name']);?>" value="1" checked>
                            <?php elseif ($data['overwrite'] === 2) : ?>
                            <input type="checkbox" name="restore-bydefault-<?php echo $this->kernel->escapeString($data['name']);?>" value="1" checked disabled>
                            <?php elseif ($data['overwrite'] === 3) : ?>
                            <input type="checkbox" name="restore-bydefault-<?php echo $this->kernel->escapeString($data['name']);?>" value="0" disabled>
                            <?php else : ?>
                            <input type="checkbox" name="restore-<?php echo $this->kernel->escapeString($data['name']);?>" value="1">
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if ($data['overwrite'] === 1) : ?>
                            <input type="checkbox" name="overwrite-<?php echo $this->kernel->escapeString($data['name']);?>" value="1" checked>
                            <?php elseif ($data['overwrite'] === 2) : ?>
                            <input type="checkbox" name="overwrite-bydefault-<?php echo $this->kernel->escapeString($data['name']);?>"" value=" 1" checked disabled>
                            <?php elseif ($data['overwrite'] === 3) : ?>
                            <input type="checkbox" name="overwrite-bydefault-<?php echo $this->kernel->escapeString($data['name']);?>" value="0" disabled>
                            <?php else : ?>
                            <input type="checkbox" name="overwrite-<?php echo $this->kernel->escapeString($data['name']);?>" value="1">
                            <?php endif;?>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
        <button class="action" data-action="restore">Restore</button>
        <button class="action-close" data-action="page-main">Close</button>
        <button class="action-cancel hide" data-action="restore-cancel">Cancel</button>
        <button class="action-green hide" data-action="restore-retry">Retry</button>
            <?php $this->useHandle->view->printProcessLoader();?>
        <?php else : ?>
        <p>No contents available to restore</p>
        <div class="action-block">
            <button class="action-close" data-action="page-main">Close</button>
        </div>
        <?php endif; ?>
    </div>
</div>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageBootupError() {
?>
<h2>Restorer Error</h2>
<p>
    WP Staging Restore could not continue for a reason:
</p>
<ul>
    <?php foreach ($this->kernel->getBootupError() as $id => $text) :?>
    <li><?php echo $this->kernel->escapeString($text); ?></li>
    <?php endforeach;?>
</ul>
<button class="action" data-action="page-main">Reload Page</button>
<?php }
        private function pageHash() {
?>
<script id="wpstg-restorer-page"></script>
<?php }
        private function pageLogin() {
?>
<h2>Restorer Security</h2>
<p>
    Please enter the backup file name or a valid license key.
</p>
<input class="action" type="text" name="backupfile" id="backupfile" value="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus>
<button class="action" data-action="verify-backup-filename">Verify</button>
<?php $this->useHandle->view->printProcessLoader();?>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageLogout() {
?>
<h2>Restorer Security</h2>
<p>
    WP Staging Restore session will terminate
</p>
<div id="logout" class="action-block">
    <label class="checkbox">
        <input type="checkbox" name="remove-app-file" id="remove-app-file" value="1">
        <span>
            Remove wpstg-restore.php
            <span data-tooltip="Check this option to remove the wpstg-restore.php file">
                <?php $this->useHandle->view->printSvgInfoOutline();?>
            </span>
        </span>
    </label>
</div>
<button class="action" data-action="access-terminate">Logout</button>
<button class="action-close" data-action="page-main">Cancel</button>
<?php }
        private function pageMain() {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="access-token" content="<?php echo $this->useHandle->access->getInitialToken();?>">
    <meta name="app-file" content="<?php $this->useHandle->view->printAppFile();?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php $this->useHandle->view->printAssets('favicon-png32');?>">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="<?php $this->useHandle->view->printAssets('favicon-ico');?>">
    <title>WP Staging | Restore v<?php $this->useHandle->view->printVersion();?></title>
    <link rel="stylesheet" type="text/css" media="all" href="<?php $this->useHandle->view->printAssets('css');?>">
    <script type="text/javascript" src="<?php $this->useHandle->view->printAssets('js');?>"></script>
</head>
<body>
    <div id="wpstg-restorer">
        <?php
$activateIsActive = $this->useHandle->activate->isActive(); $accesshasSession = $this->useHandle->access->hasSession(); ?>
        <header>
            <div class="header-left">
                <img src="<?php $this->useHandle->view->printAssets('logo');?>">
            </div>
            <div class="header-right">
                <table>
                    <tr>
                        <td>
                            <span class="app-name">Restore</span>
                            <span class="app-version">v<?php $this->useHandle->view->printVersion();?></span>
                            <?php if ($activateIsActive && $accesshasSession) :?>
                            <span class="app-license-type"><?php $this->useHandle->view->printLicenseType();?></span>
                            <?php endif;?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php if ($activateIsActive && $accesshasSession) :?>
                            <span class="app-license-owner">
                                Licensed to: <?php $this->useHandle->view->printLicenseOwner();?>
                            </span>
                            <?php endif;?>
                        </td>
                    </tr>
                </table>
                <?php if ($accesshasSession) :?>
                <span class="app-logout">
                    <a href="<?php $this->useHandle->view->printAppFile();?>" data-action="page-logout">Logout</a>
                </span>
                <?php endif;?>
            </div>
        </header>
        <div class="content">
            <?php
if (!empty($this->kernel->getBootupError())) { $this->useHandle->view->render('page-bootup-error'); } elseif (!$accesshasSession) { $this->useHandle->view->render('page-login'); } elseif (!$activateIsActive) { $this->useHandle->view->render('page-activate'); } elseif (!$this->useHandle->wpcore->isAvailable()) { $this->useHandle->view->render('page-wpcore-install'); } elseif (!$this->useHandle->wpcore->isReady()) { if (!$this->useHandle->wpcore->isDbConnect()['success']) { $this->useHandle->wpcore->resetDbConfig(); $this->useHandle->view->render('page-wpcore-setup-db'); } elseif (!$this->useHandle->wpcore->isDbInstalled()) { $this->useHandle->view->render('page-wpcore-setup-site'); } else { $this->useHandle->view->render('page-wpcore-setup-complete'); } } else { $this->useHandle->view->render('page-hash'); }?>
        </div>
    </div>
</body>
</html>
<?php }
        private function pageWpcoreInstall() {
?>
<h2>Install WordPress</h2>
<p>
    The WordPress Core is not installed.
</p>
<div id="wpcore" class="form-block">
    <div>
        <label>WordPress Version</label>
        <select id="wpversion" name="wpversion">
            <option value="latest">Latest</option>
            <?php foreach ($this->getWpVersion() as $id => $wpVersion) : ?>
            <option value="<?php echo $this->kernel->escapeString($wpVersion);?>"><?php echo $this->kernel->escapeString($wpVersion);?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<button class="action" data-action="wpcore-install">Install WordPress Core</button>
<?php $this->useHandle->view->printProcessLoader();?>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageWpcoreSetupComplete() {
?>
<h2>Install WordPress</h2>
<p>
    WP Staging Restore detects your site has a Database installed. No further action is required.
</p>
<p>
    Click the Finish button to complete the WordPress installation.
</p>
<button class="action" data-action="wpcore-setup-complete">Finish</button>
<?php $this->useHandle->view->printProcessLoader();?>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageWpcoreSetupDb() {
?>
<?php
$wpPrefix = $this->useHandle->cache->get('wpprefix', 'setup'); $wpPrefix = !empty($wpPrefix) ? $wpPrefix : ''; ?>
<h2>Database Settings</h2>
<div id="dbconfig" class="form-block">
    <div>
        <label>Database Server</label>
        <input type="text" name="dbhost" id="dbhost" value="localhost" placeholder="localhost" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus>
    </div>
    <div>
        <label>Database Name</label>
        <input type="text" name="dbname" id="dbname" value="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Database User</label>
        <input type="text" name="dbuser" id="dbuser" value="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Database Password</label>
        <input type="password" name="dbpass" id="dbpass" value="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Table Prefix</label>
        <input type="text" name="dbprefix" id="dbprefix" value="<?php echo $this->kernel->escapeString($wpPrefix);?>" placeholder="wp_" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Custom Port</label>
        <input type="text" name="dbport" id="dbport" value="" placeholder="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Enable SSL</label>
        <input type="checkbox" name="dbssl" id="dbssl" value="1"> <span>Disable this option if not supported by the database</span>
    </div>
</div>
<button class="action" data-action="wpcore-setup-db">Submit</button>
<?php $this->useHandle->view->printProcessLoader();?>
<div id="wpstg-restorer-console"></div>
<?php }
        private function pageWpcoreSetupSite() {
?>
<h2>Install WordPress</h2>
<p>
    Click submit to complete the WordPress installation.
</p>
<div id="siteconfig" class="form-block">
    <ul>
        <li>If you restore a backup file in the next step, the login credentials you enter here will be overwritten by those from the backup.</li>
        <li>If you don't restore a backup, you can log in to WordPress using the credentials provided below.</li>
    </ul>
    <div>
        <label>Site Title</label>
        <input type="text" name="sitetitle" id="sitetitle" value="WP Staging | Restore" placeholder="Site Title" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus>
    </div>
    <div>
        <label>Admin Username</label>
        <input type="text" name="siteuser" id="siteuser" value="wpstg-restore" placeholder="Enter Admin User" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Admin Email</label>
        <input type="email" name="siteemail" id="siteemail" value="" placeholder="Enter Admin Email Address" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div>
        <label>Admin Password</label>
        <input type="password" name="sitepass" id="sitepass" value="" placeholder="Enter Admin Password" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
</div>
<button class="action" data-action="wpcore-setup-site">Submit</button>
<?php $this->useHandle->view->printProcessLoader();?>
<button class="action-green hide" data-action="page-main">Continue</button>
<div id="wpstg-restorer-console"></div>
<?php }
    }
    final class WpCore { private $kernel; private $meta; private $useHandle; private $taskFile; private $dbConfigFile; private $wpConfigFile; private $downloadUrl; private $maintenanceFile; const WPCORE_INSTALL_FAILURE = 0; const WPCORE_INSTALL_SUCCESS = 1; const WPCORE_INSTALL_DONE = 2; const IS_STAGING_KEY = 'wpstg_is_staging_site'; const STAGING_FILE = '.wp-staging'; public function __construct(\WPStagingRestorer $kernel) { $this->kernel = $kernel; $this->meta = $this->kernel->getMeta(); $this->useHandle = $this->kernel->getHandle(__CLASS__, ['file', 'cache']); $this->taskFile = $this->meta->tmpPath . '/wpstg-task-wpcore.php'; $this->dbConfigFile = $this->meta->tmpPath . '/wpstg-dbconfig.php'; $this->wpConfigFile = $this->locateWpConfigFile(); $this->downloadUrl = 'https://wordpress.org'; $this->maintenanceFile = $this->meta->rootPath . '/.maintenance'; } private function loadLibrary(): bool { static $isLoaded = false; if ($isLoaded) { return true; } if (!$this->isAvailable()) { return false; } if (!$this->isReady()) { return false; } $isMaintenance = $this->isMaintenance(); if ($isMaintenance) { $this->enableMaintenance(false); } try { define('SHORTINIT', true); require_once __DIR__ . '/wp-load.php'; wp_plugin_directory_constants(); require_once ABSPATH . WPINC . '/class-wp-textdomain-registry.php'; if (!isset($GLOBALS['wp_textdomain_registry']) || !($GLOBALS['wp_textdomain_registry'] instanceof \WP_Textdomain_Registry)) { $GLOBALS['wp_textdomain_registry'] = new \WP_Textdomain_Registry(); } foreach ( [ 'l10n.php', 'class-wp-user.php', 'class-wp-roles.php', 'class-wp-role.php', 'class-wp-session-tokens.php', 'class-wp-user-meta-session-tokens.php', 'http.php', 'formatting.php', 'capabilities.php', 'user.php', 'link-template.php' ] as $file ) { require_once ABSPATH . WPINC . '/' . $file; } wp_cookie_constants(); foreach ( [ 'vars.php', 'kses.php', 'cron.php', 'rest-api.php', 'pluggable.php', 'theme.php' ] as $file ) { require_once ABSPATH . WPINC . '/' . $file; } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return false; } if ($isMaintenance) { $this->enableMaintenance(true); } $isLoaded = true; return true; } public function maybeUpgradeDatabase(): bool { if (!$this->loadLibrary()) { return false; } try { if (file_exists(trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php')) { global $wpdb, $wp_db_version, $wp_current_db_version; wp_templating_constants(); require_once ABSPATH . WPINC . '/class-wp-theme.php'; require_once ABSPATH . WPINC . '/class-wp-walker.php'; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $wp_current_db_version = (int)__get_option('db_version'); if (!empty($wp_current_db_version) && !empty($wp_db_version) && $wp_db_version !== $wp_current_db_version) { $wpdb->suppress_errors(); wp_upgrade(); $this->kernel->log(sprintf('WordPress database upgraded successfully. Old version: %s, New version: %s', $wp_current_db_version, $wp_db_version), __METHOD__); return true; } } else { $this->kernel->log('Could not upgrade WordPress database version as the wp-admin/includes/upgrade.php file does not exist', __METHOD__); } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); } return false; } public function getDirectoryAdapter(): Directory { $directory = new Directory($this); return $directory; } public function getDatabaseAdapter(): DatabaseAdapter { $database = new DatabaseAdapter($this); return $database; } public function getSearchReplacer(): SearchReplacer { $searchReplacer = new SearchReplacer($this->kernel, $this, $this->kernel->makeInstance(SubsitesSearchReplacer::class)); return $searchReplacer; } public function maybeRemoveStagingStatus(): bool { if (!$this->loadLibrary()) { return false; } if (defined('WPSTAGING_DEV_SITE') && (bool)constant('WPSTAGING_DEV_SITE') === true) { return false; } if (file_exists(ABSPATH . self::STAGING_FILE)) { return false; } if (get_option(self::IS_STAGING_KEY) === "true") { return delete_option(self::IS_STAGING_KEY); } return false; } public function flushObjectCache(): bool { if (!$this->loadLibrary()) { return false; } $dropInFile = wp_normalize_path(WP_CONTENT_DIR) . '/object-cache.php'; clearstatcache(true, $dropInFile); if (!file_exists($dropInFile) || !function_exists('wp_cache_flush')) { return true; } try { wp_cache_flush(); } catch (\Throwable $e) { $this->kernel->log('Failed to flush object cache', __METHOD__); $this->kernel->log($e, __METHOD__); return false; } return true; } public function getConfig(bool $force = false) { $data = $this->useHandle->cache->get('wpcoreconfig', 'config'); if (!$force && $data !== null) { return $data; } return $this->saveConfig(); } public function getBackupPath(): string { $backupPath = $this->meta->backupPath; $config = $this->getConfig(false); if (empty($config) || empty($config['uploads'])) { return $backupPath; } $uploadPath = $config['uploads']; if ($uploadPath !== $this->meta->uploadPath) { $backupPath = $uploadPath . '/' . $this->meta->backupDir; $this->kernel->log($backupPath); } return $backupPath; } public function saveConfig() { if (!$this->loadLibrary()) { return false; } list( $host, $port, $socket, $isIPv6 ) = $this->parseDbHost(DB_HOST); $siteUrl = get_option('siteurl'); $homeUrl = get_option('home'); $guessUrl = wp_guess_url(); if ($guessUrl !== $siteUrl) { $siteUrl = $guessUrl; $homeUrl = $guessUrl; } $uploads = wp_upload_dir(null, false, false); $keys = [ 'abspath' => ABSPATH, 'uploads' => wp_normalize_path($uploads['basedir']), 'plugins' => wp_normalize_path(WP_PLUGIN_DIR), 'muplugins' => wp_normalize_path(WPMU_PLUGIN_DIR), 'themes' => wp_normalize_path(get_theme_root(get_template())), 'wpcontent' => wp_normalize_path(WP_CONTENT_DIR), 'lang' => wp_normalize_path(WP_LANG_DIR), 'dbname' => DB_NAME, 'dbuser' => DB_USER, 'dbpass' => DB_PASSWORD, 'dbhost' => $host, 'dbport' => $port, 'dbssl' => defined('MYSQL_CLIENT_FLAGS') ? 1 : 0, 'dbprefix' => isset($GLOBALS['table_prefix']) ? $GLOBALS['table_prefix'] : 'wp_', 'dbcharset' => DB_CHARSET, 'dbcollate' => DB_COLLATE, 'siteurl' => $siteUrl, 'homeurl' => $homeUrl, 'uploadurl' => $uploads['baseurl'], 'multisite' => defined('WP_ALLOW_MULTISITE') && constant('WP_ALLOW_MULTISITE') && defined('MULTISITE') && constant('MULTISITE') ? 1 : 0 ]; if ($this->useHandle->cache->put('wpcoreconfig', $keys, 'config')) { return $keys; } return false; } public function isMaintenance(): bool { clearstatcache(); return file_exists($this->maintenanceFile); } public function enableMaintenance(bool $isMaintenance): bool { if ($isMaintenance && !$this->isMaintenance()) { file_put_contents($this->maintenanceFile, '<?php $upgrading = time() ?>', LOCK_EX); $this->kernel->chmod($this->maintenanceFile, false, __LINE__); return true; } if (!$isMaintenance && $this->isMaintenance()) { $this->kernel->unlink($this->maintenanceFile, __LINE__); return true; } return false; } public function isAvailable(): bool { clearstatcache(); return file_exists($this->meta->rootPath . '/wp-load.php') && file_exists($this->meta->rootPath . '/wp-blog-header.php') && file_exists($this->meta->rootPath . '/wp-settings.php') && file_exists($this->meta->rootPath . '/wp-includes/load.php') && file_exists($this->meta->rootPath . '/wp-admin/admin.php') && is_dir($this->meta->rootPath . '/wp-content'); } public function isReady(): bool { if (!file_exists($this->wpConfigFile)) { return false; } if (!$this->isWpIndex()) { return false; } return true; } public function isWpIndex(): bool { $wpIndex = $this->meta->rootPath . '/index.php'; if (!file_exists($wpIndex)) { return false; } $content = file_get_contents($wpIndex, false, null, 0, 8 * 1024); if (empty($content) || strpos($content, '/wp-blog-header.php') === false) { return false; } $wpIndexSetup = $this->meta->rootPath . '/index-wp.php'; if (file_exists($wpIndexSetup)) { $this->kernel->unlink($wpIndexSetup, __LINE__); } return true; } public function isWpMultisite(): bool { if (!file_exists($this->wpConfigFile)) { return false; } $content = file_get_contents($this->wpConfigFile, false, null, 0, 8 * 1024); if (empty($content)) { return false; } if (!preg_match('@define\(\s+(\'|")WP_ALLOW_MULTISITE(\'|"),\s+(true|1)\s+\)\;@', $content) && !preg_match('@define\(\s+(\'|")MULTISITE(\'|"),\s+(true|1)\s+\)\;@', $content)) { return false; } return true; } private function setTaskStatus($status, $text, $callback = false): bool { $data = $this->getTaskStatus(); if (empty($data) || !is_array($data)) { $data[0] = [ 'status' => $status, 'text' => $text, 'callback' => $callback, ]; } else { $lastData = !empty($data[0]) && count($data) > 0 ? $data[count($data) - 1] : $data; if ($lastData['status'] !== self::WPCORE_INSTALL_DONE) { $data[] = [ 'status' => $status, 'text' => $text, 'callback' => $callback, ]; } } $this->kernel->log($text, __METHOD__); return $this->useHandle->cache->put('wpcoretask', $data); } public function getTaskStatus(): array { $data = $this->useHandle->cache->get('wpcoretask'); return is_array($data) ? $data : []; } public function resetTaskStatus(): bool { return $this->useHandle->cache->remove('wpcoretask'); } private function tempName(string $input): string { return substr(md5($input), 0, 12); } public function downloadStatus(string $savePath): int { $fileName = $this->tempName(basename($savePath)) . '.txt'; $filePath = $this->meta->tmpPath . '/download-status-' . $fileName; if (!file_exists($filePath)) { return 0; } $data = file_get_contents($filePath); if (empty($data)) { return 0; } $data = strtok($data, '|'); return (int)$data; } private function downloadFile(string $fileUrl, string $savePath, bool $refresh = false): bool { if ($refresh && file_exists($savePath)) { unlink($savePath); } $saveName = basename($savePath); $this->setTaskStatus(self::WPCORE_INSTALL_SUCCESS, sprintf('Downloading %s as %s', $fileUrl, $saveName), ['downloadStatus', $savePath]); if (!($fileHandle = fopen($savePath, 'wb'))) { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to create %s', $saveName)); return false; } $curlHandle = curl_init($fileUrl); $fileName = $this->tempName($saveName) . '.txt'; curl_setopt_array($curlHandle, [ CURLOPT_USERAGENT => $this->kernel->userAgent(), CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_HEADER => false, CURLOPT_FOLLOWLOCATION => true, CURLOPT_BINARYTRANSFER => true, CURLOPT_NOPROGRESS => false, CURLOPT_FORBID_REUSE => true, CURLOPT_FRESH_CONNECT => true, CURLOPT_TIMEOUT => 180, CURLOPT_FILE => $fileHandle, CURLOPT_PROGRESSFUNCTION => function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($fileName, $fileUrl) { if (!empty($downloadSize)) { $percentage = ($downloaded / $downloadSize) * 100; file_put_contents($this->meta->tmpPath . '/download-status-' . $fileName, $percentage . '|' . $fileUrl, LOCK_EX); } }, ]); if (!($status = curl_exec($curlHandle))) { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to download %s: %s', $fileUrl, curl_error($curlHandle))); } curl_close($curlHandle); fclose($fileHandle); return $status ? true : false; } private function checksum(string $zipFile, string $md5File): bool { return trim(file_get_contents($md5File)) === md5_file($zipFile); } private function extractFile(string $zipFile): bool { $zipFileName = basename($zipFile); $this->setTaskStatus(self::WPCORE_INSTALL_SUCCESS, sprintf('Extracting %s', $zipFileName), ['wpcorestatusextract']); try { $zip = new \ZipArchive(); if ($zip->open($zipFile) && $zip->extractTo($this->meta->tmpPath)) { $this->useHandle->cache->put('wpcorestatusextract', '<!--{{taskCallbackDone}}-->'); } $zip->close(); } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to extract %s: %s', $zipFileName, $e->getMessage())); return false; } return true; } private function copyToRootPath(): bool { $this->setTaskStatus(self::WPCORE_INSTALL_SUCCESS, 'Copying WordPress files to the root path', ['wpcorestatuscopy']); $dstPath = $this->meta->rootPath; $srcPath = $this->meta->tmpPath . '/wordpress'; if (!is_dir($dstPath) || $dstPath === '/' || !is_dir($srcPath) || $srcPath === '/') { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, 'Failed to copy WordPress files to the root path'); return false; } try { $this->useHandle->cache->remove('wpcorestatuscopy'); $dirIterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator($srcPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST ); foreach ($dirIterator as $item) { $filePath = $this->kernel->normalizePath($dstPath . '/' . $dirIterator->getSubPathname()); if ($item->isDir()) { $this->kernel->mkdir($filePath, __LINE__); } else { if ($filePath === $this->meta->rootPath . '/index.php') { $filePath = $dstPath . '/index-wp.php'; } $itemCopy = $this->kernel->normalizePath($item->getPathname()); $this->kernel->mkdir(dirname($filePath), __LINE__); if (!rename($itemCopy, $filePath)) { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to copy WordPress file: %s', $this->kernel->stripRootPath($filePath))); return false; } $this->useHandle->cache->put('wpcorestatuscopy', $this->kernel->stripRootPath($filePath)); } } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to copy WordPress files to the root path: %s', $e->getMessage())); return false; } $this->useHandle->cache->put('wpcorestatuscopy', '<!--{{taskCallbackDone}}-->'); $this->useHandle->file->removeDir($srcPath); return true; } public function getTaskResponse(): array { $data = $this->getTaskStatus(); if (empty($data) || !is_array($data)) { return [ 'success' => true, 'data' => [ 'status' => self::WPCORE_INSTALL_SUCCESS, 'content' => 'Checking.. please wait.', ] ]; } $lastData = !empty($data[0]) && count($data) > 0 ? $data[count($data) - 1] : $data; $content = ''; foreach ($data as $k => $arr) { $text = $arr['text']; if (!empty($arr['callback']) && is_array($arr['callback'])) { if ($arr['callback'][0] === 'downloadStatus') { $percent = $this->downloadStatus($arr['callback'][1]); if ($percent > 0 && $percent < 100) { $text .= '.. ' . $percent . "%\n"; } } elseif (substr($arr['callback'][0], 0, 12) === 'wpcorestatus') { $status = $this->useHandle->cache->get($arr['callback'][0]); if (!empty($status)) { $text .= $status === '<!--{{taskCallbackDone}}-->' ? ' was successful' : ': ' . $status; } } } if (!empty($text)) { $content .= $text . "\n"; } } return [ 'success' => true, 'data' => [ 'status' => $lastData['status'], 'content' => $content ] ]; } public function runTask(): bool { $this->kernel->maxExecutionTime(240); $version = !empty($this->meta->dataPost['wpcore-version']) ? $this->meta->dataPost['wpcore-version'] : 'latest'; $zipFileName = 'wordpress-' . $version . '.zip'; $zipUrl = $this->downloadUrl . '/' . $zipFileName; $md5Url = $zipUrl . '.md5'; $zipFile = $this->meta->tmpPath . '/' . $zipFileName; $md5File = $this->meta->tmpPath . '/' . $zipFileName . '.md5'; clearstatcache(); if (!file_exists($zipFile) && !$this->downloadFile($zipUrl, $zipFile)) { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to download %s', $zipUrl)); unlink($zipFile); return false; } if (!file_exists($md5File) && !$this->downloadFile($md5Url, $md5File)) { $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Failed to download %s', $md5Url)); unlink($md5File); return false; } $this->setTaskStatus(self::WPCORE_INSTALL_SUCCESS, sprintf('Validating checksum %s', $zipFileName), ['wpcorestatuschecksum']); if (!$this->checksum($zipFile, $md5File)) { unlink($zipFile); unlink($md5File); $this->setTaskStatus(self::WPCORE_INSTALL_FAILURE, sprintf('Invalid checksum for %s', $zipFileName)); return false; } $this->useHandle->cache->put('wpcorestatuschecksum', '<!--{{taskCallbackDone}}-->'); if (!$this->extractFile($zipFile)) { return false; } if (!$this->copyToRootPath()) { return false; } $this->setTaskStatus(self::WPCORE_INSTALL_DONE, 'Installing WordPress was successful'); return true; } private function randomNumber($min = null, $max = null): int { static $rndValue; $maxRandomNumber = 3000000000 === 2147483647 ? (float) '4294967295' : 4294967295; if ($min === null) { $min = 0; } if ($max === null) { $max = $maxRandomNumber; } $min = (int) $min; $max = (int) $max; static $useRandomIntFunctionality = true; if ($useRandomIntFunctionality) { try { $smax = max($min, $max); $smin = min($min, $max); $val = random_int($smin, $smax); if ($val !== false) { return abs((int) $val); } else { $useRandomIntFunctionality = false; } } catch (\Throwable $e) { $useRandomIntFunctionality = false; } } if ($rndValue === null || strlen($rndValue) < 8) { static $seed = ''; $rndValue = md5(uniqid(microtime() . mt_rand(), true) . $seed); $rndValue .= sha1($rndValue); $rndValue .= sha1($rndValue . $seed); $seed = md5($seed . $rndValue); } $value = substr($rndValue, 0, 8); $rndValue = substr($rndValue, 8); $value = abs(hexdec($value)); $value = $min + ( $max - $min + 1 ) * $value / ( $maxRandomNumber + 1 ); return abs((int) $value); } private function generateSaltKey(int $length = 12, bool $specialChars = true, bool $extraSpecialChars = false): string { $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; if ($specialChars) { $chars .= '!@#$%^&*()'; } if ($extraSpecialChars) { $chars .= '-_ []{}<>~`+=,.;:/?|'; } $saltkey = ''; for ($i = 0; $i < $length; $i++) { $saltkey .= substr($chars, $this->randomNumber(0, strlen($chars) - 1), 1); } return $saltkey; } private function parseDbHost(string $host) { $socket = null; $isIPv6 = false; $socketPos = strpos($host, ':/'); if ($socketPos !== false) { $socket = substr($host, $socketPos + 1); $host = substr($host, 0, $socketPos); } if (substr_count($host, ':') > 1) { $pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#'; $isIPv6 = true; } else { $pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#'; } $matches = []; $result = preg_match($pattern, $host, $matches); if ($result !== 1) { return false; } $host = !empty($matches['host']) ? $matches['host'] : ''; $port = !empty($matches['port']) ? abs((int) $matches['port']) : null; return [$host, $port, $socket, $isIPv6]; } public function dbHandle(): Database { $dbData = $this->getDbConfig(); if (empty($dbData)) { throw new \BadMethodCallException('Failed to read database config'); } return new Database($this->kernel, $dbData); } public function isDbConnect(): array { clearstatcache(); if (!file_exists($this->dbConfigFile)) { $dbData = $this->parseWpConfigForDb(); $isSaveDbConfig = false; if (!empty($dbData) && is_array($dbData)) { $this->meta->dataPost['db-data'] = $dbData; $isSaveDbConfig = $this->saveDbConfig()['success'] === true; unset($this->meta->dataPost['db-data']); } if (!$isSaveDbConfig) { return ['success' => false, 'data' => 'Configuration not found']; } } try { $dbHandle = $this->dbHandle(); if ($dbHandle->connect() === false) { return ['success' => false, 'data' => $dbHandle->response]; } } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return ['success' => false, 'data' => sprintf('Error: %s', $e->getMessage())]; } $text = sprintf("Connection Success: %s\nServer Info: %s\n", $dbHandle->clientInfo(), $dbHandle->serverInfo()); $dbHandle->close(); return ['success' => true, 'data' => $text]; } public function isDbInstalled(): bool { $dbHandle = $this->dbHandle(); if ($dbHandle->connect() === false) { return false; } $dbPrefix = $dbHandle->dbPrefix; $result = $dbHandle->query('SHOW TABLES LIKE "' . $dbPrefix . '%"'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return false; } $wpTables = [ $dbPrefix . 'commentmeta' => 1, $dbPrefix . 'comments' => 1, $dbPrefix . 'links' => 1, $dbPrefix . 'options' => 1, $dbPrefix . 'postmeta' => 1, $dbPrefix . 'posts' => 1, $dbPrefix . 'term_relationships' => 1, $dbPrefix . 'term_taxonomy' => 1, $dbPrefix . 'termmeta' => 1, $dbPrefix . 'terms' => 1, $dbPrefix . 'usermeta' => 1, $dbPrefix . 'users' => 1, ]; $tableTotal = (int)$result->num_rows; $tableFound = 0; while ($row = $result->fetch_row()) { if (isset($wpTables[$row[0]])) { $tableFound++; } } if ($tableFound !== count($wpTables)) { return false; } $result = $dbHandle->query('SELECT ID from `' . $dbPrefix . 'users` LIMIT 1'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return false; } $result = $dbHandle->query('SELECT option_id from `' . $dbPrefix . 'options` LIMIT 1'); if (! ($result instanceof \mysqli_result) || (int)$result->num_rows === 0) { return false; } return true; } public function getDbConfig() { $this->maybeCreateDbConfig(); try { $dbData = include $this->dbConfigFile; } catch (\Throwable $e) { $this->kernel->log($e, __METHOD__); return false; } return $dbData; } private function maybeCreateDbConfig(): bool { clearstatcache(); if (!file_exists($this->wpConfigFile) || (file_exists($this->dbConfigFile) && filemtime($this->dbConfigFile) > filemtime($this->wpConfigFile))) { return false; } $config = $this->getConfig(true); if (empty($config) || !is_array($config)) { return false; } $config = (object)$config; $this->meta->dataPost['db-data'] = [ 'dbname' => $config->dbname, 'dbuser' => $config->dbuser, 'dbpass' => $config->dbpass, 'dbhost' => $config->dbhost, 'dbport' => $config->dbport, 'dbssl' => $config->dbssl, 'dbprefix' => $config->dbprefix, 'dbcharset' => $config->dbcharset, 'dbcollate' => $config->dbcollate, ]; if ($this->saveDbConfig()['success'] === false) { return false; } return true; } public function saveDbConfig(): array { if (empty($this->meta->dataPost['db-data'])) { return ['success' => false, 'data' => 'Please enter database setting!']; } $dbData = []; foreach ($this->meta->dataPost['db-data'] as $key => $value) { if ($key === 'dbpass') { $value = htmlspecialchars_decode($value); } $dbData[$key] = $value; } $errorData = []; foreach (['dbhost','dbname', 'dbuser', 'dbpass', 'dbprefix', 'dbport', 'dbssl', 'dbipv6', 'dbcharset', 'dbcollate'] as $key) { if (empty($dbData[$key])) { switch ($key) { case 'dbhost': $dbData[$key] = 'localhost'; break; case 'dbname': $errorData[$key] = 'Please enter Database Name!'; break; case 'dbuser': $errorData[$key] = 'Please enter Database User!'; break; case 'dbpass': $errorData[$key] = 'Please enter Database Password!'; break; case 'dbprefix': $dbData[$key] = 'wp_'; break; case 'dbssl': $dbData[$key] = 0; break; case 'dbipv6': $dbData[$key] = 0; break; case 'dbport': $dbData[$key] = null; break; case 'dbcharset': $dbData[$key] = 'utf8'; break; case 'dbcollate': $dbData[$key] = ''; break; } } } if (!empty($errorData)) { return ['success' => false, 'data' => implode("\n", $errorData)]; } $this->useHandle->file->opcacheFlush($this->dbConfigFile); $hostData = $this->parseDbHost($dbData['dbhost']); if ($hostData) { list( $host, $port, $socket, $isIPv6 ) = $hostData; $dbData['dbipv6'] = $isIPv6 ? 1 : 0; } $code = '<?php return ' . var_export($dbData, true) . ';'; if (!file_put_contents($this->dbConfigFile, $code, LOCK_EX)) { return ['success' => false, 'data' => 'Failed to save database setting']; } return $this->isDbConnect(); } public function resetDbConfig(): bool { if ($this->kernel->unlink($this->dbConfigFile)) { if ($this->isWpIndex()) { rename($this->meta->rootPath . '/index.php', $this->meta->rootPath . '/index-wp.php'); } return true; } return false; } private function writeWpConfig(): array { if (($dbData = $this->getDbConfig()) === false) { return ['success' => false, 'data' => 'Failed to get Database configuration']; } $dbData = (object)$dbData; $dbHost = $dbData->dbhost . ( !empty($dbData->dbport) ? ':' . $dbData->dbport : ''); $dbPass = addslashes($dbData->dbpass); $code = '<?php ' . PHP_EOL; $code .= '// Generated by WP Staging Restore: ' . date('M j, Y H:i:s') . ' UTC' . PHP_EOL; $code .= "define('WP_CACHE', false);" . PHP_EOL; $code .= "define('WP_REDIS_DISABLED', true);" . PHP_EOL; foreach ( [ 'DB_NAME' => $dbData->dbname, 'DB_USER' => $dbData->dbuser, 'DB_PASSWORD' => $dbPass , 'DB_HOST' => $dbHost, 'DB_CHARSET' => 'utf8', 'DB_COLLATE' => '', 'AUTH_KEY' => $this->generateSaltKey(64, true, true), 'SECURE_AUTH_KEY' => $this->generateSaltKey(64, true, true), 'LOGGED_IN_KEY' => $this->generateSaltKey(64, true, true), 'NONCE_KEY' => $this->generateSaltKey(64, true, true), 'AUTH_SALT' => $this->generateSaltKey(64, true, true), 'SECURE_AUTH_SALT' => $this->generateSaltKey(64, true, true), 'LOGGED_IN_SALT' => $this->generateSaltKey(64, true, true), 'NONCE_SALT' => $this->generateSaltKey(64, true, true) ] as $name => $value ) { $code .= "define('" . $name . "', '" . $value . "');" . PHP_EOL; } if ($dbData->dbssl) { $code .= "define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);" . PHP_EOL; } $code .= "\$table_prefix = '" . $dbData->dbprefix . "';" . PHP_EOL; $code .= "define('WP_DEBUG', false);" . PHP_EOL; $code .= "if (!defined('WP_DISABLE_FATAL_ERROR_HANDLER')) { define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ); }" . PHP_EOL; $code .= "if (!defined('WP_HTTP_BLOCK_EXTERNAL')) { define( 'WP_HTTP_BLOCK_EXTERNAL', false ); }" . PHP_EOL; $code .= "if (!defined('WP_ACCESSIBLE_HOSTS')) { define( 'WP_ACCESSIBLE_HOSTS', 'analytics.local, analytics.wp-staging.com' ); }" . PHP_EOL; $code .= "if (!defined('ABSPATH')) { define( 'ABSPATH', __DIR__ . '/' ); }" . PHP_EOL; $code .= "require_once ABSPATH . 'wp-settings.php';" . PHP_EOL; if (!file_put_contents($this->wpConfigFile, $code, LOCK_EX)) { return ['success' => false, 'data' => 'Failed to create wp-config.php']; } $this->kernel->chmod($this->wpConfigFile, false, __LINE__); return ['success' => true, 'data' => 'Creating wp-config.php succesful']; } public function installSite(): array { if (empty($this->meta->dataPost['site-data'])) { return ['success' => false, 'data' => 'Please enter Site setting!']; } $siteData = []; foreach ($this->meta->dataPost['site-data'] as $key => $value) { if ($key === 'sitepass') { $value = htmlspecialchars_decode($value); } $siteData[$key] = $value; } $errorData = []; foreach (['sitetitle','siteuser', 'siteemail', 'sitepass'] as $key) { if (empty($siteData[$key])) { switch ($key) { case 'sitetitle': $errorData[$key] = 'Please enter Site Title!'; break; case 'siteuser': $errorData[$key] = 'Please enter Admin User!'; break; case 'siteemail': $errorData[$key] = 'Please enter Admin Email!'; break; case 'sitepass': $errorData[$key] = 'Please enter Admin Password!'; break; } } else { switch ($key) { case 'siteemail': if (!filter_var($siteData[$key], FILTER_VALIDATE_EMAIL)) { $errorData[$key] = sprintf('Invalid email address %s!', $siteData[$key]); } break; } } } if (!empty($errorData)) { return ['success' => false, 'data' => implode("\n", $errorData)]; } $isWriteWpconfig = $this->writeWpConfig(); if ($isWriteWpconfig['success'] === false) { return $isWriteWpconfig; } $isUserExists = false; try { global $wpdb; define('WP_INSTALLING', true); require_once __DIR__ . '/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; require_once ABSPATH . WPINC . '/class-wpdb.php'; $appFile = $this->meta->appFile; $siteUrl = str_replace('/' . $appFile, '/', wp_guess_url()); define('WP_SITEURL', $siteUrl); $wpdb->suppress_errors(true); $isUserExists = username_exists($siteData['siteuser']); $wpdb->suppress_errors(false); ignore_user_abort(true); wp_install($siteData['sitetitle'], $siteData['siteuser'], $siteData['siteemail'], false, null, $siteData['sitepass']); $isInstallComplete = $this->installComplete(); if ($isInstallComplete['success'] === false) { return $isInstallComplete; } } catch (\Throwable $e) { return ['success' => false, 'data' => $e->getMessage(), 'saveLog' => $e, 'saveLogId' => __METHOD__]; } $text = 'WordPress installation was successful'; if ($isUserExists !== false) { $text .= "\nUser already exists. Password inherited."; return ['success' => true, 'data' => $text, 'isprompt' => 1, 'saveLog' => str_replace("\n", ". ", $text)]; } return ['success' => true, 'data' => $text, 'saveLog' => true, 'saveLogId' => __METHOD__]; } public function installComplete(): array { $isWriteWpconfig = $this->writeWpConfig(); if ($isWriteWpconfig['success'] === false) { return $isWriteWpconfig; } $rootPath = $this->meta->rootPath; $this->useHandle->file->opcacheFlush($rootPath . '/index.php'); if (file_exists($rootPath . '/index-wp.php') && !rename($rootPath . '/index-wp.php', $rootPath . '/index.php')) { return ['success' => false, 'data' => 'Failed to complete WordPress installation', 'saveLog' => true, 'saveLogId' => __METHOD__]; } if (!$this->isWpIndex()) { return ['success' => false, 'data' => 'Something went wrong, missing index.php']; } return ['success' => true, 'data' => 'WordPress installation was successful', 'saveLog' => true, 'saveLogId' => __METHOD__]; } private function locateWpConfigFile() { $upperPath = dirname($this->meta->rootPath); if (file_exists($upperPath . '/wp-config.php') && !file_exists($upperPath . '/wp-settings.php')) { return $upperPath . '/wp-config.php'; } return $this->meta->rootPath . '/wp-config.php'; } private function parseWpConfigForDb() { if (!file_exists($this->wpConfigFile)) { return false; } $content = file_get_contents($this->wpConfigFile, false, null, 0, 8 * 1024); if (empty($content) || strpos($content, 'DB_') === false) { return false; } $pattern = 'define\(\s?(\'|")(DB_(HOST|NAME|USER|PASSWORD))(\'|")\s?,\s?(\'|")(.*?)(\'|")\s?\)\;'; $pattern .= '|define\(\s?(\'|")(MYSQL_CLIENT_FLAGS)(\'|")\s?,\s?(.*?)\s?\)\;'; $pattern .= '|\$(table_prefix)\s?=\s?(\'|")(.*?)(\'|")\;'; if (!preg_match_all('@' . $pattern . '@m', $content, $matches, PREG_SET_ORDER)) { return false; } $dbData = [ 'dbhost' => '', 'dbname' => '', 'dbuser' => '', 'dbpass' => '', 'dbprefix' => 'wp_', 'dbssl' => 0, ]; foreach ($matches as $match) { switch ($match[2]) { case 'DB_HOST': list( $host, $port, $socket, $isIPv6 ) = $this->parseDbHost($match[6]); $dbData['dbhost'] = $host; $dbData['dbport'] = $port; break; case 'DB_NAME': $dbData['dbname'] = $match[6]; break; case 'DB_USER': $dbData['dbuser'] = $match[6]; break; case 'DB_PASSWORD': $dbData['dbpass'] = $match[6]; break; } if (isset($match[9]) && isset($match[11]) && $match[9] === 'MYSQL_CLIENT_FLAGS' && strpos($match[11], 'MYSQLI_CLIENT_SSL') !== false) { $dbData['dbssl'] = 1; } if (isset($match[12]) && !empty($match[14]) && $match[12] === 'table_prefix') { $dbData['dbprefix'] = $match[14]; } } return $dbData; } public function getWpCoreFiles(): array { return [ "index.php", "license.txt", "readme.html", "wp-activate.php", "wp-admin", "wp-blog-header.php", "wp-comments-post.php", "wp-config.php", "wp-config-sample.php", "wp-content", "wp-cron.php", "wp-includes", "wp-links-opml.php", "wp-load.php", "wp-login.php", "wp-mail.php", "wp-settings.php", "wp-signup.php", "wp-trackback.php", "xmlrpc.php", ]; } }
}
namespace {
    if (!getenv('wpstg-restorer-as-library')) {
        (new \WPStagingRestorer())->run();
    }
}
