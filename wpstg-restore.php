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
        private function printCss() {echo $this->LZWDecompress("#;�gC8��e����!i��d0��CLD�g=�M&sp��t2�Nc��� 9�F��r7�M�A���+��иj)�ga��:3��٠�4C��J;5`f�1�Zc7���)-�SJ3T�����4�i����;:��Y��d:*uZ��=3���S��B/0�l>\"-1\r��ܮ[/���F��s;�ڑp�b��Ñ�)Z���H�S)4���c:\\M�Cy��m7ͦ���k���h6n�]n������h���Sn�-����`�: �3,��C���A�F3A��̰��h�0��Hأ�#�*6�@�6�+�1�!`�0�ØZ9�j�EcH�2�\r��ǿL�ٲ��0�%�\0\\�,I8X�?,��?̼ ��xޔH�� ��rc-\0H\\�6͘����j\n�������D�?��HSd9pzN��:��v�M1↖:�x\\��\n\\\":6�L9Mc��86q\$Y�2�v�I���!�:�%ɂd�\r��A�IDV��tHuE�Cc�2��r��!piNM�]A9I��\$�\0�6Loe�\n�d��tscG�t�!JIB3]#��@�\$�f��*���jb�����J��+2�i=�ħ5O��2L�j�8�m�6<}d�C�4�(�.2��BJU�u^�U-�{AS�6-���nj�c�1�co�S��\r-��;C���\r�9Wee[6�y�p:A�6�:�Yl���D_=�p�.�\r.�Ec����t7\rbjX)�W8����3��(@*�!`�7�xX'��Γ��P9���\$���`���?C�D4ā\0�2��,M��qn�=B+|g������j��0�N���=���0]��}̵�p�Z��4#���գ�j8arVn�R�%�9c@��AU䚘Ff�(j{4����w���j*��`�ݐg<�i?����Toܰ����9�UD����F7L���3����1 �38�\ns{�M�@��h�b|�M���a%��n�� j\$\rĕhC6Ea��}��O��T��a�Q>H\"�_D6`�@r\rC(}@����TChl@��6,Q�:)u(iգ\\�J�Wj�>���\r��5��2�Y����(���RZ(:,L7�2C�|A���(�C���=�H���*��F�'5�`b�\$i�ݣ��+ �]�6>���܊����Hu�܎���\0�L`l�@�I�g5eRa�Ά���c)!�\0>����0v#�w���1]��x�A�\0��pbh��S�	\0xs\$0���u�r���pe!�����pl�{�Fjf=f�;� k;f�>�@�rK\r\0&e�I	!��d����j�)����9��X�]yT*�|�P�Z&+�/�!3T�Ȉ̹#��0���\\�,�px�R�=^E�uC+El���S�]��H��k!�g��&���d�.�܃�M�9wN��f	AI;v����`��*�-K��P#�uv32�0d|��y���8�x���KY���l��xy6�f��rЌ�,�;��B�\0s�@��W@�C2\r�)��Po%�*`��Yɐ�.��G��%��7'a�c����#f��Ew���j���-��̒{yӲb�Du讂�ץ�O_�5��d�U�df�1�@`%��8,����VYcOM^M���)&�Ҏ�;[c}Ð �!�@���<Z��v���pp��+�e|/W@�]�������ɔo.�V�mw/�1Ř�&h���7���|v�ʖ>\$��b����~+ ��U���\n�d�_�\\��3T*7���\\ك�-|���F�)��U�q�UA`M,\$�7�x���2��������.�=�2X����ٟ<e,�s�(��]j:L�0yS�-I�T��LԄ�S=F������[4eVu������Z�]p5�~��ڣP�W�5nV���C�=h����h�y<S�/a�s�ؘq��籲Xn�N�7<��ˬ�	�o�N�:�eI�����ų��6Dՙg#͠����DĩM����\0:Aqk��Ee�sc�E�Z:`���n��A�JinT�՗�I@��g����E�N-=�n�{\r�r��8�C�F�~j�/�w9〃mˆL�xd����>�h��(0҃�x�^�麟��m����W�c>��֘�\\���-\r���=��^��Y����R\$P�*V��g�']�\"���t��p[1�.�?M�پ~�ʞ'��ʬͣ���R���⻃:��ʹ���]%{���`��2�\\�(�%���n^t���c2ϖ&���0�`��|�R����m�zzØs����;���o��!v�CH�Ai^��j�ele�`p`p�r�l�Cn\$f~���h���8��j����O�� ��c���t(JV)Jd���/�����N]�R.��S��\r����D��dN����� q�� �Nj�jeZ�I�h��&'��k����D\"N\$kBүhP0/�w F�O�O����QOPnX�����g �j&��|����\rx��\r�U\r�h*�+��v@t7�J|G����P�v��v�`h1�X�(�b�0c(���0:�&wo�)��u�§��)��A��k� �k�֧�R(��l�n��mìn&���n�oF�o�\0pGJgqq�E'\"r`�r�.\$�4�\$:C�B�Dt�L�\$TE���2�)���@�'j[t�k�x��+H<�=E�=�>g�鍋\r�̨�)6+J5\nev��Y�R%	\n�h�\r�E�\r A!�X���1%]!��P�@�u	��l����/E�nb���\"��)%��%��e���RTc҈�9�����Hp��M\$H�*/)�U\nچ�.�4\"�,2�9B�3DY*�Vd���f`m,�*H�-�i+�ܭ�.\"D��-(�cD?/��. q>�2�\$�Ԋd?	U/���l\0�1���S#/:%�B\r���hD�̉r���	4b\$�-,��@f�p�h32H��4�@p�\0�s	d	�h���#�=p�VR�i%(sZ|^8��(�8Q.(4@��?q5�1o[3�	l`���+J�@J̪���/2���\0p\n�˃\"�1��k�+P���s�]o�>Ф��i�k5?	�9�80�#�,p�s�\"3�&�%:0�'ө@�(KE�SLK��C��CӁ��%%rE�Fb�%��������N�H�tjt\"%4&Ɏ���=�V�\0bɑ\r/�H��\rt�C���ЊE4G	4�9rgE�ET-\"t[�:�c:�fQ�/J,K��\$D�!@ډ��#k�\\�F b��]����(��(8�@�:+w�;\r�P�(���Q%ʿ�F���IƐ�L�S��Es�U�2�(Rub��%���ܽ�^xo�Z�V�u �?OiW����\r��1��P՛Y������STUr[���l���t�<BLqS��;���?X�X�T\r�`�D�� �f��+t׭�M�̵�_�_U�U�m����>�-�A_\"6�3�KK��6�Y^u�����nv�䌶Lɶ 6Dw0�8��\0PQ�K��c�\0��BGk�C�:�Dv�,�ND�p�CrEgbg�v��}X�]�f�<�p��B^�R9�N�P\r@l�l��!)Tj�\$��Bi�]Q�w/��o�}��<�&���Gșt�ҵ�cRހ� ��S�̶�Z�(H�t�Eq6�qn`)N�[o?r艷k���т�\"��NѶ�M\"�b�b�@��l:��B.����sw()H+�ނ\n�\0j�N�B#d��9O 1\0��wv�G�lo�Gd��bd,WBT�\$5RER0\"_�(��+l F���%\\��RÍ?W�&\0�uM����wc�nk[��x�6�0�h��K��z�t(���#�r<�-v�f��Z򗭁G�B8J��W��)����PK4�\\��S�SC�AÂ�@h�e�\"[z�ʪ���\\׸_&���+��/~�h�\\8�b*l�+��huTXh\rxl `}��KU�mS�=�e:U�/�\r���[�+�xp-�eT�����]BS�_���W�4C�9�Z^l�kOm���V+B�m����8��Xs�,\\ע��JQ�b�h�ʗf��������-���ё�Z�&��@́ɹ�V��a��Ge�W0���w�F:O��<b;���O9x4��k�`� ڱ�n P��Y���'�S���ya��\\i�\\�y�����Ut-��J�WPҷ��ch���bu�(�\rE�B��?��d�ڛ��x9��6^\r��9n=h/B�@�*�f::Y���\0ܠ@�ٰ7��])V\0AY�XՓg5��+r~�f`�9��xF���)QWm�p>yz����Q�w\r3�Si�AP�DY�H�	��\\\r:*��~Ww�aZ|�FZK�n���VWI�h��d�\n@_�zh����-������WN��G�}xxn�����m�څ�\0Ũ�/�\n�����׌�7�Wv	����Cj9��x�8����YS�(-z=�8�p�9T�6��E��n�d���)n�5���rJHC�A��uP�w�R\0�}i�}��B岸��C�����ߵ����� ]�`�T�)�����k��ƻ{����~W�Q�o41����{a�۞\r�iWYcW���ړ\\��B9n*Ys���Ɫ�F��Ĺ��ِ'��y�'�Ϲ�:�;׽������/��%��y�ry��S��V`x��7Z����b̛C�{��ۡY�\$ܼ'�{�]-zZ�g�fE�����ݚ�˪�?���:��{]��HHS|#��b���ܙ�|��ܣY�Ov<[TO�{h�ۓ|��}�ܶb�Qԕlԃ�9)]yN�˗���XÐ�����;e����U��R�\";[&������=δY��i�|��\\�K�F{N�6��|�4\\a��H�̛�w����X����X�ю�v�X���4��:��S�l���P�.N���t�h0��M��|OY`}���C��I	�Y��4�Ԕ�<A��i9�)�_M�yO��t�d鑼�p�����=EL�����ܔ����j3���;��\"��]<n6�=�ޝJ77�e�Ͻ+�]�[�G�b���O��Sл!�c�����i������9V��5�>%Ž>�՛��A�#�i���\"0�\0c\"��l�I8�F/ހU�{��g��px��fV���K���Ǽ�2�bi�^l,=�}���o���\\�e�/Ҟa�\$�c֘�1��{�'��S��k�-ֺ\nÃ0DL@�^��^�ׯae9�p��Ϟ�Ŝ�����Q,�.yJ��O��K����u�+�D�����7�?=�_C��{�����1��_����?2=��LO���i�xQ��e�o�o5���+��=�|��?v@U��q��+瞋����I;�?��0�W�/5���r6u�\\���a���}�E��צ<_��Y+jC�x9�ӯ�1H�_(���?Y�I#Ij�R`����9E\$�+2N���<!aF\n2\$��`��M�o{���'ؾ寅0�i_M�����N�0#��H��ϛf�m�\nNB�B�3\0�bՁ{��b������`m}k�^�Ǽ@�k��p��\rr��<r39���7�:���3X��V4x�9�Aċ�4�;�R�H��o�rxhT��r���H��\$��I��\n��T\0����Z��2�{\0���J��`�S6��p4_\"���\"���|(\0�@�a���C��fI>�\0A�`��a.R�6���&�5)�\rRwh[B�`И��b��D �r�R� ��b�����,�¢�1���:�p��`��\n��Cņ<4��m�ߢ�O\nRL2͕�UC>���3�Ь�vyII�J�%Ԛ�2[)A�H���&s��uE�_��׊}S�\n1K�\\��!n�Hr\n�00��\n\0dX�`Æp�I���2�zE�Lk��*������<=�;�e�F��LD��`D���H@��w^�h��f��!�iĎ�4}tM�P@\0�U�;hbA	haEH<�U�ɮ�U852�j�/�,LR/8C�N:[�GF��F(gn���RH�n(�o�R\r��{E51�:<���#7����x,c�V�XV��ׯ+8t��\rp��5p�I��1\n^�xM�q@P�@L\$�]	X?�8����&���R*Т`�n@A\ne�F�`h�i�\\�'1�6�.X�Σ��]+�prHI�M�X�Ŋ&�(f\\W�c��E�\$���w��3�E�<�㊴waܯX�Ò\$��lyL��9�'t��Sb��%��=F\n�ö����V�z s��T����J�2�B.����!�шrI����.\"�̀,|bf1��E�%1�yn#e �G�&�*�P˰�H�C2D��K*\"U��F'�x����(�E0��64bK�H��~;0�+��)���Gpђ�J#�v�!�a���iz\$�9��ER@�Ob����F��G�P�u\$���I�׍b��\0005�� f\r�>@�1��pa؊�V'��Bl`�L@\r`м�H/G�\0f�����Hf�x/�G�eu��<|�e�C��`:��8�\rpu�هXz1�4	�жGp%-�E�i�l�\0̠���.����+0���c�ǎ����������!�#�)�O�>�ڢ�\"@l\"�`�sC�7��D(F�c8�/�	�J%R������\n)a=�,itB�Yp�̲��@�.�<����pI�%��wE-j�Nj���HJIAEX����\n�'Rn	4����LAȋARY�E3S�K\0005(l�\0Y3�|5��Eh�P(Whɏ�*��AN�)\0L�f1E\n�!�\rE��*�����:1�1���q4���m����ߖ����x\r3�Y�-�ǔ��<T~#>-Y<�Upb��r�֨��X6@xT˦1���h���_��|�����|/S�����T���Ѭ���B}yѤZgS>�aCf�46�ig!N�\0�͜�o�\$Yώ�����yXPsf�֏��b�0��̄��.LNK%�\\������L� ��1L�g�9\$�NL������>�rc�\n8@w2`�̦}�:�������D���|�g�>��E�m+�4�g�?��������T�[\0	��n�Ⓦ~��}�");}
        private function printJs() {echo $this->LZWDecompress("\n������o7\n�à��\":�̢���i���b#y��e��G�C���o3\r��!��e	����E\"�I��d��.2����?��e�\n�=��\r�3���n:ΐ�`�`)@��hD*i3\n5�V�\r:F��e��NW3��Dj(�L�#Ȁ�e8�M8s��� ;�M�+�F�T��j��R;Ý�#p��e�GZ}M�d2��FS �C#���9����j�������M0�D>#���#���� 7�G����='� ��9��P��\r�)<�,0��\"㙲13�~fca��㬈K�дm+����p�0��b��@J�=��#���hD�#��6!C�5�X9�I����;䟲M����pz=��Hz7�P��2C8X�Gap�0�bx�7=zL��!`��Q����t�0�PT<(�\n��|o��\"��o4\nMcrn�'IJ`�\rʨ���ӣt'�rX2���c8 nB>���`:�lL<�TL	­,��󻴑'��a�3Kr�!��`t�D��W��42֕��������)R�:F�s��A`�)�Dl7����L;�2��{8��C�Ƌ�	�>�|z������ P�A\0����-�B�����:H�H&7���8�w�!Jcs\"W�\0��T��e3���2�# ]�̘�ፃ%�j��'Pas���.F4�8�`���Sce@��XDI�ě'\r�h��9ѳ�~7cL������&�&�@�;L�A�\\���E2��x�*	�`H�c�8�hu2�/���\$4��]`�p��t�+]��K�Ex��w 3�1�{��\rv7#�̘�1P��ΰ�0�3�wh�	G�O#l�9jRR:&!�y�àZ��S`��Z��\0PM��0�ɍA�j��&.��\$aaOe�^���|CS��!��8=/[s�LK<�ad27:#�\nQ�vL�]��t�OPA/n���d\$ޞq�j�8��CR�\riɲ&xK��P�I��*�\0e���0��'r���ӄq!�]DȬ9��~Ck�J),c����VG�C'��_�}L�u����C�a&���L�o	�V�DM����;Ըn���B�]!�t��D�;�!Ɓɱu���]���D����c�}����G�ADd�@S��QF��őY\"Yc��F �Le3x�&�U�P��X*��8�q#�,���s\$삃h\nB���\n��)��>�2X%��r�;Ih�&&,Ǚ1�f���4欹3d游�0��F�����VV�9���vaM����1\r����N�p]��r\\�9�/����'�:/��ȳ�=QJ��tqC�NGΐ�TN��\$�\r�Fp���'��S\0ȥf\\�lO-�唔��=�����8=*�����܊-g�����o\r�0�&��Ls�g������ÝG����F�HS�;+E�R�d�gL���WP�!:'��χ0xA�s���r3�nB�K��n����_C��4���ZwA_�����D�I1&��*�Lmp&���9��d��a��@�VAqķα�0�~���Y���\\;�p�Z!�T����%�6�,�R���HGUP�Y�eMJ�&\r�\090�t�xi��Z�c&o5�PF����zA@{(,�޴S\0`� \"s<&���˪�R\r��jLs�݁@/��&�� Rc���+_'a.%̺�r�^�tlPI:��+km�u���=��j�(!-A���:��:���BTju��K�����]�u�	W'D�X��r�E�0TB\"��+�.�'e]��s��p��u�]V�i@�3U2+Ș3��:�)�\r���,P���X{PD\\<��bŋ�1H9��n;�����r�{�� ���h�n4-rUG�\"l�����.��ĵ�X}���\rV�,݌-:؜k��wVN���@�`ʉ��/�b#f��ժ:z��B�mLZCIg- ��]`�wj�;��n��O��td\"�M�\n����v�睸�(VA�alB&�#]:�k��\n�>����C��M�Xe��3PDtmEC��ڪj)�P�q�f��\$���D�RZ�)�2�P�\r�E�:�(#Tr��۲����~���K�x��,��D*{l��7���el��P�\\�w!@��p�֌�oZ&�Tն�T�o�`�6.r+V�n�OA�v��ۉ����\\t���=h'Wn�T*���9X�<x�#������L�4\$��;�Ds���ҵJz�\"Ըu�i��SX�Biک��6�0�	�HW���9q��ҴsbY+-)�T�ʵ?2��cW���ޱv9f�o���oX�D0�xӺA��غ�7��Q��#����گ�^��B�Γ(.��J�`�C�D%��찫��\n`�vʶ��fj�)��p�?�Rp6�0:RV�mzK�ӂ�J���J	��0`@K�\0���@��@��D��(L���@/B��+�D>�P[�@3ɔ����@�L�	���pD|��|��\"GԚ����LE(�I�X6ؤ��cp�c�;P���\0d���P�\r��̖�bGf�p��\0D�<��`�\0P�t�R��M{�u���R@S&��@Hm`D�<f�h�2DP��z��Rx(�eB�e�`�>u���Op�KXG��P�rZ,�C\r��g�h�+�k�Q�ΆT�	B\n\0�g9\"b lѲe#�����&50�:��m�D�`�� �`o���Z��� \0Ip��������Z�:Xx���\0~Q�u�{#���D6��>���j��LC�M�6��m\\����&�\nf�%����[�:���bj(�q\n��@�hC<e��C�ac��l@�;@����`v -<�H�!`���\n�\n�}+�ȧ���������/��HsEjE\0�}G���L��0S\0\rL�}��|j���L�`D���6�#h�\$%�3\$�\"��`bP-��4SI*���d.m��`y �6� �%�����`Z�����I��\r�\\ �>Ȅ�*��Z¤\r�A9�1C7%�9cj!��Dp����� Z��j������;�8����;�#\"r!q��xm`�l�� W@DB��f�^�1=�D@T� ��~�O@�SA0�-D��NױL��T?1;D@�>1:TL �Q+���3@�U��?&�A0�3Ӹ�,=H��E�`�K�SEkEtCDtcFtj���E��H�S?\nk`V���MTtmd��Є��(@Q@Ƅ��\\L�)M�NQA�T��4h@U\n��B�OqA��WB��@�wCp�\rM���}\n��R�Nd�5AN/�HqVL��F���uOUMO��t�Ap�`� �\nucugQ4�V�=\n�� �W�X5QX�RM{\n��\n��\nU�SOO��L��\n`�\n��	��T5���`�	 �VeS5PE�:V6@�\"��I\n*+\0����`!�Z��I7\"P��A�YA4x!FbZ	��Ds�^��šRu#bt�����a��MVC��\n���u�Y��l�d��.�hm�+�eu�\\�7Q�_U��V7��1\0�s�_\"���^��a�aT7Eq� k�b�1@�IA�� ��Ofo��m��\$Ej+3bj��eJ�b�ԎF�Y`�m\0� v���mAVӽ\"@�d<�Aq�p��&���m��h ^��T�{;��<3�<��=3�Qs�DE>3�>������)e/#1n:�\0�%�L>�t Z�j�f�H����p�\0.`�dc�&&��yw�l,�x��y2_y���| `�z@r�Y{�d`LZ\0j�g�F\nW\\�@��X	�z	@P�if+�d*3k6�h&'�+��9fJb6�`�1f�VPf�.�B^<�&P�\"��`�c�<����N�k�AV��O\\f�Ʉ�PJK=�hÅXX�(j���72�?��Br�i6l{X~+�H�b��ǘ���L>BA��+��#R�o���z��bx��D�L��8 ���0q�-�Y@.z�\r�W\0r��Z\$F���nk�P��\$@�& r�����N��+�~#PD��Z2�Zm�'@c�GQ0��)�@�f\\�t��-ze����l�K�~_��[`� �d��a���\"�������l�>jᭊ\\��1��<�͕�ߕ�c\0�	1�g���ظ*n޹��x�mD�Ό�@˔�Ӕ\0d׹w��>�DI������ �{.\$a�~\\��\0f׮������^i`y��-�x��՝�����Y���j\0Р�{FE��bc� ����`�\$x�!m��\0��¶!��\r�Q��~�z	Z!�bNׁ�zPj�h�Ō�L)s�\$��Q�fb`�b�rcj��Q(d\$ҏ3�7��R>��a&-���E\0����|99��^�ha`ް`R\r��Z�#`F�@�h�>C\"L3�*`��Z�3�>�Ծ\"�S�	�4�\r���8�\\~�	�r�@�k\0Ǟ�<Cxv9��X�\"f�\$K�2��z\nn�e��f&b�/���X�oc���*�0f,��c\n��;Ǐ���^�HX.\$��d��\n`R��K������3) P�>��¶#��ۗFDw�ł�t��<\npD�خ�`@�ۀz\n��c�!�B�6M��3�����xr�7 c�\0ݴ@���x�L��q��[�bffj!��g�����{���)�-�\"r`�m���z~bq�{��<ow�7�yVg��3ţČ�n�a��=���Ȉ\0��'��e2N�\r�LP��<͘�sm76?��\r��#���|�c�#a�<��Q�p|rK���6�ܶ��͜�'� �μ��ȵ�~& �,���|�͢ph����(����܇���\n�	���o\0ޗĦo���B��B�`�D[��Ĥ�[��w�^^�س�lO��Q�։�]l���Dm�K6P�^6#f6��wG��������'��Ò�κ�1�������h�u+oHt�e��U.�u�\0�Ge�݂ߡ@XT�B�\0���*}��\0�����74���@�p���ŲH�Z>\r�����~\$�`Y+cn\n�P];�63F\r��\nÖ�@്,c�x�\"�	���5Ӡ\r�:m`�1Z�������G��f@�<,d��*\\���+��ي��������ݡ`�ޛ/���)|�b�3����|B�̚�|KPi�c���Jr���\0��b���h�����`�	���\n�T0�?�,�r��\"�D���G����1NL���|�-�>�+���e���_}�9��'��.a`�u�4���յz������G��Y�Dc��[��\"'����\n�wz�>�&%���O�}��a�_r.�\n� t;��,\0Q��Y�_�o\"'�-*�Q����پ_�6=�Q����}�\\��Ƥ?�Pm�jj�L�h�\"���&~0dߐ�O�M���\r����0fu�Q���v�]�|}@��i���1��2�K�\\���M\0@�DG�F	g>��S@��L�z��B�nU�D\\����M��.��\0000.��`d�/�T?@E2�D�4c��<��b�����6w3��AZ���㠈%�O|l����*�W�u~PZ`'(\n�&�mvs@��'i\0�ZdU�#֡�h8\\��#|Z��:o�y�1p@�v��-�@<{.@N`�)�� �l@zxU�=͠'��m\0N�\0�0�[pAG�SxS�� \"G�|�b�h`B���=ظ]��HC�/p�ap�h*u�0s|�F����Ct6�Kz��Z.�q@�D�\rĳb�M��7д_ ?\r�CBH�TM]r<�-������5���p�0S����#p\n�&�X�Cq��}�A\0S.r��=6��H\"@�B&�;��oÉ�P0֬I�MKb+v�؂=dP�lL`Zշ�Y�DB�&FBG��vI���X��6N�:�T?��a�Dh�Ws�vF�Q2D����o������Z���R��#/��Q;aQ��Hn�q��;+�Kb��@��Z��ާ�}C�P�QJ�_� 1����DF��t*�cƮ(��1���H/�[g�b�><�r#!�{=��MJy���H�v�V؎�\0���`ccl���py��D�Kd#c��r7�(\r@H�i�ڜ�!���l���ۊ\\!�i��~� \"�Ӌ�E�uc{�J9�\n\0쀨���+\"J�LY��\$j��+d�:xCW�}��Ilh��}�tg��?I�����\$�A�l�L�~���@4_<c���*��&|fs�^3��\r�'U!\0�dZ�H��O���	\n.Gk��t�o����a�h�M)G{b\"��;��B�{q�P�=��.�w{!**������\r����\"4�p�͟�,D���9�)Tdb��j��y��	�|IBw0�|&29E��Y���7��\"�ri|�d��-I�O@&v�<B����O��K�=���}-�5��Leé�Dbr� K�)���i��4�WE�^����ԣ{/�|��_`��\$�J����X���)�f+��^�� Q�x��/ 7C gp@�A@1��@��OF\$� `i�\"�K�hDa�7	�\r�{v�8���dw��/\"=/��LO\n�W��/����L�v۫\$��]\"�>��Y��\"�k`��p#Sg��5#{��������\\r�9,�>IR~��`.�`��RL���d'Gx��Ηim�Am�n�T@tQZŐZ�P�B��#�F`: |%Fa^�\"D�*@a�����J�/et')08���1٠̾��0*li`p��4I��|��v8D@�Ԑ��ld��0��7	�0,�Sux����b��4AAxU��Sݔ.������\")�m�\"�,�>N��\rh��5ч�,a��~ k��8�k9�\0�[�\\,�H��\0��WB��`��:y�Se��>/��#ӕ����j2�&s�|����Ti�΄�Gɝ�\0�:C�x�Z&��d��-ys��o'�k��Β{e���K<�ғ^}�}��=b�O`��V���Wl��-`[���Z�A��*�W[Khkѩ��hs���8�:�ΐi�D���ZG'������L��bR8'���c�MVCS�к��*2��r��PxG��%h���DI�52��x`�u����*o���'�*��@��Ɍ��9ȹ%��0\"��{Et��(S��9��.�v�d�Pr�QR�A��lP1	�5�<U4��Ӈ@�����D�b�Vd_�#�>�	cqT�Xհ1��C[|���a���d��@�ٝ) ���d :p�G]+�EQ���	�+´nѢ�ޖ�t-`F���@?.`	�bl��=�\0(\n��*a�xc������n�����学!���ѫ��T���6�M0�K|A/�m����;ajS&�T�Q�9�RoY\0� YMf&�8���N�]d�KyOZ�f.��S�-`ǩ�=���_娨rI��ݑ�l�����H�04�v@g����I���B�>��~~��0�v-`R6�\0:M�6�'	S����m�*�`lӯ�%�TW����{����i�*��&�;:`�(��g1'V�L\r�[��E��.��A`i�����)�\0E	�⼇\\ч*�P�8���9�A��E\0[���V�o������zHw@��\0�(�\"�?(Z�ޢ�V]B8h��5��l�ò)l'&eY3�Y��i�	d(2����[t�\n�X�Րk@(P��-����7X��%��ĭ�����Y�l��c[q\r~1�`�\0\"l�EoE��q ��m��:k��C^։A\"�(	�d�'�4�+�<�m�[����տL�k�pU��'�l�+��de��5�C0�d���a��-gk>���u�\n���r��\0�������\0� ԃ��I\0^�+�[��W	� Nc�.7<E\n������-�4j�d�Ƶ=Yd?1H\n,�,vY��[�-~�k�B1��\"�f.8�H`\"H��H;�@5��PT�ˉ(I�sxI�>YL�4|%�t�NW`�ﺌ!n�<��ϻU�4���\0=��ۃ�|�[�ܡ%�>Q5h�H{=\0����_�d�(���A�B��L} �\n�%I����0��\0��P1=!���:�0Ѵ���(�\"�����A=�Tj�0���\0[����h�c��ׇ�#���eZ0��XY2j5�mZ95\0�j��C:�/iѼI��5&�(\$�H��?�R~k��c�Qڞ���B�%�N�|�g&�.��J^s*j�(-4dLQW@�n��4o`A�=�wd����aC�i|��%�)���C�弧1dZ�R���R�@�@��+��\0�\0�h���n�t�j]�Yh9�����X0���`kzyS,���<UV�!TG2�H6Qi��w��<�DK5LjЩ��@&9��6�#	 ��U��E�r��j�.v��f鄙D�Z>x#��3ǜ�B f-\n�\0�ج>������FM5�����	h�F8���|&�	41(lW@t�r�	�W�T�����l]���6�s�L�8ԗD�3+N�\$5F�zx��@6����m�pw2046_�Lw�ky1v�瘽��g@���a�)�)k£����|�0���t��\0�z&*��v�۹�6:��7|��\\cc�m��Ak4��l�\n��T���]��n�������\n�i�I�/X�,�\\�4hn-�0\0.!C��\0��A�E��:K>��q5h�0\$k{8J��F�\\�\0t�J/@ܨ�aW�	�=gbr�����!��ׂ��\\*�!a��vP��ȶ=j��P3\\�2H��#J�{�<��C�9���y�� xJ�f�������x8���ۄg��`�jg�\rY�I�k\"��8M?�{Q����aS��¹�vO|*�el���w�H���\n,UE�J���H��@�(1Ѓ����k*�r��\\\n�e/��Q	�+\0004��<��M�,�c���ã��Yf����1�p��e�M��Ib���q�-xrm;P!\0 k%��M�/��m�c!�E[�Ui/fCG}�@g�TT���S��a�j�0C��S�B�p�ɲ�4�]�?m-�ֳ4훕������Z�\0���,�x�q�WT��-\rݧ�@Qn��*�!���=�Oj��1�\\ǭ٨pUz?�y���ɬ�`t��?@�+)��.���n�B�m���?\"&�q��L�|��l�T>^n>E�l�9W]�\$8VO�j�J���U�O���X-}nF�+QZ􀦣��k�,��> hzC�~�d�puڊ�d�P\"�`k���M��,&qz��qp�\0pΩ.�U��R��Œ�[і��/ZD�wP�^c�:}1fWH8��Nc���ʳ��cyu��H�9U�2l��i����_N�l�'�a#䷨�BC`�����Έ�'�Ee\0��63���%i�� 3Iz�t�Ni-����K�����}�\":�	h\0p�@yN��6�Xf�|-}U�95�ֹ��l.nd�����X�@���X/�b����P+\\�:�g��7�a�X����ŕ���~�Q������!4���n0���PtD<t�n��\0�^!�	���g�cܨ��R�;��\"��n��Zͷ�]>�u*ej1����=�H\\w��\0���ҥ��\n�Y��Ea��:։\r1Pb5�`n_�CcQZ�@ !�\0�L�.��@���ط�u9��\0`p*Jj�*s�l������mhQ��\" ,�=�8qf��zɬ;��.[��h�¼�<�.!{[���p8gA��\"ǈcR�=C\$\0yԘ�ĦT�?�	�v���F�#U'�*g˥\"���	��}վ��u�\r^��&4��M�\$��\0�&�M�5��V*��E���v`1��%S���k�b��:l[9̼B�j�(�����؅�m�Q�s\"Sn|ծ�3W�\0���ţ�Sa��Ӷ�\$�/��L%�0@�\"b�&Dm���m4�� �e��d7���-�j\0��8��\r�ʚ�/ajfX\$�?������MG�n!a�á�#��bW�F����^�|k��[e���Fog7iY�N�JӰ�srcْx\$��N���id�`+K@�Ø�#�/b[	(N��ZmC{�/=�z6�Dɷ���s(�E�M��!�\0��h�S�f�YǀT��h�����Sj�2k��#V�Ë���s�G��6ݸ�%	�Svߵ�7�+���2�� ?j1)��K���Z��0E������f&�h��N�����F�l�� %v�^vz�-�C��ղ�L�a������v�X�S0&V�>ړ'q��DjKIM������~�]��S�`X\r���:�ޘM�`.*X~c6{\\4S\0w	H�޷h��\r��9@�Z,�)��:����\0���`�E�'am�X�����k��\0�F�a�5`����>I�a�\$H�]2���Iz@jj�`�)��o��	�K\"@�����\rH�\0�q���?�e}uo�p\"� p�f�F��Cߙ�W��w���ٍL��ex7�I/��R��\rN�I8,��(t�w�1c8�BÀ�����R��e0��K�x(���~�Ȫ;ڱ���hB�,A�l�ykM&cE�;�@��E\0=d��(&8ͬ�)�''�9#Dm#��8�P A���^>1�&��U�+�X籀Ws7gBw�R����U�x�PeDJ_Ϙ�*��;ex�Qq�q����\n_�ė�e�b�����i�#��va�7m��P8^������If���غ(,]�\r�xέ�	a Q�����sA�y�ē�s3S̕��������[\n煽dѰ��[i�]i�m/zӶ���bv��4B�Y?E]��\\��!��^��&�\$�a�\\l�qz%��]�a�:_\$1р������j���#��~.B�`�Q��L�j�8�B����\$9K�L�S�8���95�Z��l(g�d0/����t^���{\r�8��F�z#�)x��Y��g`~������hzk����.���<�������i��AtkJ\"������!l��r�znם��!�j�Q`���~�P]��3�������!S�|4����Kk�����F�q0�v����\$�&\\�4|lE7�vg�F#�Z���8�9g̢��������\$�a���[6= x@DJ�!&#�}�1ʀ�},�������nǬ�������~,����0�CG}7�_8(�/s&؀-�T^�\r\0�ȡC��\0�\$B\"���h<^�D�uG�/��@�p >Y\n��,��*R�Ir�.��0�&2�����P_kG;���;�zF-������`�Ω �7y9�d����˲i~O4дwf_��Ѕ��(W�L��Z,���n�\"�C֠�T�}|f��{H�Ϳ5s�c�*x�^B�mF��d6���(�.�z�^\r�ٵ�F>��2f���N�\\iT^�.7��X��he4��=&l�:��v �F�I�r�~/����F�����І�?�L���Vr]��eV��.Y������u�B�.V����a�vd�0#��h����̣ׄ�w�2p\";̐\\PF;����I�\$���ݡi�o�8߀�w��;w7�`o5��\n�4m҆���.��Q\0����p\n������pI�\\(�F�_TBxp�~�}K��k_\\�Sօ�FƾHQ;s�;�YQ����t�1��z�ȰA�6�ANd�����5u������.�Z�S����Q��D�생'�Y'Ϣ�T��v	�݂�^������xu�%\nw�L!�f�x`���v��hio۸�[B�ph�69�:\"�����Ɉ��4Cr�ң�F�~�	�T���A�8܁ϒgS_�v=7O�Lo}6�쓹�_�jT�h�X���L���!����#�J6g�P����E�����\"ҹ�\0,K��8n6\$�x�M<��@P���~j�U>���̸����sS�\\��W�Gr(P��32,����{�\"*0����A�\0����B�?� �sM.�4'ؘ`n�3+��\rJ!P�A���JO�f��,^㟽��ڢ����,*��<��bx4���a����;��;?�(֦�2��cX4�<譯k�`=S�rF��aꢃP,l\0�\r�+�?�-��F��o-,��O\$h���w��?��N�@(���i�3��>/u;����\"��.������n9��(6N�񑢃��:��/�>�{�{£�ϭ?b�����*�p}N�����\0��td�H(��/^���f��!�_��;�0�a����ox��PY�VD(��;�h(�h�=�2��B3�\\��+�.���\0�+]\0+\r�/��I���=��������@ �E��p��ޘ��gk�?�q*����t ��Po������2��/�|*���(��gAΜ\r�A�[*�jh�`��x��Z�C��(��k4���A�,P��.G\"0f��#��&������Ŧ�zH�b�����I0�<��&\$?�\n2���=�7�9b��E�-�勿	|&���q�AH�A�J1	�+	<&@\"P���R)��%<�*�o�26zP���N\0�-��r!\"����&��P�;�]�x�<���m��~��!�\0�6zߩ�;Kx+k����j	�:̳��04=�#��ό�Uh��j��%*	���x��楈���`���A��2�|pN��\r���@���@��z�g�p��l4#���غp�6|ᱏ�j!�pb�.�hG�!�t�\\�+��A�1�E�,pΒ�1��p�>	J�n�4*D����@����pj����@{ݢ�\nl0\n�N����+Q�\ng��\0�]̎(;`҂墘��UC�&��a*2jn�}N\n�`�X�@r�T�|&f�SL����I�'��0A��\nĴ!���	�����H���2%+̏./\"�����h���oPơ���\"ht�	}P���C��	��4�;)0�|݈�i\\����N�b�.8ڸ\rՃV33�Xɦ��\ri�\"Q?r���\nC�TDI��R\$<H\"L�\$*tHl�����:C��x��H�㱲<��t*�b���\\�I:��x���P <A+t�H�DF!�Df�Ks1?��s�Q�H��s��o�M����E*4bW��\"Lb:7v��wDT!\n�M���S��\0B��l`2�� Ӭ2�|+��/7��Ӟl� آ\$\0��v���wg�SezK���Em��1,�[h1qOEK�\r�+�/�U���	6Dꨠ��/}��h���	�;a�Î�9B ��*���\$^p�Ö�_1���Q �\n������\r�z���V��DE�d�F����F%�u���0\r���j�`�2��Ea�E�	�bчF 4�cq�E�d^��F#b���D8h�\n�<�l��*�=!�4����`��B,{����0��>�\rtj��E\"\"m��\0�\"�N�Ci��\"6\0�H\$���,�j���H�\\��h�*\0�D�?Q���D(2t���|o����e&)�\nv(	|�B,Z���bQy\0�Pr�r)U��y��w�0�Z�)�q�c�f��Fx	�,Y�*0hQ[�\$�苯4�r��LⳝzuA�A�G*��	��9�������1��\$Z���v�)49�\"=�8�惴?i����ޔwc\nC.�07��*�\n�����`3\0[\0`kײ�i�/�#���\0�����ϧ �h��:`��2�`*�\n�s*�2�?S�����iS� 3O��HahA��Y I1R���squ�\\�h��L�Xs!�_3 G��Z`��赡����|j�Gy�H\0����6���p!\0\$\n�[��HvN��ĩ�\"\r�>���/��,Z=8��V	2�b/��i6H98 ��\r�W�4��Q^�c�c�V%�\"`\rh6%���Qr�{#p/�8H�#���u	�#��2(�#䇒6��#��q/N�\n�T�N��؇�\$!:�����I�`/R����D/jW H�s�<�>krrMA`pV�OD�1�	�T�%Q7��I:���k�\0�u�4���-\$4�68p�%�H�f�ҧ�P�m��w��ȅ�P;�{�H���{�,@��d���s@�~�y��8������'J^��I�|���I5yf�M�q'|Y½��*1Qm�J!|ui��>Q�����\r��\0�G��Sr�+�lW�yt���zC1��ʌ�)\0�a %ڔ�E��GcQ�I�D�d�������BC��/0�/g�D�t��WF��q�l\0X,8���<��4�G�ȪD3k=�>���H�0&���72jBD�h�������`>�6�w�9+@*�������t�.ң�~�{���|�bQ�ȿ�4HA��t\r\$� ).2 �D����e�È\$e��H1���|�!��!��r!ª8�⭄�-\0���S�;��;�P+����`� �ƚ\r /���\"4�rB��,�9+�����\0��'�pɐ�����A���\0�\$h*�|����.Ib�L�lE��3(X (	��Ql�怊��y�Ep���h+�/�N�ށS��y�������G�K����������|��\"������.<�2%<��k��I�e c?�x�r〮.��s��1�����l�o�L6��ߓHǔ�/�\"����1+�3�Q0\$��1��n�^�z���3��0(���^�u��?�!�R�\r�����L��LɁ;�B�RJ���;�\nao��b���K�\$x+sLVW��PUK�/\$�÷�/T���K􀫞!S��/���K�2|�p��_s|��*`��A�V:c�[G�(�u���b5X1�x���\0 )	��2K��p����\rо��M&����L^,\n��H�B���X�t����.��Y���̋,p�\"Ë,X���z�'��4�8�`�6Gx��3q3�������)D��<@i���*Hm���F�(0Ǜ�����+\0����ߛ�o1�A��h��n�����\0+�R�OQ����Z&S3t�)�z@\n9Âm	V\n/�XI��+`���)��s��HQ�O�#���;�\rꍬ���8��N-��6�B&�`����AT (jͦ&�zp�L�\r*�X\r��	@Y�VE���ߐ���<�����x��:P�ټ���E\0�ӯʅ�k9���C�a�Ɣ ��BC�t�,��\$o�S�NH�t�d�<M:��͐\0���Ѐ4i\"��h�B�9�,�[�;:=�R��^\"����/F\0�D�&u�P1�,����b?@��P)�d������@]\"��)[�m���1��t��,`4�a�>\r\\;p�ł�y��a28\"q�r!�KS���8�9��9��GZ��;d��Nd�������O����ː�#����H=\$�!��-����b�\"Q�/�.�,O�`\n��B��/��z�=�-�����^��rD�\0�H����db�lυ�<��H�7��O���O\0�@��ȸ\0=�h����\nx��>~~�ՊI%z/����(��A��#������u\n��@t����t�\$@�@�0Nih� ���	�'�C2�t�~p�,\"b��IG�tw�qp�>�?�0��B��=��발�B�A�7л;�ot)�;������:�5�mB��!��L��/Mfu�7��B��/��>��P�C�P�)x�P�KFcM8� �d2RL\r�@bV�᭚ �NG��B��>*�\0(t�2�R2� 0�W�At������I����j�Qv;\0*�x�b�XT>�,�0��7L� >� 3�2�?PTX�fZL��6>r\\~B���H�d&MB��b�������D�0&3�,:������ED���3b'��(�ѝG��t+��a�t)��	�O��ƅ\$��	�i���P�DDӘQ�Mɽ�u������~̈��>��T����ړ�H�.\0��]<9#!�P���b��N���t�?;�b��9P�ز+!��َR!�_��A7�8j�tMȨo)N�L�@l�����H�C�3���^#v�D+!Q��Ȱ�EeJPu��&D�H�Eܖ�33郱.\r��<��o��RQC����cD���:Ť_�+4���J�(�i�JԈqx�IIXp�\0�D\r'̛z0Q��F]�f�F;'�d�E�,Y��RP`|�ԌK�)��t�-���P%��� �25�l�3j!���F�b���z�/�D�֕�'s�k��4Ǡ����,�8����ұL���k@H+u7Զ�[]+��\"��T���S&JFI�N�\"3�R'K�*4�Ҡ�ס�\n��04�=�#�/���������<��K� Jq�=`u49��0���*R؆c#�F����\n��>�\")��<.-�!x��ʶ8D���Jڏ�ą\"3���4�Q�\$�}4R�k&��*Q�.�X�B��\0�Q�DE\0�0Y\0��5H��T�Y�{�F %��[I��z�d%��PXDD���+�E�LB�\n �D�I�V\0�H�4l2���Ea�f�d`	OԤ�%	�\0%�\$�%���;r9p��X�gEꂆ��ɀvQ�@)\n��P�UC/E	�\n?\$Tb:\"E���S@��^��UE���0���q�cI�Z�p;H�\"�	\"I�;X��\0���U�5@���9Z�pՇ\\E�3C/��MP�T1����%��KS	d���(0�FC<i�1VhX��XF�1Q����ehȚA�uE�`]\$���X-�Y�w�l��\0�]���|\0V��l�U�W#ECS����` �\0�{	a�:�Mbv��7�D�;��U�a�\0VԹ'@�2P@%��nP��FbŠ��b�\0ƦpI�U�um'U��\0�!��Z�^<�Q@>V.�]��D�p�u�\n&�A����Jɼ-��iq��/\\+멻��(��fPl#��&`X.W	\n��J�����f��jG���X\0x1ū(,�c^ˇV�L�Vn_�W�(���V��%<k�o5�����U�i�MF�V�+�k5�MTU�z��oub���H����1�¶QVB���/mTuW�B*Hj����.������Hg2h��ښ�g횬L ��w8\\mp\$*7�q*T:\"���Y ��\\+�J�o\n�ڊ���2W\nF=tu�ע��SÅ�^EZ9��[unU�\0�?���\$|>Mo5��\\�~GD}#b��W	�b��M��IeN���l\0�8ж�brYe\$G�cm9���#@�VX19>g�`@���i`��=�@��T�%�,����?�lN�����%���k�U3<LD�� �C����<p�ܡe�������\nH'ă�PE\0\r@0��Z8b7D��m\0:�݅�AW���: #V8[pH�t�B��X��i� ; b�Ov/;\$�5xKҢԉ�h�rE`uf��YD�ȶ����΄�/Tji��@��\\ �\$V�e���\"��Og�16h5aͩ\rZ�ȩd�\0vO+X��C� ����B w�t��\\���@��d�.��L�6H�3e\0006Ξ�W��3�8N8�L�d�2HP���k!r�p!Z��\"�V^�[f!`iGBj�blB2�w�8����|�^T�;���m��u\0ʅŝ�cA����\\���hk��g`!�]Y������t͟6t�g���?��gvx��h0%��~�� �Y�h]�V���C�6'��v��!��uV�4���b91�a�)�7'��΃I� ������7�}JI���^����\"����i\"�\"���<�����[�8���#\n@abͮ8�X \"����Z�:uSm�-�_(����449@6K\0r�+��TT�P�B�kP�J�B�����]����Fp\r�{k�K�l[��o�Cgl9-�٨� ���l@6�[\rghhTN<m3\0NN1��΀ʅ��\\��Q���?a��\0FHq\0�_iA����7H�O�[[O���܁\r�����g���2��Z�[��\r_R\\�Kf���k�2~]��Dˋ�Q�\"��R�\n?DL噂H#斸�a�G�%cB.����cbk7�Pw��&M�2c����'+AݡVB̐Z6��Af9���!=fpn!*'&Xo�;.^A��;�3XjF��@��`1�t��k�[����O��V��,!.��<%!s�_3�U�*��nR%aH;�)z�.�qp�P��XCz]��5Sn�2ly�L��P��4@d�X-�9o9�@�ֳ�K���c=y����|Z�@ ���+貵}�5�Sb=�L���\"'L�N65l ��\\E�T�N��\"*Qնp��}!2a`��s�=�[lƂ��#�b���1��4��UQ��u(CQm\r��F��b�L�a8���Yb�����;]@���]IA��.[<�]X�wL�'S��]0���]o2�ce��8:���X�5O��;a\$Hǰldq���\$�졐��wi��v��hy�\$��B3��'�����u�X \$l��B���\n�Y1]�|�O<�,�x�d��0`H�J�ڴ�ہ�Z�\"��r;s�\n���`�ؿ4�@�I���H�`�\0�d.�>B;=�Nk��qTB�8Npg��BT]y4�\"��)}�q克����w��.�^Sx⥚�\\��ca'\0�h*����e�K�䛜&����yu\n���gy��Wo��x8/��Q2����=��8=W�^�=�\"^��%�w��&�����I��\nnpD�tӘ��vU<�2���]�)�����/F��Z�/+�\nr�Ӗ�4m�N7���F4����#�\$�\r�B�����	�<�bKA\"*jp/#\0	A�4�v�&wh�e\\��Ų��(�P+����Q9���&qK�W�%J���.7�w���C�ʃ���[�\"-���):��&5��g�gP�Ҡ�t{\ns��.=�;֓A*k:Q>\0R)�?.��X0���ü�I�W|�!y�w����	�90�����]�x�w��ܬ�#�\nn����'x�\"�b�������{��\0�`x�\0��\nZ��#aW�\n%N�7�t����|�WCQ]�U��D�����Yn��p���N����'�\rn�h��\"j;\rޡ�,�񰈉ʳ⷗+>ǖ	&P,.(fW�� 7� ob��a�\$�R&\$�)��ΜI73\\�2���3��s6�nK{&�AT�l�x/��������}A�:`Q�hH�i��#5�w��1q���e�+&��h���{���`0oX��9�S`rU�۠:�CV��|[E�\\U.����F���{}��a�9�JI*,�ù\r�\rg&'0\\��_��M�!ą�s�o�j\"�����2-w���<}��x`���\r*��r����dm8` ����6*�\"��Ε�Y�d��FP����V4�Y)?�F���U��g=�\\�;�Hkx+�b��-c�X\0��i_��ݧ���H*{��-]�;%�؀�F��TT��f8��{X@�x?S�؍�,	���a\r��++��/	���.���lI��*�����2�u�f(R�ژ�E���4i�Ĵ:L�\n�-��]���.�'L|͠�b������c�@�n�8L��/�9�`�SMC��X:ϩ}��NRx3�����StP���rf*��9WH���N-��qc��J���ڃ↏⫆}���r+S\rw�NЪ������1:����������O����+�-�s�-b0����4Dt�~�ˢ�Ǫ�*�����޷3r\nC�ɲ�0t����z�뷴!43�p���!�+w��HjĤ*��ԫ���6B�ΐ8�\0�{6����{.87���0�?������-�݂\"ކ]�y*�4���P���{X�к����Ԏq�7�_����cx�W����u��;�b9�m2��9�j�T*N8��Dw�+rZDL?����z����DF���\$f��\r���Tx66�@��B�O��Di���;�v�� C���*���3����^�!�I8\rl	�����v넧�=h��ފ, ��A�����L�1�m�L˂�X��8�?��a.\n���s_6�Gr��+)k�-oK1�+��'T�e��+7\$���ˀ�r��+�W�#s��G`i��Tè3�6B�40C\0㜬V�ʚ��u�C�a����G�20[YNc�=���y�>�Nq�Fs�����\0�����o�N8\r�ѣj@��X�:4g8�r �tQ��¸�؁Ck�-��	:;e��i��\rHƴRe����9j���[�:e�/��y����6eu];,K�t�^�FYz�B������qR��%#��V��A����t4�4Xs>�@:�Ŗ)2t�d�/\08.�!8�c��i�\0�����5r�qM��/d��	Ch\rN��f>!vd!��F�x|��#˒fe'�^	�e�r��\"	\n��\\iY\"c���&�fJ36h�\"\0L`eH��Sk|ȹ&)7����0��ز�(r�5I�S�^-��Q�<�1��͗\0��ϕ��\\9^�3fHH�)y1���b����o���(�<`�-�q�g/4��9&A�*��	:���CJ>�	�[n{��`-Ἇ�rM�k��-�udr�`d&C���)/�T��>C�h��@i�ףM��-��������{{`WA��p_��.��鋴g�4�y�g���\$���b(�4k�㕞hC�ߞ�<����|����\r�}0D�dKp�N�y`��!�Ά�\r�\$v���>�z�ƶ!y볒Cǜ����C[����\"�Q L�Q�y�gqx|S��c~u� ��{Vy ���y�;���9{6���h3�p����8�w�d;��mD���>\\Cg_��v��u{X9��hv����h�y��v'��8\0�kN����hr�0��\r�S�@!��{c�*h��H%����f����z�;��^ɢl����j!��5�&�w�hM��<7�跢��*�[��׎hѡ&�X���%Fyz2h�zpRg�a���%��1(V��f.	y��c>�؎c�]�(�8��X��\0٤����f4X���|�_d���C�2�����<�,�sK(�Z`��/\\�,�h�{�`��z�A�r9�:f���zh[��)���~��ci�\nƗAXP0b:l�dIb:n�j\n2Db�ɰ�`D��m�ݧD���0!�\"�Dx�W ���:~`k@�!_^�w�S�K�vݷ ��rf����M�4a��ۨt�FD�\n�X 7�&\$��v�Y�U�AJ,\r��W��m�ػ J��Z�#��zM7je�h��p�`8�\"i\0s�V\0�4ٞ���oc�.~H����B�0�0B����8���h\r8���}��^(�F���\0����h���A���\$A�\n�,�/A�:qy�9�����ϗ���v#\0����6���}ƠެW��A��'�9bs����Z�B�֭\n���֭�t�p�������z�K﫠L���/\nȔy������Y����_��lȤiX��ks[�[�ݘ���\\墔:9y-Ƥ���D�k%��ʄ�k��[!����A�+�D���n>����Lٮs��?yH��g`I�C��9�0��ho�Ʒ���2η��j�~�\"뇩4��bk\$�β��\n~�h�kٮP�Z��Ӯ�������n�n#/���*��f���p_�V~x)������&JE�A\\E�|̅\0i��&�>�f:���,�������\$�?mƵ׉^(9�F� `D^���o�������؎}\"��h=��=�8|y�YTJATs2T�x~g��cy;G��e~�ٴ�ĵ�i\rkk\0�bd�Q![�@�}t��M%kQ\rZُ%���@�{5&46��N���-!��e�N�ï���K֯��Q���m�(Z�o�5���)d\r�h@�~=�{m�X��-Z�6Z�4d~�,��?�:8]�`��@��8��d2�7%�NN������E�z��`��P�j�{�;4�yaT�6\$Db�\\ >DX��I����u�\$Y ��@Qx�}\0{��VR�@E&�Ѷ�����i���Q=�.��a�����\n	���?	�G�d+�L݉�MŅ�����3[�\\�������C��^K_����P02jP/��yv��P\n����Af�n'�+ B�F�u�іj��6AB{c؊m׀�����]��߯R�'C.���s�>��Q�v�ax�=��\\����*�#Z��[��=�ۆ*�Xk�8�-�\0wC\0ID:��\r�̨�)�~Qql�%���|b�X �t@oK��p����fL\rm�\"�*P}B��nǽ�A�7����,Ak�%�d�����b�j ����t�]�:�H�V:�\rj��=�n�!�\$q󔛏4����0;@ ���aOÅV/ �N.BN�xd�E��q1�tSUr�˅�5��o�6��iQķ-زB����nu�����FKaTE��!����dM�\\F+%�M>���B-C�]!�&ˍ�!@�b���k3�֥]x��d\nɹ_�wo�K̠�M�e����P��V�oC��DV�ћ�A���f��x��,�VACؘ	x��~���f	��,�N|c�<&�`�H��(�\\H*�VWy�n����J�����\r1\n��'��P��01.\$�ˑ*8m׀�m���dN����	*��-�>�q���\nڎ&oG�w��!�\0ݻ�B�V���C���	���4���p\\67��^d�(���4-��p��r��V͔��l啷�{Y~����V;P!Yl}��\">�^s�����\0����X�w�Wr�am�%�<�{�o���K�0���c<�u��j}�vC|Ƴ�Sc(�v��!�.�y��\$m�.eu��#epZ��V'���d:�'�g������Ah�T+/�R��K�GYK@&�ۛ�V5����^G�P!{�-�+ͥMt��r�&�p�\0+�o�Þ�7��WԈf��x��c���|,����7�hI�خJ�t2�6e����p�\0�r=:�_7�]�C�Q�����mm;�b���# �NxA�a�P����-�o>�&��gM{�;1�\r���\\Ɇ�	�{l��LP��-�M?lA��p4�t������iK�O�S\n�pZ�m�M�؆Y0A�	��ܳ;�P|�^���)gH��KH,l��\n���d��`�o{�E�HLM �r��K�ܳ���/-|����޴��ފO.iĒ�/M\\<�l�*'0(G�T��>\r�H�����hK�{�m?�!<�(�'���p��ʀ`6 �a0&�/�r�BXbn��xH���W��5��@����0����/��s,\"�Z�̉+��x<�9�\r�8|��k[7,����O�G G�\0�;CR�񭰇=�KԐ-�0�? �4q��ɝ#|�`0�x1IqͺE#��v��S��d\n���B�V�����Ok�<?����ܫ	\0l8��5�������J�e��UY���ա�̬~򮬀Cp��ah���\r��v���ËϽ=*�JlD6��ᐫ�O؊�+g<���A�\r���OD)Iٞ��}���pi��KND�E�ܽPҘ�7b���<��Ҥ���\\�����8�\ns�%�\"߉�#��|���*�I\"NŘ32���C%0��va�/U8�c9@c���lC*P��s���l���E�W@ ��9Dֵ<ѝ��J���0`�#�X�m�۫Q����ϻ�\n�����Sp��(?Cv�\"v��am\nu�Tkc���	���W�T��={��� �(�U�LU2\0~\r_�/��S@e�&B�\$6��Y�!�-U����f5�G��+����989m̙�7�΁�\0��o�4���1�J�����������cu�:3�\nR�o����_�x���]v	�O�<\0�YH�N���lB�Z�υ�?\n|�����l�g^'a���\n�~(\\�G�~�r�x�&�.So��9@�r�ݚw��hc��Ą����8��\0���a`\r�\0�צ��pJih��=�S�%��ɓ�%����#p�3�@7v�LGm\0000����m��v�L@���v��?n��2���m]�O�om����\$�3b8yW����\0���S =��K�\n	�<3�Yݙ΅���0n�r������m1��D5�\n���t�VGl���KbS��،v�cy��u_n\0\0l�v�7��`�\0cݸe\$�� ;�S(��s��U��Hl�I��Ȯ�	�<V}s�W�\"y <\$*��x���N���(?��ܷ2�}�\0��Ҥ�w��W]���x��1߷w��t���w�(muL\\���^|ǡ8�����T�^]��`���	~�\r�\0b�|c����O�w�\\���;��߀�f����:T���`���nd�W�j8!�w\\*�a]I����C���m`�!T�>U�p�u��>�0e�DLG@\"@#L�\rd�s��t�M�H���cu]65[�쥍�W�F	x�^�ld@�3%}��M�ֻ��U�r�5���lm��ә&��\$\$(��1:�/�B=�؊G��	r�k�,N���H#�U[��I��X�}�x%��ף{��z����Ṉ��De��^K��0ow�7xCW\nyXw�O�~I�n��&�0y\\	}�:�8����lԥXA��DRJ�ƶ:X�	��1V���c���2���2[�z�r�织�_z~� �v&�A/ݯv����\r�?6w��x�\$t�]��\rހ��w�W�\"���^�=o�#\0��wu]��c~���_�����u��w��mP\"�;�����,�:)+���8_�x�\r��H����ɂ����u:��^�'��zUם`����me����+�y�S�=@6�s���!��w7�����蹞�K	��\nQ�(��B��'�E`W`=Xx�O�%y�c_�^0W�f�]}�\r����|�p��hb�~^�\n�K��aZIuM�ڊ-��7XI�N�#��z���խ�g�;^�r<��\0z�y�M�J)�]/�Ul[��\$K�C1 �b��\\�`���r! 1w_2A\0���p��Q�/�'�w�*�U��#瘈<�\0�;'x����h+��(��8A�ɘ9^�05�ˀ�(vȢ+;����\n�'�����l\r׺�V���.�����5����-��֗s�\07�w��\n��L�4I���l0�d_O�i@p�NW�Vh��(�X�E(\"���ˍ�Q�*��_0s�T�9����\\׃�9W�^��q�o��~�|������c�q��/���?\n|F�'��v�'2�Gm�1��ľ�|D�?ġ�M4���_����\0E�_�鈌�-��\r�*�8Ǻ]�(��n�Ҷ'�0&ج�{��ݞ�^!z��Ց\0Z�p�Q���Hu��^�?�f�\0{�j�;%��|-y-x���>���^9j���s�B�:����=�	�@E\0�i�1��\0���\$�|�]��#���{(p�̛H`���9����a��ulo�f����aB�\$p����j�ľk�M=A�Eyփ��2��y�+}��d�o5[��q/�v}�y���\n��@����жA|A�g��e�=�i�BQ�0�,-[�HL{i�z\n��G�`�	����-��\"{�	]yB�\n�������\0cb��W��Q���T'p�0\r]��*����=�.����#}q.��\0�HEy�u�6m�����^����M�\$�Qz�%	����\0��ɦ��W�˂����2 h\rߖ�>	x\0#�Y��( )����AX{���^�e���9�x�C}�v��Vc~����\"������p����%S@;���O��J�1���?��\\(6~�~����T���(�8]󦮞�ic�.��\0��\0(!\\��*�\r�O�h`	���~*��Ȉ��n�w��-�֕ �Ț(	�pR�Kzx-����&�Ww�w�OX�ͦ.#R�d\0��\0q��0���kl�VvM��]=���98A��R*\r_4����_t��M�Gp�H�����Q����R��|4��c3A�W(\\׹��3c+1�T��:��JtU��f����p�xP�s%l��0�+9�vU� ��y�!#gg��,�S��U��B1��c�A ����I���x�e�!P�`-��\0�_�3�^J�Lm�_����n�vfY�:[��<FlY�'yl�rő�w@��ά\r*EzjQ�3��/�Tȿ��\0���'�����RX�h���@�����pԫ\$�F���X����B�m�\0\".%υ~����⸀�P\0��%�Xt�����&�?o2�B�З��+�_�����;�n���B����=F!���f+�W>@�U8g�@[�j�FA\"r<};� H0\"�j5A#�P8SO��Ik����q�`�캜C8Rt��di��	��\\>�:E�+�w�E��ǁ�WT��@'��_@@�}�^B��[Jb@�M�a�C�#�@�\r¹�)�d�/�.td�Q7j�3pa4@.�-���+!VQwO��>:>*`k�a������XX���y6`�	�*�RL�)[��7��L��� ʻfH�Ͳ��xBP���E'����<�wd����p2\neo`���5��gvP\"ł�`��M��0%�r��<H8�4|זl�^L��>�t�	H\$an���_��]���\r�_����̸8m��\0�_\$:���A������\rbO�_)�@��F��P'�X&#I��%�aOo=�c*\rP=�c-��*�(`���ҽJ	�>��Ȍ��,�LB֩�l��Hڮ�(���	:iN���T������tܾH>�\n������4�q���7¶B�@��+e��P<�[�<P��v����[AxĠ0=/R��������*����@�㡘C1����0��h�ና��gTt�bR�\"8-�B�r �f�K�Z8,�P�l&� (�a�\nNU���/HZ�yjՀ�x���u�\0005^��X��~����� �������i�_+�y�6)[\0],�çb�@@�A����?!0ǣRe�`��3��/�䧙mw\$ɉ��@�s�TB�\0�X+P�psA\0�R%�s��a	��`]91`���\r\0T!y<R��yp�R�Ss�>�^e�m��2s%�����q �A����Z��>�5W�A�@ ~���88I�A���e~���=*dA����=��g��H�8���\0\n\0-���\ry�b,�r{i1΄��!����AH����&��ŏ�˂|3\"�`�xGP��6\n���L'2�GĜNBerڟ���RD��������Q���Bu��\nl����3��!>R�\$3���\\Pܟ�s\\��M�}�[�l���:K��S��U{��X�9d*�Q�{!T�1�	��:� ���.]��0�ʂ7��\0*x�A,��!a&E\0�Y��(�WPl�ܟ2�\n>ӯ�l@EA!��\\3�UK�j��\r4�����@jǂ�ڧqy��X.�BmR^4��Qp*�kĩ�*���P9+p֛%�S�^���c����̙�.��l-�F���)Mx�p �&F��T���F�f\$���I�]P{U�yАZ���0��]�\0F��;�����BVo~Y�K�N˾�NJ�@�?4 ��y6�x�΋��A��;c��T��K�`������Aǵf�y��;rPF��\rþ�����a\"��\r�{�g�<�}�.��	�1�2��t�&��?D�3nT7���T�-;���#�@c���.��l��9��²�_p�B8q^�n���|���Z\\0:�s�G+�+|����F��B&�/���wd�C��t��u��y���g�.x�jA�s��	5��2x �(ET����z�ME�m����aR�OLs�育�!+�\$�I|ʠ�R4 �����\n��?�#A�X�k^C�x�K�B��	-��m�A�����Y��C��a�c\n>��y�BķA��^�2z�ŋ�kZy*0������Q\0`҂e��B�c%�Q\0003�9\$��O������y2�.6xQx�uc,�\"W�:�  Wd�֞�\rd�V\"h?Ĭᘀ���\r�2JX{�t�W�*:2�'�2 &��<F0�i\n!(7�d�Y�K\0j�s쎐���a�Hl�7��d���L!k4Ph�N*>�q`��n�h��K͊h�j��@݀�+� ��/?4\n9��W`��>+�rdD;Z�<��	����\n��Y���^F~z��]1�3�\n�B����8�,�T2�ьq/\r�L(��d�r��E�dF�B��lCT*z��W`���/Y\0\\��1�d���>�����&�M�w@QC(�֛̉D�~�R���ۉ��-�5E�bw#\n���24J�t���w#)&�h��;�E	[\n=���w�f�a�?�*ae��h��Jt�eP�a�@ ��!�������15�r@Ѽ�����!bV�#\"�L���,��d�ለ�T�E'K�4���C�����0���;A�U������@5�7����bYER�+�u��H��J\"�u.\$��<�9�����R��x��tc�X��Ԥ�Ll�l��X��R�c��hG3��������W��Ezu�SDA@�3Ad�&��u+c\0��0����θ��b��I���ਲ਼��/eB�@�\0�r`qg��*6\\g6)2h���\"�C�}�N�C��r��}RLD�,�~�0���]D�&27q���8�1.f?�B{���n���s��=�`v~+�\0�a�E�)z#0�;������\$���g��0�4n����݇@��\"�0���]ш�F��'���^,lgC�������41�@a��\n���0\0@�\"����d��Xpg�y�˫�(\\�{�����7��9�����ƱN+,+�m��cd@ϝ��U~'L��Q���U��E�q3�Y`�d�w����&�d����ʁz�,c�q�%W\n�%S�\\=�iYd<�,�|}9;����p�� �P�F1�c�k�F��0D`�� �!�r�\"-J<�� �E����g8����2�	\0��\n0lgH�*�c>�j��3�(�P��A�Z7�r{\n�Nk�c6�`���	Vj���&%�f��0�h�3�5�Esa���h��}K�|\0�<R5C`��\0<E�/4\0�Z�q\r��#D�:�T�/8Ce��Գ�|��\"V6\n (E����]\nn��#HE��F�݊�{�E�����K3�vix|J6�9����R�/��,�(uѨ�c<�����j��b�Df�P�42������D<b٣`�`�A_�&\nf4���\$h�`rCREj܃ ��q�����~�t3�/\0\$���D#R�Tq���vc��'x/:��\"��3��\npuJ��t�}JhE�0�iIB��^�������\nAZ�s�[��O�lJV�:3z1�Ǵ��G2\0L��O����q6��\"�lĤl|�nB�P�b�:4w\r���� ����\$A\"�|2b���˗�l�Y�����Nm���F�;�?(gh�J����=�jqqR<�p��B����\$:�ިn正\0�j�=Tb��&@��Eg:\0�;t�Ɯ�:������l�2R��I@�����Bl�)N��.`N��攻G��M�e7'�ٌ��w���E���z�w)lT�-�x&��P\\��0���,R����RG\n�\rF����g䬦t̶�x:�W���\"g�>�U�V�>���\$�� ����a���꤮!���c��X x��1c��6��\"V?��q���bT��opu�0���EF���GPS�uBpArQ�Bl�/�Ǣ�TU��#P\nv'j>�ؑ�\0�*I� mMTM5;���3����\$S*�ObG�zE0��p�!Y]����N&�#�8�Ȁ�C���,�s9���V��6�!��s��I���G������!��4 ��?�~0�!@��H{!rB\$�������Ð\\X��ba!�@�,p8��.�h`j��r#����/�+��P�(:�����U��3w0�	��b�ޕ��j��CS���l�K`Fr5�\"p>�luȗ��B��0P�r\\CG��!�\nb�����1P�Q�� {A�ȭ%8|�B���\$TH�����Cj��qD.:�li!���L�@����ˀ�DW������Y1ŝ�.\n�|��cAt�\0����9���LeB�G��@,���2\0�W}3#�Zk���Q����_��F�D�����h2��Zп(Q�ˀ9�\n��FH;������k0�<�5�B�V�\r�����L�>���ݫo0Z!��%縬�O���z\"\r���l��'\nc�I^*EQ{��ju�(��q��]����II�R)��2���BW��z��ՙZ/�(��:����d�͇��VǬoL`�!���d��EX�IA�D5G��r�_��g�j�^g�I�AE	�6�*�LV>���?�a�<`l��d��o�� R���щ�����]D�)(/`󠾓 %%�)␗K��A���vL��Pr bjE�|�:L'&*7C��塿���6d��*�)�W�)�� Ə�A1sC�0z\r�	:]�<-����\n�J�%>J��s����ݝ�`r�E�8��O��3o<�`Y��Y3#j��ŀ5�J��m�s�=���;Yőa�D�&�Pv�N\0ܝ�|����k�Q%	�b��JT	��1�A)'����`)G\rj��K��PP�]V?��f�`9���1��Ae���\\���.�HA��(�l���L�h���S��� �LW=�\\\n��63�A�hڥ�r\$\$�5]��fx}#���2�C*��YJ�t���\\���/ד��R�|����D��+:	�)hpK�K�2�<JIb���0_�S̓��ЕX�0����{��Y!)v))Ӄ�yB�R\$�)0�!m`���@\0b��-��H����f\0��H[�yM�&��r�lG�ĝ�\"�a�S��x9Pe���.f�ydI��`��׀k�\$R(Trm�����(��B��1���I�	&4yf�2iܻ��r��1���\"RVE�?c*�q8����5�ܘNU,�h�rI��R\0�M@�5Ri�/mH�w(w����\08J����/<�=B\n�Оi��04���3��!���{'|�����y�!I��-d��=�l2��	mN�^_/�O?���\r�L��7�E��)��p'0���J^�`��_�<*�Z2����K\\8��h`䆐L�,�*��Ys�����5;d�B��I��<�����^��)(W��`>��-�����c���]B�:�,��\r_��\0006@�z�>̽�d�%�e��?U88A^d����5��\0�+�c�b4B����n&zE`7`A�n�˓z�`(\0�Kd����d���s��pFP�\n���)�P��E���<�Z���պ3}*!���A�PU�y@I��(�Z��\$��(�H�4��I&��&���\\��NŲ��J�ʔ�N�Z�R� �Ð��ܝ��P@���Yu`�uS�#�D�%/0)�A����񹎜H���j��]���u`�Y.i*FYDz\"�Ȧ�<�G~@vs�r�t�Ԧ\nXN�&\\�O\0���e�/W-(�L (\\p;R�#�\\���@H�|�%�9hsB�]��0���Vܸ�%�}[���Rg8JِB)�.��N��R����P1.�`aN�5 �%�g���*^t�	d�ၵ�p�����J��h�\\\$�\n�Q.9���U:����z\n���䩔N#�����.�pl��P�0@���db]��~��G<�KDu�)�(�8�<�!m���Y��v�ҒD����\$�X(�jU���\rJ�[�z\0���\rn�/.u%Tey�@NR(�y1�>��/�&#�y�5_��#E LZʅ�\0�,���v��o�D���c�rɐ	�U���<XZ)�c<��ʓ*\0�c<�b�G��cd'f4T��퀇\"�YU�S�_��tFleڲ�)�1����M������*��D�h��3\n��4\"��\$�\\�\$���A�J�n���ۘCԦbñ̄� �à��\n\n�?6��8s�d-\0V�Y�D�ep��'ܪv��EI�B,.��B\0	���`h��Љ���pB��,��)�[MLz\r\0��� �DF#�nW3�g��ah]f��{\$�3���s�Ft�V������~�v��@��1f�0�Y	,'AP��~�\$U��u^`��ɞ��45|�f�ҿ��I��68�h	�3��VC˳����I{A�����\0]���<� PY���������%zMMa,(25jb�h\rA4���|!`S*%�������3�.A��oE�G��/N��R��G�p�e�5�pe��k?%� bQH�8#��B`f6Bě�\\���j*yYr3Rk�J`�)I��0�2��TN\"Y��lvO3SB���!<���G6ž��HP(\n���P��f�1\0z��c3��eKa����C�� '�h�̕���Vf�\\I��iͯ3��.#p`�K˒����Bm ɞf>既��*=���ř@g����J@���ڐ�s�_\0�P.�q��ɦCC�b/���P�lϾ��\0�w\"d�h�-`���9�����] �����K�-�o���݂����\0��p��}d�˿�rA�z������sX�͸b���Dq3��\0D^L4Q+a���Ag-S��0a)[3'��!+?�*O�԰譗�G\0D���M0&��DE�	�K���S��d�EI��2#\n��[H��_I]@/U��q������q\0!�ݍ�L�sF�E\0��l��T-��7i��kX�:�q��\0�\0ZA)��v`#J�`H��c�I�|4�R[U���\0�.)�z\0s�u��a��䉄m�L��μ��:!F�h�\ne�u%��k@T�L73�zz%�+zݧD�����^\n��M�@���l��B2��y�#:X�N��_NsHs�Yb#�r�Ka��dl+�L;�y�����QG��G�Y��N��Ւ+��ýBoDV����\$�hq� n���c\rj�F��N��Ƣwj��\r��!��b;�<#1V�镂�7��5=��f �[�?�\$]�%Ӧ����O\n@�\0��j�Ԧa�.Ό�א�H- �3�C�턘��-Dc�Q[���B��iІ:,n`/�	\\�r,s���-\n���7��L*Q��B\0bo��/:��r����M�\"��R�q2G�ДX5*?^�b!�,.-��Ҟ�10	�������2��k�z�kbK�G�4G^�\$n꿩+[ԡth,�����5��V�0Zb�~��I�sf9v�Rf*�@t��A�y����VX����ANT�Ty�Hw���.Wj�������	�N��	�?!>I��F%����ϙ���-zt���p�χ5X3ћ�t�MS�k�v0��.u+9ļaOG�dh(��\n�Q\r�gw\$�\\����s�%SH�S�*ѵ��E�\"��3����D���G;�9^���@�@��M�^ʐ�O�.<d�R�b}���~b���QR�s�UD1zT���Y`����5RS�R�n@fX������TC<��Re. eL�^�>)B��P�\r8��;tAgB�-:�^\0H揔�֜���6��A�.�D��J�q��P.�(R�y]F�T,��)&P��t*�ޒW�@7d5p� ���ɽ\"CV��\r@4\"Ib�&g�����h�<AՆ9H��Zj�p[�&n��� �\"�w���!wh	4��j�B���P�,��7�b��%x���\\���7&T:����^H��`KY�������kІB!(J:�}�k�IyT�#��x�M%j�'�8fX@މ��dh2H�?��魪��=���G��O���s��R,\0Lg�\r3���h:@	��!b\n.�\\ӫ�����M\"�B�3JKx �@�-PYI�(57w���������Ћx������O���}b'#���k����n(�[��\n�hp�����+�6 \nOeBi�0��5�@B�ߕ�\0�\0�0k��B�耀**^Sx���6<t.��\n���ET�#N?\0��x��P\0I���r���({�J���Ua	��AnpO�%-���VlNǄ\0z:b�\"�2�)S�>Aux�2T�8k��P)CO:2rQ)I���U���09��3Mj��IEYR�R*��a��\n��Ut�E^l1��\n�����NP&�Z�����PԘgDdbŖ5��7��M��E�=��x� JH�+*c�(Y6���ދ���.�P�tcxT�+(����+�=�>jkHm\nhĕ�#����#����\r׀G}���ݸ7�+]����<��O��Āu���u��S9�BY�)B+(VpR�h�O\\�<�M26�V���@��/��r���.���e�Ԍ@�j��=�F�J80\n4�x@ц���,dȚ6�TE�*:�\0�p!(��R���@&��!P�ipE3�=T�Zp֑:Bi���\r�imf�K;�20�iXӜ��\"\0�ρ?��1����F��\0Zސ��!ɯ���T`��7�%��_7�T\0�\0����'\0b�D\0���Y����U�.�t\r7�b�A������'7�t���\0�N�\"\r�PR��������7��(R��@�tl�	Yi�G���1N\0��\\�zz=T��ӟI��\$�K��0c��` ���4�]ORnu;��#1�P�F����I���'�\"J�\$��ؔ�<R�J M��n��e#��A�V�RJ�JBSҊQ�K�RGh\"��w�@Aj�4Q�pSH<�X�P>v���	T\0����V.<I�R���	i�\nV4h�[�p�:���G. ��\0(�HL�\"�P�����R�\0�8b�0TӪV	����QӖ���l]��h&�W�ۨ�C�(H��\$&�Kº����H\\-�p���pFG���tU�y�#�2<*�y��bh�D�/�>����B̛�GQgy�lM�6��`�S*%a����u�&����'*�Go2��l0W�[��	>�%`J���l��L��2ǯ��`�,p~��GMs=�U��\\�`X\nʃ�A2��\"e X41'c��┪�4��xp�\"�\$pŀ:Sy�J�9d&��d\"S<� ���3\"�����40�m����	#�M�AeMl���*�ᶥF�E�z��U�&�x�XQ���J`�DNPw�3� t�F��q0��`�HLMfI&Ȳ��3�Ei��H��i<?a��,pR4PWJt�����ކ�0�#�)�Y�\r\n��C|C���V�������@�C\0\$�N��*2�nV��ʣf\\�ځ�lH娢�YT�`Z�P1��ւ7�n�%JKc:!\"+�����o���i�8���ƈ��#|sؽQ��)��n\0����8@���;�N�*�;��\n�+2(P���}=�~@d��M?F#Ҏ����.�����uA�U`�>4�P@+%;�.��A���K!�2M��/\0Tv\$�J�T��(�ǣD �V����g�	���j�ԕ�y\0I�u>]%7O�c:��4EF��CO��Z��j\$HL�\0���A ��*\r(���HB����4���w�H`H�Yp\r����?.������r�9������3��\\�,��&��DD��*�j.`4���;\"F��E6\$��J���!g�/�&vm��v \n�\r�v|0EH���\"�T�\0ER<~��SzX�l9N���&������Q��D�����t��>��15Jj�z\0\$��v�i7p҈���Q�YGD\r�K֡tuj^��\"cSZ�i~*b�-X��#:��x���\"�Q��7��݀�T_\r�����5����\$�Sl�g��6�g�IteK�ju6i��LK��Z�z��eO�\0&7Ģ\n ��t�\n�\n�T\$!}Q0\$�;��QTh�XTa#t�@�/�H�[�\0m/���p�*��8Jt�8N�\0)�Sڦ�PcS+��/5�¢&���(�dᇃ�7��D=T8�C��������w�|CwX�<:���M'�.��@�2#to�@Svj��kQқ�H����=,�F5\n�I��K��T����K'����U(]8\n�&�Q�|��\0e�)��;��)���(It���R90���0. M>S2*& /##�aY	�T��ڕ.����-¥�m3h9� e��NR�Ķ�2{f��s�ޠK,L4JA�W��\n~���*o��P\n� ��[�%�a\rH��J9����N�\0���`Z5tC���&�DϚ�H����#�4#O.��Â�`�k8R�&Gp_u��ܐ-��1M)L]S�U>�~+	����JP2�O�U�B��:6��R�/����\reג�Az��QXs�?�Q�.J\09��Ā�\0Q^�R�tM@�d���@�8�Xɱ�Q��\rٜB@SJn:ʄP�=�����0�Vz'M�_�KV6��Tnt��Մ�]C��eJr���\"d̫Ϛ*�C�� b��Տñ�5\0cP���0��4�	����Κ�>��U�+\"�X+e<5�ڗ3�m��I����',߫7����j�=�z��*f^D�\n������@�z*�?R�z��q*�Mn���~[���ef>�\n�5������!���5GW�3�0!���\$(Ώ�)|μ����e��\r{�9ZpP9-�����<�GZ���+E`,��!���X� B�\0g�ˎhk\"�@�'�hQx��Qln%�����)��F�>�p�bY���9�Ql5V�� �Qڠ��߭2�?�`�[�\$�2��F�t�\0ml	e�Z�%����S|U-jgԷ�\n&z�:�>\0\0L�j��%F\$��]W	��\"^���%U{�7���\\,#=MJ�?D>�ՒcDj��:��	��{�:��q�籖���I��\$�r�'T� ��(]DN�<�,��.�4��<�d�\0D���=ت�Nj-PwT����N�P������E�1��k�U�Ҭ\$aE�	F��^�صҫ��ULW*���T>��[����Z)e���l7f����v1��a�1W`ؿ�6a`�3�+�\0xzD��W�L��əT83x�A{�WPu5�8�5��5)�\r��\"�J�x8���S���qW�C����L��L�_�\"�dQ6���cb��L5](\0�kH�4��������p,���	�z'��������S����uKF��ut\rͽ�8��Xo�`��S�)�-7@'�M\"�m.����zt�2W�^�Y�J<tF+y�T����q�A�`i��޷\r(�!��+\$�5����(��nֺPʑ��e�.&_��Pz�@Χ���v2����1�AN�%��c�+a�:�6E`�Y����ݔ�z��HBs��I�\r\nBS`l�=�e��\"��ˣD�%�X�aۦ]����0�U���[�2�XA��<��d�������hmC N�]����XM�(\ra\r���v�c��0օd�ys2��	�p!���a��wA���7Xm�,8D���/��ܰ�X	P�J��jt�A���s��~iq�r��cP���^]��`3��\$ɺ!VpK�ɕ�3AS�J�x��v\$�������F��S���PH�F�ו]&:��p�\0�H�.�vp(���.�:\0p�p�~��#���,��\$5\$�_!|�����&\0�wZ��.J4Ԯ,Y�rD\n���z�����\0h\0�'T{\0\$^ �lg�r��\rt`��)e�;��q|0Dr���l\n��cm�@�~4�_胟kd:�J(mv-)�l��Cq�j��dB��5�d-�e\r��d!�R��o�d���X�q+��Y���u�h�#�\\���#\nF�4���nl��)6%�����Y\\��Ѡl��ls\r��aW�i��X�'>\0����2����e�[+(��,]�\0�}}dxYʚ�T*|Dn�9�g)2g���T�L�et;p�,�C*!��\"�J�\\z`�HO��\$Z��\nqE���T�4魶���tq7*�@�H�\n�˰ũoJ륁V�]!�J�1F��H�e�E�zn���VGm+�\r1�O��	J���\r~��_u �=�9���\rf�5C�ŝ٩��P��if����(�Ų�6p��G��ٹH�e��800?@_�I͕�e���\$`!Y�)?e�+F��5��Y��*^�Hʲ1k���S�U�MUq���M2�P̙�rj���@�g	�C��t�=����*��5Q6[v�}J�'�G���c�F�~\nK0��,�T;1cF�( ���3�GfZ��� I\r�)\"��(Ѝ%[B�c��@����[C���Z�&������T��4,P�\$�Qb�%jMv�k.���,Q�	И���?��g�Lc�5�:��r�7B��,������Y���Or�,��4y���[c��\"�r�*�<α�i@���J�,�)��r�6)��a��-�N��6Q\r�B�7H���R#.�i�r༩�ƼAY0,��\\*_�c��l����\$ɼ���Vq,��X_���U���c�a��#z,�ǻ��>�8M`Y�[R( ���w@-=�\0P(`�\\qB�	�z	�7�C�Wv�a�i'�'{�՚DފI@����Sj����)�ND��ԫ����M�vƃ{- \0x����խ)P!ݨ���ntUQ\0%�Q���K�Q��A�L]\n�kPT>�\"UqS��MFR����#�HZ�E!�\n5)�kq�B\nDW�Q�kh�%cs�͠C�Zڵz��v�`֮\0005�%�����[a��� ��'�VȘA[%�jO6��ڴ�ŭ��TwC�T��x��]����*\0��ڠ�lxgM��ԕ�ir�����rz��I�#M{��%�^�=T�V�\0�T0���W��rɌ�Lj�ei`TD��\"֣�gx��0î��)�R[N��A��|�bhÈ�c���\"�\rF̐�#TVP	��3���M��X�B��kT\n��T����u�m��:']�j-Q��1f�F��╹�M�n8[�~���B�U)�L��Bʺ����Ϗ��+�b2�*M��\"&���ۺ��9aq��)?Um�z͸����-[���%1�z�\"N>e���.�n�zkR�T��#�L��)�Da�@;�.5�ʉ��S9�|!<���P4��C��Z�\n�K��Pu%��r��VԦ��Y/ӯ�����w��709Y�ba������`l���y��SƐN�TB�<	�n��JX�\0P\0�N�(�\"�v���Ѥ��J���6����5V���ޞ��}�&Ѫʓ+�s\0��8.��\$(�6�m�{7�������\ry�s��w�`���^�����&�Ovk̾VSZ�A���e��e��Jh������_�o�U.��.���:�i�6\$�I�H0��F2{F^�1���i3�Z\0�fúk��r�O��B�vE�u*ϲہוb��	z���8��^��\r�%��%:�<	��\n\nn�Y4��V5��!�����I���a[��<�\r�����4W��y/��TxfBq��1�,`�d�\n��B�#��\\��rEMG����]�\\���!x��91[653��<����x�vZF�!���b^��y�E`�/p�׺ªL0o���VQ���˛w���0�\"FzCb�	���>'P �@��cs�	�9��S�P��Y�<�)0cGB�&�4���ϖ帒[��Ƅ]��\0�s�	���Ɔ���U#��Y�u�\"e�6jQ0Z�p]�0�o9�K�b�@V2:�e|�BH�e�=s�<(��M��A�	b�L�F�Ǖ��أ�;~k��V�B�Zͪ*e��P,)��}� q-��a�夂�MB2@�̲��	HN�X)r������\$H�3�7q�b�n��w�yaN�ɉo��|2�ݙ���ƕ �]W��F�4�#nR��>X�^aW�k&�7�Ѻ\\86�+���ƙ��g �����\"0h�J�:���|+זd��]\0���cK��@,ݖ��Gi�	��z)pn�أPq&?j�`��cct�/�J�\n���Q^���q�<��5��ޗ7GYV����4+�N\0�Q#4W��t\0ȑ ����{X�R���I}	��;�ة�2��z���fĝ�d�-�Gw���`Z��~(cd���o����t\$Sظ@צ�ȑib�1ڍW������k����!\"�(ݨ7 ����!eH;����~�\"�\r.�'¶׀P�G�uJk�\r��϶w�L�1!i�� ��U�\$�����A��k%*�Iv�n���ׄ_��L�0Q[!��tZ��X�	㐆�l�d]��t=d_���`Oփ�Y�{y�;�t���/&-��v���m���(�d���^���`��~���V����f�Nג�v@xW�B+*��Y��F��<J���۫�3a��_a%� �7v�46��U�#�!0�`7J�@����@�3'㆚��zA;,��%*I��kT��2vZ7��˰����p��2¨�m=�i�jb�`�\r5kB\r�-zG@͇��l.ؤ8�'���^��\r�&��;Q�S������B\"��+po̓\0̉�f��{���@8^�O'{y���٭���޽�H�,:>���E�O�\0@�2��L����w��\r�������\n��:~��2�w�A���iᣀ���A����� )�#c�w�9�\0�Hp��(�J�'�*\$6���\0����վ-|���:��R��\0x)d���K\\c��P;&28��o�`�Ɇ(9�0�d��fmoSc}�|��5d��������I\0&-��l�!�%LU|�rd�4�ɇ�4K��	\0R�4-�>��0/�BE)��\$�\\�j�h9�A�L��D�\0�O%(\0���� 9(�&@9ݰ7�V0ЁHCD�����#|�~Y7@�����-� ���/N�&�,\"��@�g,)�<BS����lt �?`@(�[�w�Z|iW���mG�Uy�l�!�bQ�%[-S�����	\"q��e�\$�r]��g7��^��~Sh��E8b�m��_�}�~�|�2�a�d�]T;\\����R���m1�X!�2k�@t ��D���9�) �:�XAx�=�)����q�y�hb�@A�:�\r��	�ȸ'��ƺ�[H`*�M27)i<�\0�-��l*���`�iL�`�B���n��0��:\0gG�I\rͱ���x\0,+<�t�\0Sz�C5/��\n\0�\n�;���Ƃ剿�H�0��!��@2\0e��-2a���Sf@��Q\n3\0i�0B�e� �0�\0N,���2�D�\$\0'H92/���s\"�4�&����P.`� �JY��/�*t'K��\"(06y��\0�ci�l�\n���.m�&0�\0V�bTZB���8�s'��E�=���Z�i�\r��Hr�\n�3S)fCh	\n��0E=��Y�;�|@KF?W�\0�s�ٸ(�=�^P�>��~��`w�ɀ	x4\"7	F�`��i�-��!pi\0/���\\>0���)�U6	��t<d�0X`}����Xb@k-D=u�y��bC�uL�����N���\n`�M�����0ź��w�S@Uۇ��RX#�j��6����A\0��9j�I���Tr�D`��H#|�-�X-��`��w��X��1�0�dN&(ʝ�D�/S�l��\0`��_�����[��>���z�=e�.�\0S��LCa>���`X`��S�y�Ky��`D%j����f�`��	�HC�9������Q�-~@,�E\0���;��n�Ȟ�a\$��&�P4�(Knʾ%N�\n��l�eY]<>\r�=5p�*��0�aZ�!� �!�X!�`lq��\0q��X�5/���SD��XG�ea��*����0\0s��_�m~�ި.�� �,��?�\"ZL��v��C	�ܧ|YX�xT*>&(rKK����Q�Us`��+�\0��8B��Ft�qE�`�/h��?��*+y���\0���E��|d ��v��T\$����;.0��6����r�<\0���=��è�8C�pQa�#�E�\n�T&���C��	��&n���\n��]�s��H�����(�.��>�XQ�~q> L<@c���\n0bF�5W6�\$a!���4�� �bMڠR�x'B3�T\0�����^I�{4�2A#L�bѴH�Z�ٓ>agg�ܒn�xV�(y^��!����*L�\\iʔ4U����@�n̅�����%ɉҸG�\\����Q��aL�	�+L@,�f)Ga�㈍�&'�KX�_�0ǉ�@�r`@3�\"���;����U��a}�,�N�x��\$bEX�G�0;����![|� �h@�F/q��{�0XE�*�\r�d���ZF�h@<�J���ʋ]}��5F�ɱb��ᄳ���N�P@�.<.�\0I�P`\n�d%���%BD��+���1\0rC�� -���͈�Ov8J�q�W��\0֏q\0c�h%e�x��@bĒC+�|�,�e��/W(�K�|^r`�����I)��2�,�\r@m�#�h�U\0�UJE�s����p��6\$p�0of���݌x�X1`ȸN;M�N\":���h�t���J��+\nX6n�\0~Z��8�́L��%����|�hq;����p\$[�&7�T�&3p��(ka�q\0�PRf���s��~c/\nnfD���H��ȋ1ova��eDtV]��M�|�Ql����uI�,�!�Ie��r=�u�G0��A�&bl\0� �!�D\0�{�eJ?rwX�1œ֒F�\n��v/�ɉG�\n �Y	�=`*-����ʀ6����Ғ��w���C��`\0E�k��n͎N�J�VX�®�'2	�b�Ώq@�E��SD�V�#��Ƙ\n�)�R<�h ��Jm'�y3�4qX�1�c���˅F:�:��ە\0000ǫ��q4z���tF4А3G�8�j�c��M\0��?�e�}��VD8峢\"�>��oX� 	 uU2[\0�­���ؗ\n����)e�\n�P�X��D����d,\0@�\\+4��^�`�Q�Px&B=c��^ ��ۨ�.���xҘ��D�+���م6���\"r�3hD���nPp\n-@'p��O���?RǑtA^0TΞ,��Х�\"�2k4GC��(��tM,�7K[�����\0�3�\0\n�)�\n^����>94����;2	h܍��1�`�Y���R�����\\\0ݐ�;h��Y���� ~r��.gt�(��\rp\"�ʹz�Vҫ���]�_�ѵF|ZԞ�ׅMQ��P!��2Zb�P����^��\0P�N�8_�%:�L�o�d+P��4:q���V�몷B&��?9�xp�y�Z��&l�qu�=�Ւ��<�����p�\r\$K���3��#װ+Xfª��{@�\0-8N��i��,���o\n�#��I@�N���ƨ\0_C�\\�a��⥋���Q����2m�<�����'�&v�`=OX��x����h�]p�=[��*���fMk%��?7q�d��ض�3v`ǘJ���.\0@\0�+��*7FF��������1	(p\r�H-|���Y�eI��B{>&<��17a�\0G���i��B\0v���-@'�kN�8��[i��60�)j�\$��.�Y�:�܏��!1V^�:�P镻X֐h��*��pk�+�Tl���������i9Q����{ZB�,����@�\"��>_0zH�FА�!r������p�&\$�JN�*�R+�y<�[Y0K��Tq��:ʏ�.�&��7����[w	�L��4_4\r�~/\0M;�E��Cʥ��(�S�it���Iz(�pd\$��9H�2|���;A��bxrv�%E׊\"���\0�����=0�:	\$�y>r��m�.�銐��\\���k% �@^�*3r�3�²��@�f�M�2q�Q�Շ%va�\"Td�+�\"��t��C�^.ezP����K�/�g��#����/ъ0�s\0p��Ep���,�re��ut����H �ɉ���\\\$��3��~�Ӏ�0��� 3	#�̨EVDp8�!�cQx\\�M�B��̃�d��E�NRSo��B��򭻗P�%��\r�v�0�R\n�NM�-Y�7Ҙ���})�>��2�E�`<e�Tm@���X�0�\$�g�ë��-�p�Ơs�9�E�n\$�#)�r��H���)�:���u��'�`\0�Y�����Ɲ����+���ɓfS��wvb�()f.�q�G\nFc0h�-�f�&��u��L�2����fH�]��@�ɡWb�Ṃ\0bӦeZb9�rf�k��ff{_O@/@fm�r��3�5�B��:����3�z10\0ɀ1d�k��&�hr�n� ҃�Ӛ\$����Y��F�͆	�*Y�Ef����u\n�;����/b��	ař�b�p ��@Eq���͆�(��!��v�+2�P\0K�ss���W�0���<Y)�d����g1�X��� ��6����^!#�@f�.�.�r[_X�1fh��k��Y�R�gRXKUt����������?����\0R��!e�D�y���fP�_;vo���>��2���;�v@�\$h���W7��З9�,3f}���6Vg��vܥ�����|�y�3�g���8r���\n3F�M�d\rS5\n\\���m���r>��S(�k�Kk��|ebƜ�5ӵ��\n;���+2o�JW������(!����+�Lę��u&t͓�t�/�������<:����t2�.ey΍�6�t��y�3�_���:`���w�Xfڲ\0�A��|��h�D��'϶Cw>��b���sd�̏S�2Vn,v����̤�/:�z2Y���U���279H^��9�@�\$˥Lu|]B�Y��>d.6l��[���٢���-�9�UL�r���\0Ś�*�k�����b���d&f!�x�\0002\0b�3Ne�*�04\"�͡'B@���	��5�17�8�r����cg��O�74y��z4��̿�16M�\$9��O�\r͛��6�l�Y���h5Њ�\$	5�	�fDͷ��6\$����}�͝�-s�\r��0�l��\0�Bn�l�y�3+�b	]�,v�-a���f��;ր��ٹtg��ݞ	\0�Z ���g���Q�h��Y�\"/+�y�8	�@�X6d�3�-���+��=xA\0�h6�[Ic8N�,�Y�22��O�G=J���#3Gh/�g����=�;C��ʹj�A�kƆy��:vC���v�5\r�Ĩ�I��E����	��hGЕУ�z�\r\n�t+����:���0s�g�������X]��=h|̆��2.�_��4�����?D>e-\0�\"��fc�\"T�zVo2;��B�\$��=~��YFl~hN��G7�2��z*��c�л�W?�]����C�Ş��S>n�H���dg��鞈F&z��3�fU��'�0+ފ}&Rf46d�Y�SAv��\0tmi����G��M�4red�{K1f��\ry�4��sх�9ƃX�Y�j�h��2N�=Z3�i��AFn�#�H\r��%��D�x*nx��b�-+�oJ?=�4�ů���[Jg�'��s@h��G�CJ^zm)��o��E��J�I�٘��V��0���?\0_�xm�0��*8li����`\ns�iҨ���b\0��4٬\\똌�Z�����&�ύs�Z����K��M!\$W��@�?��Jޕ�AZ\r�T�l�̊�F�{\r:�H��e�������x:zt�G�ۘ�Oz��<٪��iͰF�r�����s�\0 K��5}�m,y�+�y�ǘ�%��������2�0�F6S���1a4(b!Ph�P�=��%��� �\$E���A���h�#\"�(6�0!\n��)�]������q��\0�]_�:&J�B72��l��#)�������Z����CK����P�a�D�ш�,�ܩ�Tt�����w5.c�>�Eu�ʿ�?+���/���Gԏ��:V���Z�51�W�1��<&j�z+�g���L�R��ږqceɧ�?[�:v��s�f	Ë�`R�u`A`o��ޠo��\0]���1kF����r	�d��lM��sDn`�9:�˼�⬉�+/��\\�{�%/@-�\\�v܌H�[I��\0��(*\rW��\n.\\^]�����E����z\"�!�+mS�����O�˪���5�t�̂���0�0��g�1�/�Տ�cVV�-Y�+2����S���	:��_j��@���`uy�Q��f��tT��<R!!��U�qL@M�a��� <#vɹ#[Mf%��dO�i���cT�`���3�i��jm��cz���k�45PO\"B�S�h�н�b�6�M+z�1KbÚ��5nN�+zK2���X.	�(�|i�b�c^ӫ�(�i!�:��4a��(	�T.�\\�Z�KE}N9���\0V�R藅KkR���0v�MS�ӳ�)]M�T���ڽ-<��-��[f��l���<*��%�b�]��\0~dLh\0�T!� ����I(!�(TGZ��}�_t\"����lg��]j��AdH�)!B.c��Z�r��z��LL��A�U�s�����&�=f���Vkb�-]l2���U�uY&H5��[��\0u��j����|���Z��Q��J�F�msT�_E`�1��-�q��?jp\nKH}T�J-�rl��VU�#V^��9��>)s��KD��\r��ð���e�7Yv�������Xb��Y��f����5�RP�YX]e����f����['fUMf��u�k>h��A�����Kr���:K�P�x�=���c\0���7��\0�T8��0X�6��>��s+��c���ƾ5��a��c{��x��	j��fiθꮉ�a�M�Q�&\rhG�?�\\[K��Ё��8Ҙ�@ �s���ȝU.��p\$CʶD�;�s���5Dɂf\r��,f�������rɨ�`筍�؇6��H��?S�7y��!lrU\$���ȑy�2\0?��\$�v�9y\"5�Yd��EP.ȍ�@r6Ff=��\\jno�O�NAe��Q�pPp]��U���r�G���2K�����&x�\"8�j\r=q�5�k7˝�Ge���%{���k�۰CF��4�{�l��-����Z�:x6aa�٤�Y�m[2��l���?`uҐ+��0@�k�]���������[O*of�˭���v&�y٠�M��}\0����h��g>lw�O���}�\n���\0��c��٫;Wj/,,j��b,~Dm���	l��!}�{2ޭJA'���\"�	~7�R<�Y��aj�K��\0�Q�X��gɱO!�\"l.`�p��wS���=~���v�k8��1ZU��L��3����+[����z�vy���w����`\\k:��a\\'WΘ��=�ʝ����;Wh�̵ya\0���1��h��lroԱ�����8V'�Ii7�:�+Y�̽g[5����߭].l�Hآ���sy�R~����]�@�\"h?��d���i�#��iف��gR\\�����l�ԧ�g`�<�f����X=��	�\$Ͱ9K� �3ԇH�R.��g��r��L�0�O1��i��D�k�	\$l���4�yd4`e�ʒ�,� !��T6��]ٝ�ojf���=����T�/_��\r�z��d�<9<i�����ڽ��h����F6K4�ӂsf[�[!B�=����ͮ\0	��yRǷ�hnލ�7��:j���:so���aF��W��\r��D��m����:@�_T5t�[���֢�M��&W��O�X���/����斵���4�v�8XJvt�G�E�����k٫6j{�R�\\�����4<�����<���5��,SG�b�n�f׸�u��?��ec��r��u��F�ӕ�e�Mr��	k�����iz�,N:ȧ�X��-�s�-r�����h���m.]=O����im÷�*V\"|@Y�uD@,f-�'&���`u��l�t X �mF*¥���&\n�t�C!��y��8nL���;���Ki�7T���Ѻ�����?t�N��L�*���gu^��6ἒn��.\0�ut�_��7`���%�v,���!R�W?���:=@�*u�a���3�s9��6C�\rr�o���t�����(���}}�}�5h0�;�n�N��T76���ۺ�������':5eu���H^4^	o�yC�TT�\0���z�d��<�	�DK-���A���ʑ��Yc��7\$��`\n�[�*�U����k\rìcz)��:�@��\\Ø[X�y�RZ��k&�#�oV��}��u����]Z*�M�36����9��Ύ�Ry�5��\nڍ�[f��U�m��m���C^�}d[ϴ4>�Q�����Q7���5�;1c�x�ƷVd�k��{(D�����׭����M�{�7�of֑�_n����F��1S[��X�q�����,ֶ��p�]o0ԩ}��ǹs�'j �5���1H�Sz���g\"I�y�w����w�oZ+�W.>�~��w�,�Ƃ �a�]\\zq! ���D@�[(}�u\0X�nE���l�,�7����4<9;]��u��I��<���Q�@��X�{��]{~a�\0h{ט�:㖽\0h�����sܻ�+]��MkZZ�\0f���lZ��{��Ƌ��ŲWWn��R)�@���9��y�}m�����9��y�� ���4����z~Nݘ��w�`(���S������7��r�3�#y��5��D�coK�-�d\"�����{\0<�-��ݛ��wދ&0ogn�K������Q���G`cy�*w6D��@�ׂ�l6q#��6ý��\"1���m��PM����#R2�)h�\r�v'�Cۯ������Nڨ] M�h�� ��\0�.�L3sb����:j@�ŮY�Jk����v5j��۲�H �^��B��v%m���8�\n6>�m���d\nz}��!�L��ٶC����!���M�o�F�\0CŶK�F�3�LƂg�8��Hn\$-��D`�	=��y�WH�V��X�g�ݼ��މ��g^�Yq8�C���'p\\�[_�l����[`��lR��8��H��f���;ٸ�7ٱ��f���5Y�x�ޝ�?z��b�<xC�tq�u��Ny{q�l�/}a��\\w�p�᣽gn�m�9���s�E�G=��P�j�-��·�o\n�,+�[\rl�3��qЧ���v�m ��gn�M�|����6Kb������B6�a��gV�^�|/8i��-l��g�sHE2۱I��5�O��\0�{+\\RR�a���	���\"�:8�����_����|Qv��bۧ�cG.ϡb�6��s\r��kT���%�N������k/����v�n���H��ۆ��5�t���'l��H�{^xp��M�����8n���;��~,�4�6���۩_]Nt���O��*q��Glf�2Y,x�X���\$\n/l���e���!�;�zg��X8|��������6�������C�G���etKm�ԩ��A'L�NѨm��L\\w��-,�Dwa1����.��Q���K��)�mԙC��k�\\H�o�\0���(_�wk!y���u>�n=˗8�����@���'G�Q�O�ǀ)�>A#yq�e�u�~A����9��\n��A�Pyn�ݖ[u~�.C�ݱ�6�L���\"\\��7J�;ӣ�OYS�]�9s|�ܭ��wI�]�r\"�8Uшe���\nKy�r���y�����}��5u�����`�54k��2�H[TK.>E�w'm�\0���7I|]�[�wUn�ַ�k��#-������ᵺ�f�&��@yr�q��eS�ܸ�A�=�fy�-w���C��r�.��tl�0;��5�=v(j`�`�G>:=5�*\\[��co���!>NaځB�k������x��{�	IYl�/{^�p���Sx(�*�~M�����M��Y7>W[��S�ᆵ���!y`�1pMO�7`�,p���e%�Q��9���\0��/��o�I�沜T����m��豜RB�rC�|�\r��;,`���\0>�V��p\\�B��Y\0dY��cp�.@@<�1ϭ��_x��.'5�p3���ז�OPy=�X������+<��`r���93g��,�v�oo�!��w6�3 �w&l\0�u�	�w&-�`�2<�8Ə�Ȑ6@�q�)���!,-�3���h)�K�9AYT��أf9����cw���h�h&�I����k���چ9f�4U��w��2�A�9��<��W��,.d+������̟`#.e@�S���J�w5Ni�9���(Ao%�����Ѽ\0�qv�#�z4�?�g����9t��=�W�3��&������V��ͭ�V�\r�8y�ԛ�����`]�š7'e�ܣ����|ȹ�а��'?3)�\\��S��u���5^o����s`�KC����z�A�q��Q�h�s��w.nE��d6�}�8\ny�ne����'\r<i�s1��;��g0�c����k��^��tgkFQk���tޮ���y&�@ܛ�/;����	<a�ܵ��r&c�|<<j8K�pE\\(����q��s��Tvnb�� �*�o�MS�<��v��t�[�h6���\"Ou���KKpf9MR�nD\$��ʧ��C~b/�<�9�S�SW�B.6�n� ���?�k��%�!w��\n���ǘn%��\n�nV��sx|��9����ӽ��D�a����n`#��B~���ܷ�����`���M~=�5�葈ߢ�Fމ�����r襳�/B7m��q��#����s����qg�ٳK'�ڽHLu!\\�a��l'&��{mw��������v�lm��P�{�7̨z�v��ۏ�kT6ܱ0���ĩ�/�{�?\$~9<>����A�;��M��Y�� R�<J��`[�I���V��o3,i؃8�q��ѿn��}�[���������=�O������op�<a��c�0F��o��^0\0��m��Ӈp���xwt���ko��2{��Ƽ���Skp=����o��׫<*māy!���ց���+�m�[���t���n�Ӎ\$��t�/͛��K�?\\��⳹�Ak-�[�u����#t �К�F�,I̓�*��X�����e��b.��_[�I���c?T�M�O�aKh�T8P.s��	��8R��ˍ��\rd��xq����f+m��7:u�e���n�-�8��o5ޯ�/x�W<jJ1�����o�tq͏�uRu� �Zk֫_�4�k`��ϻG�3ެ;�+k��;��z��=Q�ⷴfZ�խ/�޴�\\ɸ�#�G��{n��m�i@9�����O������m������^�� D��a-NC�B�����g[>��	����+\"��z�?�[&mޏ�D�f@���>��k�Ns\"ֺ�m�lE��cly��)���ω\\�����d,�ׅ��ZBӌ�N\nlB���ߟ6������q��x���2�H7�r%�5�?n�S��TTȶ��샐�1�c\$݈	t��U�0�:�o�zkYm���~'많P�]��sA�u�G4N7b���:�b��\\�>�n�9L��I�����<���j���i����GF���h��i�/�`B`�����Q�x�_a��������G��An¥�,�'���߲�����z+�\"Oؐ ���\0;5���N/�&c��}ş6����{�χ��w��7��ׂ��7?.Ш����p�{Ґ��6��p���H|���6���Lû7�Ԁ��- G:�v\\�4\r��]z��A�k���i�b�����Ͱ ����\0e����m��Ѷc���,?�9:s��7×��F.�=�z��u��'��E�]d{-��ʻ��\"}s���0c��ۀ�v���Kc �·��#������4�3�\n�𹛃V�(s�2dƇ,1�I�g�1�d8�ݗ��ꛑ��ڵ�\$�\\�v�D4�Yop�:�Ct�f�C4fu�]<,�lC���b@.�\r85��W���g��G���[��[��N�p�/������.�7#�F#��b7fL���X�׶/�Ӡ��]��wE�\$(	�a��9��l`���㑮+���\n%{ۀ�Pf�n�q�z����vY~�]�;�p��*\n��(�.W����pY�����;����Q������Xm��!z{��l���dGkQ��vKl��?��>��Ġ\"�����(p\$�,\r�Bh=�\0�\0�0�,��|'�h� ������@�(�v8��>�JY�9'�/���������w�����{�R}�@7w��[{v:mA�����#����4p)VQ��0oF�B����	˼p��-rP/3���H��������E�MDR�Gm9����@r�B��\0�u�����.��xv�|.��S4���9����\$F\\o(����D��+N�g�6����=t�\n�pHwN��o� .���{���r��ׯ�p�O=R J����T���R��!Ƥ T��A{�E�	�<�@~w�pʆ�s��ɍK�G-\r��Z��t��c�/��G�b��E�P:��G^��pR��ʩ�_���� g���kM ��si�\$����������|-Q[9�Is���#��d��ݴz1�\0����?H�q|��j��	e���a�����o� n�~�>u�����m^��ư��͑�7��b>��;`�`�{��1�tC\"=�S����燎�\\��������f�^�H�Y�\$����5.����j��[5�nG|R�o����͠�R���B=rz�S����e�N��1~4�����>�HIe:��x�ҹ��?���p���qt��#�_�>c��P�HQ�.!p����S�&:���ۛ��&=ڡL\0��񭻲7D�[�<Zta���/��W��p�������eg���=��w��@;�\0��8\0�����h��>;��03�N���0���r�����c|��Z10-���r�=�M�}\r~Ch�Y\n5�J/{�{k\r��hm\0D�Io�5�^B��?�U��R�Ky-�^H`�'§w,��a�g�W�����Xn {���,~P<�yX�姽��O<`jx�\0����P?)��d�>�'��5fO�Eۢx�xG��G��?Nvz����Ӄj�ϼ*�G�mV���\0o0>0��^\rڻĻhO�\r{E<xm��۴h\n���&�>a5�}���\0��<Qp��<F��Y��)�x�qB�M����*{O���W]�.����Y��h9���_�gL,�>_x�T�ڳ����M�98��	w9�/Ǉ�{�[:yn��3�~l>0�\rx��%����l��7x��l���+H&���\\v�t��}a��g���k;[�����L��m���&v��?�㎁y�������O�C����ٻk:Jm��,\\�%Nl���A��eԙ�?2�ۢc�������ү�.��+���9 �33���[��X���i�.>YOG�N�?��\0X�98Y�ߜ����.?�5���{�9>[�l\\��<���j�(���@�����j��\0����|�@di놪��XjXd3�U'`d \r�<ot�Ђ��i��E��g����LDP�M��r=G�[C\"e�6��7�A�O��5�)(M������v�O��>Jx�����^ΐ%�. @=�6���=(\0�");}
        private function printLogo() {echo $this->LZWDecompress("�(���Ph\0�I\$�!J\0dC�\0�4<�O�a�c,�D\0?�2�N���N���bK؀����΄�i��x�00sBn\"I�T<6����P+Sc����t��v�%r\$�PK8�9��4�����8�2��1\0.D�Lc@��8�Q�\0	�#�b��D\n�H/d3���/T���{���U��qj%�Yj�����w��@Q@(��\\���T���m#m�������JR���J(��\n�&hF��@�Oc����\"4J�wH���\\X��*�5�h\r�Yj0G��)\r���D�f��;��Pv\n�X�WbQ�i�ĸ�h�\0�;gtZ�b��<��q���]��Hpq\$�T�ǐn�B�Hv\0c1�[��f�&��(�%���\"AZ_Q*#��h ���zbq�)\rЬi��	d6�\$٤\0\"hr4F��'p�^��Ja��2 �8NJ����gpv;��i@s����u�ai`:d��r�@)�A&Y�*���\0�Z�E�,rhpT���\\U�fy�Xdpn�C8�I�`�Aa�\r�4h�8N�8�\\��X~搌J��A�]	�^\"�)�\"�,O�ɪ+\0\\���y���-aq�a1��c�&\0��ȰQ��x4c��\"_�:]��@�T!�HnW�a�`gQpo�m\$���i�0x\n�ќ~�`P�P���\n� d5�fq::��|9�n��}.~�8H�\"Q�X����F4\n���w��Sd`r5	É���08���*FA[��._\00023��Ly��p�]�&��x��h�j����\\�g�>�!A�:���R�F�Ld�b@�j�q\nj��\n#�BIZz\0fIf���&�x`���g�#�'Xl0���b�Q�h� @t p@�#�l�C��U�(p\0����\\p@\0Ā\0B\0g�@�1@�l\nCQ\0Z\0C��\0he��5���,/�0t�D\r#�{���\0]\0�(�h@��H9 <\0\0p\0�~	Q����H0\0�����#�*@H�b���\0Xd\\@	�D���(f\0q�\$��\"�_1� ������=��\nÀn�Qz)�Hncqb��ض��e	P� UB�(	QT�pb��O���G�u��k��z0=��\0\0*\0��\nt\n�`4A���x�q#�(o*�\n��\0*#XR�a�;��\"QP'c�\0��q�A�9�P��w\0�y\n@�{������P`���@�/�h���4F���`�d%�8��t�00\0��Xo��Bxx\r��x��!���!�_\0QR�H�#\0,@��\0#8\0��2 ��\"\0@H2��\0���c�9\n�4X�!`��p\0��\0\\<a�!FX���N�!v\0�kC���ǀ�+aA8Z��8Q~C�x\n\0�g	1 +�6B�=�a�,��\"�D�8���`�X1�`i!�:�1��#C|�q��0i`�f��?\0�8\"��a�Ǹ�8\r`\nT�\"�4\n�?�Ћ\n�Є<���\rb<T�;�� \r�\0/���`��@\\��\0�3�|��\0�K@R�4	�h\r1�8�2��`t1��1�Q	،�� �\0#\\\0� �;C��c�P��1�s�x.��7��r��e�a>5�X[!6\r�*����tz�`�,�x=A\$�������`P7Ÿ�`�x\n'\$h��\0A&\r����W�����\0hiD@Dw\r��:��7\n �'�@���`,I���7���	 X;!lO��7����E�*�\0�` ^Q���8�@�	`r>�(�TF� \n�(���=��*� ,�6(��C`G	a�*(E@0h �)�	�@�2=C0��H\".X~\0����@B����at=C��b�_� �3���\"\0#1�3�h��q\r� :�T\\��6 \\�P��|4\0(��3Q\\4�x�� C`��H=\r\0�b��v�(eQ�m��\0��\0���H\0'@�1���)��c�po�x���\r�40�f \0���(�0X,AA(�aN�\0�����n\0D�j7\0�(�<z���0J��XD�`�8��\0��`�-E�n!D/qE`�aD�1l �\$�`HX\$�@E`Dc�����W\r!�p��CX��`s��\0�\$\"�@PB\"ǈ���Y��>	0q�\0@�qB��n�n\naf.A���po\0�An�� � !F�z�v��<\0����`���a���A���!��0�>\rP�� D\0004\0x���\n�(\0z����\0!p�X��A^�����`�a j ,\0\0���ա�	�(�X\0�A������T�l���P\nA~��Af��\0 ���\0g�|@��@b��\0�\0��~\0&��!��J �!�`X`��!����\"a���	!�@F@Z@�H����`��(\0!�\0�n�P@6\r���6a�� � �!.��H\0��	�\0!h��a�a.�\"A` X���}�� X��FA�`��jM�� ��n`���\0�!�\n�����jB\0������(�4��a�\0`!!�`D�|\0>�\"�6�~�lela�\0�>�X�TA�\nA\$��z���0��`��`�\r!���`����|\n�x&^`:a4	�N�:�@����A� ^�\naR�~����A!d��\0!�\0�Aa��4��`�����p��A�\r�\r���|\n��\0\0@A��@8�6`����\0�l��:�.aj\0����F���:>�j�A(e��\$�A� �aA���`=@�A���|���0\0��\0�	`�\0�2���\n��`����b@���8@�@������J�^A��a�a@�\r\0A�	�H\rA\n��\0�@�\r��a��@n	�\\��p\n� �V����d	 8�(\0r �!d.�\0\r Dz�X`�`\n\0�a����x�,!\rA����\ra�\n�n��F���!��8�A�\n xa\\�:U�����A�\0`T�\"a��f�<���F��@\"@`���� H�z`H\nZ�������� �����^a��D�6B�Z�6a�����D�8���j!�`,���N`�� �f`��*	����H�<r�����bA�@��!@!� �a:\n��U�`xaz\rA�!���`�8��a�� �\0�����@.A<\0���0�A����0!!^������ҁ�a���`�\0��H��\0A�Apa�ah	�b`��:�Ab3������N@����Rq��d�V�����8�!4��\r��\r\0 `�a��H��!D��<�\0J!�\r\0�a&A �Z\0�!��0\na���8!\\ ��!����ƶ!\$!��A@a\0 � ��@ ��(�0\0��`\$� 0\rB��u�l`��\0�r��`�a\n�\0�b�a��\r@����� &\0!4�c��f\0!�A@���A4@���\0> ��2���b��\0�@�d��@�`d�\n��@�������jx: t\0aj^�*��\r@!u����H� ��s�t ��J@�A\0���AN	A`aL:!6������:@����*���`���!��*	�@�0����^a� ���@J�����x�~\r���PA���\n\0d\0�\r�\$`�\0@a�\0��������h�t�@���D`h\nX��4�A��j��	�H\n�Ah����\r \$!Z���`� ��\\�!��x�z��A��\r!^\0\0�`�`���	��R�P��!����r��av������\0��>@�9�\0��������A0\r�l�Vhna�a��Z������\r��^��	!<��`4�G�\0B��@��, ���	`��V��k\0� �`��\"�xa�� ��\r@�a����&	�����F��\r�\"�!�\na����\0~aL��\0nCv��`��D�8\r`�AA\"\0�B��(A\n�n\n@v\n! �!B�Ha�x�&\n��\0�B�� T!����B�\0�\0���L@^��@��\rat!�`aV@@p`���@�������a�̀��`|@8���\n�z!\n�`�:\0�d�&�p�����p���@��D�`N\r!����\0��,oN\0�\0\0� 0\0T!����2\r�>�D`�a!��*�v@�\n�Z!�A�������@j�j����*�`\r`����\$ɡ��N!v�����[��`�\r�� ~�d��\0�8`R�r�x�j�\n�J�&\r�� \0���n\0���N!n\0\0@a|�\\�\rA^� \0Z�A��n`�� rAp�F��\0�	�4!���\0��\0~@��\r� �\ra����\r@>�~���7�<`�a�!�� ������x!�\0���@b`�at-�aZ��\0��v\nA����2A��0�<a�@j\0����j���@ zA�a(��!Z��!��\0~@6��\"�\0D������� `Jl!�� \r�\0\0�!��r�n\0��aV\n� t�\n`^�.!k/,\0���\0�An ��!���c�`��� ��	a�!��l	�<@0�\$�s:�8�� � ��,X��4�\$�*�p@���>�.`aB�� ��~	\0\0\0�!f����4\0��D���|��\0(A�`r���%`<\0nPS\0��1�8��	L���\r�0\0�\0�\0� � t��\n���f 0\0� �X���x@�p������y�@��z����� A@}�@<(Ar�L�4k*@b\0\0\0 .X:AD�;\$@T�7A�\n\0X`��#@�\n�4x��X-AJL�@P\n\0r���\0��� @\$< p�i�@��{0�I�4��\rA`@4!����\n`H�7��0{db�(,@�P`,�=@�`b�@,h@l\0?��@	��,V�\0�p/���w�\n@�����0`c�\0���H\0 �h6��\0 ��@����`�`:�����Qh c�A*���`3\0@v\0p\0\n�1����p �����@o�x\0������@�� ��)\n�n��\r���*����h�8 0��pm��	��a	�G��\$\0�`-~���0@>(%��`m���\0�,@��<�T\0\0OP3!R�}�@�G0 P���@fh/�\n\n@L��	M@��i��	���\0�pr\0��\n�%\0��*�@�x�8 o4���:�pj�L`��@n�R�Pb^P*\0���l\0��&��\0N��\0�=�4���0�\\ K�;A��0�t��>@�\r4 ��\n	�< �@\$\0p\"�@�\0�7@T�E��\0y�6@����8��@	��\r����2�\r�\n��3\0-b�s�8 �� �#����\0>A�\0Pd��`�x��]\0�\\\0�`��  �H���/��\0G�\$	��P!�a��C��d��\r���`���P\0X�a� ��b���\0(��@A�����t\0��D������M�\0�7PA�0]��@�8@�`!�T\0Mh�@TP>�	@�H%.�9<����Z�\nA�<�@@��*H\r�C����8���N\0�\$����b\n���)\0A8P\0��6���t�!@��!���V`H����{�l`��AdK�P@\0Mp5����`�\0x�	�,�t	��� ��	��D�0@�5�P`O�+�|\n�}�<�\0h\0vЁ��� A�:��`�('\0 �a����x:��\0h\0��)��\0����J !��)P!\0v	PA�Xo�@|�5\0d\0�)�Z�6�X���#��q� �\0�4� Pu�\$�~�< �K����28��\r�!�@\0�\$�PV��\n���� 	�t /\0p+�P c�t\r��Ғ�XH�����z�p���*�j��`z0:���Zh�6�;���I\$\0�&�efh g\0p�����p@��#A��3D�@X+�h�?���x&�.�\0�P��,A��)\0H��7p*�d�h\0h��T<���\n��\0@R�1��	����E\$�2��R��\0\0nX,\r�рh9\0IT�@�:`x?��T�AJ� 4��\n`)�)@���t�&�@&\r�\\��q8.�^��h�]h-��0r����?@l�8\0	OP���b8@��@���d\0�c�A^P�� NW�p�<P&�\0�\0\0H`����A�PG��\0\0@�9��@");}
        private function printFaviconIco() {echo $this->LZWDecompress("\0\0\0@\0000\0�a\0�\0\n��\0m\0\0�)P�7��\0�B(\0\0�0�\0(�A��&!\r��S8��m7�NgS���}?�PhT:%�G�RiT�K���}�_��3�������ۉ��n>���y��s�O���T�Q�w��(�}T�3w����r���ײ).�4�#s��iv�.��(qwO/j�eM=kg���t���;����z�D\r�b=�Q�Q���-��C�]�B�|Ms�	΁�I�6+����X����]������⣣�Pxx��/\"RY�^>Znw�����4W�R�a�+���XI6��~~�摞|��%	0z ���		�8v(Ъt�ˤ.�a��읡��w�\\9�h�xc���ǐL@�a!\nz��@�Ѹw7/���2n\n ��+&�TI/2px���BK���r�!�� �� �\"��\"�0�;�1K�QTYF�iQ�PAa1zdQ����QI�~���I�q�)�؞\r��Jo+��}��}�a�#�g�BxrB�6M���C�i���Ph-��`h�;�hZ6�X��X�x�'�B��-�ǰ>H��12|�)�v���qL���(f����To�c��7��0��5b\"�m\\WS\\*#�ؖs���3gqM�E	f{&�Z��Za��n{�E)�)��h?�A8�G�1�o���g�>K��4|���s�K[xSt��n	���&�F���HTp	y�#���V�Ӈ�1�N����mG��ը��w�񎰐E���}�[�BI�.{����\r����_�G���^Z����f���PW�l[&̈́�!��)�G9@QGI�K]����I�>��\\G��\r�q�\"|����G��d������ω�G���ǃ�#x�uF���w)���f�� ���r�;�E�@��e������V{���:gP8B(2A�*q���iJ��~��L>\0�#����6����`�Y���;`)E��z=��[A,A@&ǉ��0d���?B� q��XN>��*��~�?G��(���� {���q�ut'\"B�� ]��E&r�>(��.*��C�[�r��5��9�:������r���o#[y*b\$e�Ǜ	��u�	����X�ܣH�\r��|��r&��и���{�	\"P�+}��(=5\0����\\��<\"��SNv.���\r�e���\\�@#�zZKinP����I���c�G\r4�W��\n)�@��	`��5��\nX(-�\0�p�5h�p@���!Px�8��|y��7���#�\n��E0���\nL ,��SC�{�B�:��b\\w�+Mi��#�W	��+D���|x��B<EX�B�R!T)����T\n��+����U1�9ݽ'�����%F���J���\$�����Ś�]��y�U�W��_���V�X[\ra�E��V\0�'�0�� G��#�ŗDO���<���C�n�^5���xS\rA�)Ƙ�Dp\n��8EH�B�f�1V2� �C�V���+�0��s��:��\"�r�a�UE�nb\0q\n��\0��D\0��\$\0����a�6��b� \n1j\r�\0�bp\\��2.����I��#�E�#���D(�	bd��2�x{�D;�1GX�*�^�`=�0� ��h�C�]��com�C�V��r/�;����xD��BL_�Q 0B8�a\$D�P�!�8Mc\$'��C��\na�gL@.\n8�0\"`]���1v0`0v	pH>�8˰7�l�\\)��G��#d���2vP�YS+e���2�`�Y�3��R�Y���^;D`\rB0�h�u�c�~���,�	7�䐇�2~Q�yW+圷�r�a�y�3���:F��@���=?�GP��Ia�3��6��ܫj��,�\0�	� ���n��I�M,R�����f�A��v��@s	�J=Fര�tlQj'�PɁ\\�y�����ZSb���<����\0��:���\0Dq�1�'�h������=���#89�P��wF���Il=+�ʠ�B�x�\0002;s� �|�H�1�\$�����w\r.?G��c�zt4C����θ����T�X��1�P��Ch��E�:h�`Tn�P,6��b�0�A�25ao}��xj�^+ [֙'%�����W<�{��R=G��܀�}�8��{�\\l�p25�ǯ@�p��;��#�t^�_G��Ctl\r�P.0eB���{�u���,\$`� 1�gi�Sw�{<<����H�_P��|���\ra2Ɛ��<N��(A8��l���AN��!��`��a�!a�\rd ��h�@\0x�l��������6`���!��A�	�\$ݮ~�\"���a �0N�OV��^ 8a6���@�>!�\0D�BA�`J�L!x XT�h`\\Z�Z�bAb�0H�t0=\r\n�@������\r���*a�a��������pn�+�A�@��,!,��p�	P�	�D!B��`LAJ~�TaQ���Xb�d�`�V@m\r��p� |��|a�	A|!��sA���!��4\n/��z�������n�\0004A4p��AQI�QQY�`�e�mu�|�r�o�vaf����c��B}A�����:�ﺯB�!����\"a^	a�\0Pa<��@�Da��O`S�W\0Z�X�j0�AR g\r�X����!��z!��B�A���A�!�al��(��a��!z��@�Z��`l�X�v�pAd�t\0����\0lx��c�#�A������N�)��������������K����N�۪0oN�-�53U5d�&�]5�a63e6si6�m6�q73u7sy7�}7�83�%\0\"b\0Z\"�f#o5��CxՂ�*��n���na��\\����T}��!RA���(��K��a�a, �!�	����2���c6	�@��a�!�>��]�T��^�!�'��A�A^����f& �9 �\0lD@h��C�����P���r\n�l!���v.!Xa�	� @����B�la� �\r���z����.�����DH��Y��Z4fC�&h��BQ@n��!�z�)T���A�	Ğ�|AN�������@�u��\n��@�\0�\r��і!D�!��F�����[Dr�G�Qe�E���a��H��^`8j��	`P��`����D�	��1J��>��&�X��\"�� ���E%��.y�*A���O\"�Ouv\nz	ƳX�������vA�B���n Je�\\�:r�8����A\r�'5�O����tU�	��h*��!�-������*!H@P�A�bbob�yc�X �^��l��v!��4}��z��a��:��Z�R������'�mb�3^�w^��Y�\n�����w!�a�	b@0�.��@����(!VA�	�	�@	�@������ck�}J��V��!H!�vZ֞	���ban�����\r x �!�����	��\0���\n����\0���\r������/f���a��l�hi>��g�'�t�����<!�{�v��a�w�a�-W�f�]Ө�p�.�4Up�i9��~��~�:��fl�����,oj��a���\"�u�a��a�x*A�����E���'�N��!*�� VAD����a�A�@��~\n�X`���F\0��!6�����\$@�b\0�!������`k��!���!����\n�8\0t\0a|A �,���0�	�ZA2(��! �漸�a��\\a׆�!�!Z����~�H�������\n\0��a\\a4(!�T�l\r8�\0��`!����-�ߌ��aT\0œ���C�yK��R\n9W�����A��O��-��\"�����3[t�	9=�E�����Q�YX(������'�@��\$�(�����ْW�'�a�A����8�ә9ٝٛ��R��@�!A��������l���a����Btۡ��*��!� ��Z�\0�A�	X�	�)��&�a�!��4_�&O\\�2A����ban\n����!�᭬a� ��~!V�~Aj<��2���!6\0����6@ݏ@������(��a�@jN�����>p����aLl V!X�X\0df�F̅��!Z��a�n�:��Ꮐ���[��� ��AH����\$�� F!J�x\0V��AZFAN@h�n�\\�����!�'�r� ���}(�A�������\\���t�b�v��������(�����+�^)��2A�A������sD5S�:j�w��)3���a�3a8�8\"�  \0\$d Saǂ^y>\0~�\0ad�\020\0.@\0a��\0��� \0	�6�\0b�\0�<�:�\0���\0�z\0D��\0�`\0�< \0��\0\0�Ȁ�|�A����\0\0�����>\0���\0\0���ڮ\0���\0s����N\0*����\0�@2\0\0\n�\0\n,\0\0���\06@�\0���\0� �\0\0NP�\$l�C!�\0&�\0��\0\0�\0�\0A�\0\0���\0�P�\0�X�\0���\0�:\0\0������\0���#�� 5ˊ���ޡP��a��\0��ȁf=��h�\0�^܄�E١�ȁ� 4\0�}���}\"af�\0�� \0W���*�\0=��	�\0���\0p!����\0��� �����˺*]�\0\0������꼦*1�A�!ܮ��|Ba�@�\0\0�? \0Ą\0�}ja�a�0��/�\0m�]�~����^�����\0\n`\0���l\0	�\0\0a�`\0	Oh\0��P\0��!��a� e�a���!�\02�~���\$\0\0<h�\0�)�v�*�@\0����x��\0\0�\n]���ف�5@\0��`�\0��x\0\0F]\"��ȍ\0Z��� 1�S��a����!� <A���\n\\���!a��\0\npL;�\0[v�Ȁ&�H8�P�d`\0�@FPM����\0,@Y�!\0u�4`\nx\0�\0�0�8\r�\0`@�&T@�X\$��@)��@R�\0����}0���\0d�\0H��	p�`�\0�D\0006	��`�R�-�T\r���@n�:\0��X �P=���b��`�@(�D�	��P��h`�X�1\0D 0@(`7��`gt��\0<А`A,`���\0xA\"�&�\0�@�?�� \0p�&XH@`\r�i\0�	��\0P8�@\r�Q�`����@�\0��\n&�");}
        private function printFaviconPng32() {echo $this->LZWDecompress("�(���Ph\0�I\$�!J\0C�\0�4<�z==!�C9�A�,Q��1�\nD�A�M@!!�z�:�!� t���	��Dy�dr �N�(�t�<p\$Np�XN�\0�I��<t\$�H�w(,(��e�Z*N�H��a��K�֨e�u���Wq�L34���%�QbT1:����\0bO��\")\"�Ԧ�n�!%J��%>�ShtHlm(��\"a��b��I�d�dp�c�����2���l1�͌!!�������fY�݋V��n�}+���(���-��eL�?	H,�� ���t	����h�F�`^�x��h:D�0U�g�F	�Jc)�a�@�|c\0� z0��mg)�@�#��\"x�+�YX�)�#���~Y��\0�Y�%�~)��y&\"�@X�JňLb@\0��q0�g�|g��)|��b�)p7��1�.�d)�bFA\ne�x���X:a�ax��G��[Cy��Ⱦa̦y\0+B�'�\0	#\$�	�`(���aFO���M���OᐬL�&�JK�\"�pa\r8���@aŸVv�D\$H���J�cЦ6��&�!\0��r�aZ ��xC�q�f�ar!��8Q��Y<p�F�F��l0���G���n�AA�m\06��ne���tl��Du� ��s�@\n�0~fP7���@sD��z�C�0=ch�V&�@k� �p\n��v#\0004Xs��i�c�r��JG�e)�\$����0\0�r����L�x<3�C�\nR�\0)J��8d@\ncɎY����P�ɠ`�0v|�%p�=DЬa\rFi�@�&P�F���q�p�_�#Y�p��F�)F`'`2\$��)|�X�/C�>o��X����\")xPgFn����l����d�D��g��1tf��K\r'��[�#������x�44G��C�D��x/��u�\$9\03AH�\r�8��|&��-�p \"\r��)��t���;���\r�,>��9�\0�a'�>;F	7\r�	4�LX\0ah��8��HD��F��@�`��h0A�@�0�p}��'��;��X�p�P'`j ����=����\0�h}��*��r�D�4!�0*��\r!�Ƙ�B(�f8F�	�@3\0���,7��?��*\0�hE�<�(\\�t	� .Ű���p66F��ô�Z	�\0���������n��K\0D���N��0� �t�a�\r� �bD|��DH�k�\0>�{\0 �>A�\r�c�	\n\0(�8/C@		����\n`�\$q&Ĉ�\ra\\=�!�E�\0�\0\0/\0��xs�A@���\"�Ɛ��\rF@�c�]��9E���Z\0�@(��\0��Q�+���`hp�������<��H���`R��T)�h�^ �3�>A�w�`J��#��I���a�\r�\0�(\0`jb�T�^EX�A\0��1����?F@��\\�p�6�@�`F��%�d4�!�+���  y�1P)��b4�!n�0j��P�@Ƙ�<?���(B���|��/Fp�!�p�����x�S�\0p���K�J\nE p\0#�'���\0 m\n�!��\r#�\n�6:��\0\"8b�B>���cH]�q&>F0W#l�!��8!Dm��}!�:��*@d��D\n@�	�(�\n��~�����`L�<\0Ax�A�����\0b��7E��<�1r��M�Tk��<>�X���D�� ��:��Q�\"E���}��;�0�� =�.+�����-��9���\r�0W	1�-Ÿ�\r��1bG�S\r�L&���2�ȐC(C�hC�wk�D���@<6\0�+CP��\0j\0001(GExr`D �aU\$@H���j�������D08����R�臂P�B�t��:PtÐe⁔\0 `��-<��NF��5@���`��0��������6���C0y���0[�y?)�|���\0`;`�d<+����w�9��C`e�<��C�td <	��<�`!X���X�	#��\0000F�pc+��	��&\0@A�;�\n��e��1`����\0\0J�\0uy�<�5�<\0��� �A�A\0");}
        private function printLoader() {echo $this->LZWDecompress("G\$����{� �dx�l��\$��Hr^��&���r~��(�TT�0[)%\niB�P����I �l��س�h�n��ڣ��T~�g��h22��N`3K6i}��11�ƆKL��j��3�9�yg������B��(��I �j��-U+m��n5��i���P���\\��@Qp�Ky_9�\\#2��jSp��}(��9�\\f�S�>Ns����U�{,��wJ��E�(�� Dt�HΤ�)ԝ%:�/gc���y=�η����x���';���a�ج��1��q�^�w�դj����{���|�g��}����~�Ќ\"�-��5\rÐ�=�Eđ,M�LUől]�B�bp�*\nb�(��\\\0`\n\0'��`\0�\0�'�\0A�\0 @8\$�A�\0�T.�Ba���F%�AbQ�X0�G`D�d��cA�Q!.��H �E �%�b)D\$!� %��\0T+�F��Q��a\$.02eC9�O��`�@(�h��&%�ЌHG��z��\\�0M�0(��\0n�D)�k��fm	���?��\$X�V�a�=�	�F�<��\0�\$E(�\0��@�a�!<�B@�F\"�	J0�`h)	c\0��cA|c`�%Q�R�����g0X*��p�)\0)���\0	D6b�d{�#���^7\n�I��p\0\0�� \na��%���Fc��Y�0�c!���\0\0q�\0�p�ZH���E�n�bXip���������\"�,�h`0� ,�!\0�A�\0iÃ��b��\\\0��Z����2 ���N@���#�\0Z���\09��D	�\"�aYp`������&��(,k ��&o\"�\0��\0Ab�;�`�<�0�@\"-��a��<80�a\0:Uh\0\0\0�X�` �p<��&����ـ@&*\0\0a0�\"�<C�`8A	� @\0	 �H�l'\0���z�0��\\��0��	0�-8AJ\0P*�+����ȭ-��`�@��B@3���+��c@o�H'�P�\0�,�@�:�@s��0T��W��b\n����C�k��/G��c�!\0��R8\0�&\$�A�:C��J�+�\$�\0��KP-�D�2�K�/�d��LP1���3,	L�'3����LMP/5���3l\rM�77�����NP?9��3�N�G;�\$���OPO=�D�3�O�W?�e�PP_A��	4,P�gC���LQPoE��4lQ�wG��!��RPI�)4�!RЇK�%1	�#��?	b��0�x�!@X�p�.��R@�)��fh2\n�D�A����Tm�6�hV��+\r��7�XW������[½n� �+\r��7�hUຽL�32��)\n�8���À�xf	��@q��8�hNAJ���8�Kllr�9�I�\0\$�a\n�8��4ڈ��9�pM�4'�aG0�\n��Dq�!����lt>�� CE�\r�4:�G@��C�+�!m���\$CQ!��hG���\$Ĉ�\"dT�D#�0���^!d1B��A8[��-�X?�\0��n2�:��`Ql/	��&�И��d-�6�|M�7���@�[��t-���a\0]\n��/xJ��'��nF\0��a1�1CP���c���2F@}C\$A���\"Fh�C8d���2�\0�B�h��V48���f�Q~2�MC8c��������`��/�����u�|;F�� c���3GQl�Ti�Q�5� �#�o\r��8��C�pQ�8G8�c�r\r��9����u���;X�ôr��9�x�C�x���<���c�zQ�=�����j������Ah-m�?w��[��������[�vn�ݻТR�`%+����R�aLi�3��֛@����+��8.�`!�I�a��b�0��� A�[��%8�l<!�:G���K�Af�&���\0\0��ap#q�\0�EBPg	�r \0P\0c�h� r��CP!�`�EXW�ÔkP�C�;�!��J.Ø�A��aP��9\" \0�Qt#\0�1\r� \"� V=��\n�0\r�D3`P@�A�*�HPB� 1�*ȴ	��!��1���\n� :�n1��6��%�`f@���LR`���\"U�QX\\�0�@�#h��\$g�x=�`#�R\0���1 lo��?�H\\z0�6x�AF����}�HcѶ\r� �\0��Q�`|�P�Z\n�����4��ba�!���F	 ���@\0 ��8`�A��������0AH	\0�@��� ��@�h\r&i������:!�\0��P�4\0����T��A\nH@�AhA`^ ����\$ �@�d ��`@�0�AH��@��\n\0�\0v\0(\0a\r���P�,��vB�t��a\n �a@ ��D�(!���Ad����a���f�f��@�!�\0� \r�Hq�P@FAj\n\0��l�\"@4@b��fA�@l�r`\n ���\n\0p	��Z\0`���@�N	�Z��@@*\r� r�X\0A��<\$`n\r��� '�P@��Z��!\"`�0!�V� N��@���\0l�jax@�. ��Z`�A��2�AtA���b\n�� L�A���� L\0�L�`�A��H�`n �`�>�Xa�@�`	�\n\0�	`J��8@K!��D�`P�{-@��j�N� �\0p\0�qn �\0�n���T ��Z@ ����:��I	F�����\0�\"�iZ\0I^\0ib\0�f\0�j\0�n\0�r	v)zI~i�����Ɏ�	�)�I�i�����ɮ�	�)�I�i���������	�)����I���I���\0�J���J�� �J(��0�J8��@�JH��P�JX��`� �D �D`�D�h	�	��	�\r�\n�� ����<\0���`�\rA��� ��\r �����\r`����\r��`�a�\r`�����\r������K�t����������\0�����7��\0�a���8@ι@��`�� ��ˀ��|�A	�V	a�@���	��\r����\n�bA�\n�tX@�5\0�5\0x2�p2�N�h\n��`�`j��@x��`���\ra>@� �\0�!aT rA4�h@f�2Aj@b�7[a=[aE[aP��^&!j&!r&�z&������!�(���r�h�n@���	�|�@!�!�����a���̐��Ɂ���\0�~��|!��0Az��P!t��l�d������Vaz!�aP�t�!L�n���N!l!��V!p��ab!va��j�~��r������!�a�a�a�A�!���!�/a���!�����!������A�a���������!�A�a�a����������T7T�v���A��A����r��rA������!�!���a�������!���M����m���W!�dADA������)}�}W�B����K�K��L�L��M�M���a�����:`n��� 6�G����A�*�6`��`!F\0��\0�������\r�sa�v��L�T�\n��!<A��\r������T(`��\0a. ��A`n�@��!a,�:!z�d���La� a�\r�4\0002!��1-A0Ѧ�y����za����\n��`��.�`���A��@�`�\n!F\0r\0z���R� �l\no��\0��X��b\0`� l\n�@V�\r��\0XA���A\0006��@&!��b��j���(���v\n�\0��!(��@��\" �	N r\0``�&�� jA�aJ��`�a0` T�\0����!\n�����^��`�a>p\0F��\0�\n`>!��&������=\\��!��@Pa6A\r��@@TR� �ـ���!�\0�\0F\0va@@�K�@��<D����| ���\0\n�P�\"��@B���<a���A��\n@����2� �@�Al!�\0N`�����b��������Z�!P@����f`<��@\"�0`�.�Fa\nA�@\"��`<`� \0!f�NaL@���@`�L�! \n� �A�a!�!h�d��A��0�����t�A&	\0D J�X@�a�\r������\\��@�a�\0f�^�j`A��� l��a�0\r��@f��a�AR!�	 L@`����%��*`L\0�R���@�@P�d��A��d�p�8 P``�e �|��A8l�\0�.\0 :�!L\0���! \n@�����@|Ap|v@�`���A�\0|`\r��@�����f� H�fI!�6�7t�)=7�83�8��93�9��:3�:��;3�;��<3�<��=3�=��>3�>��?3�?��i���������	�)�I�j��\n��\n*Jj\"�&�*�.�2 t*J>jB�F�J�N�R��:J^jb�B�F�J�N	>,	^0	~4	�8	�<	�@	�D\nH\n>L\n^P\n~T\n�X\n�\\\n�`\n�d\0�\n��`�@�\n�\r\0�\0�\n!\\����	��`�!��1D� ��\$��@�!\$\ra�\0�A���!��\r�� �R����/��!� �`�a�\n��@�\n�H4@�4@|4@r1�j\n��VA�4!�?�&\n���0������\0��dA����t!�\0���\r�x\n��\0�a:�\$���!X�0`�tAN\0��\0�\nf�����n���Ar �=\r�ǈ@t�Ł��!�(!�(!�'�p�4�`&!N��<��4��.&!*'!,L?� <�'�|�P@A�)\0��S\0\0LY���\0+ \\,��0Z����t�\n�>\$���#`X	U�x �J�Av��b�0��h0��`~��p0�Pw�8���0��\r@a�P���1��`m��Y��1�*�v�x��\0004Ml\rCT�\\���9�\r�T�@�9�`p���]p;���x��\0�P<���y��;���\"A�m x�����~��`|�{��^�ͼ �q���\"��־�	�LCF���M�C|��ߋ�7���\nc���\0h�L0+��IA*0��\0;�A0!�����P%�\\\n@`�\0�`j\0p	@T`@��m�`S�\n@0�f�\0��p&\0�b�f�@ *\0000*��P#�l�\\\08;A�	�1 ��%�� 1�@���.�(�@ �X6\\\r�`��H.�n\n&����hP%���4\0���\0�Ԏ��3\0h�I-\0��7�	��P�\"�q�� �\0���\r\0-��\n`�=@T\0��t`7�<���Gp��\0000�P5�p ���[��+0���v �:@\$\r0J�(@��,\0�ᬂX�7�\r� {.��TH�F`z��ꀚ�.�r�\\`y�=�H@�(���@��t��`L�)\0�p7�\0sh\r@	�I�8\0�X�2�T�p|\0��4\0Pv���-\0��5� �A�#�,\r ��@n�v`/�;��RP\0Kx5@�	4[���d�@��4�F�(�&�`�E�A�@I�,l�@~ |��\n\0;�Ap��A(%A�i����\"��\0�N�M��(0A�	�A  x�\r %� I\0 \$���� �w)T\0V\n0D5���\0���\$��	� �\nPQ@@�0�x�#���f�P�K��\0���5���l@(@�\r �o@@>PZ8�����@_���9�(  �	\n8\$��	 &�H���\r�P W\0����2�l�O �7�p\"���sX	���i\$���8��`^��@(&�� w(�H/\\��@ah\n\0002o�%�XįL��h��@I��K`,��<��	`-�\0X��\r`��	�Q��`h�\"@H	\$�,���0�B�L\0��7�.\0�@P������\0.\n�g����@\nJ\"P\0\$�@�u@��PJ�V����%�-�pK�^���&!1��L�f8��&�5��M�nx��'!9��N�v�?��P����y@��PK�����A��P���&��uߊx�^�����\nP�ÔD�E ���Ix��4��1�řy��J�w����/y�̀�8�,�DS�X���)�h�P�\$���O(`�8�f@-�� [���h�;U�@�\rPiV�Z�\r���@-p�Z�>�oh�0�7�_<���1@.���J�w��`��� Pǲı���h�x`p�\0003�D�e@�Y��@���A��2� g0�ԥ�6���p@@�\\�� ���]E��\0��:HX\0��9�[�F*1R��-\0� <�)3�J�\0�\r>L`��'A�@�\\�g �)\rpW�@@��-0PZ��Z��V�+�6`]X�>PA��0a@&4�`/z�^�����0A��a�\rXx2\0��d���\nAP���4\0�h���g0\0�` A��B�D���#�(	 kL���^��\nm�h���+��\r�[��F~/��P`R�24��`s�\0�89���Pf�����6)}K�j�<@�@9A�@t�@��:���v��\0.�����/��pxR ��<\rB)�t2�h<i�l�e�@`���	�P�K�7�9a\\�3|/��k�7��\0000\0�]Pt����J�����\0�`h`x���	@\$�E��&`��hp��	�J�� \0\$�n0w��	���\n\0�\n K���\"����7�LG�\0 \n@�\r\0%�`�m�\0�	�]t\n������P�\0�\\p9\0Z\r�\"��W\0�\0�.\0� c\0�#�\0n��`�1�6\0�<L`s\0�7@J\n0\"�(\0b@<X�	 ]�\$u\r`�\"�\"��\0;H9��ð� �-\0��P^�0\r�D�&�n\0�-��a�A�`f�x	�J�1\0��\0@c@��P`6���P>�6���\0 G�\0j\0R�O�@��;���8A�ph���H�^\r�J\0�`�`\r��u-�|��H,��`D\0�@,03\0�����r�P)�rI��,(\r����`t�`�0�3\0-@_\0 h�Ĳ���s�\n��@b d� �\0�\0��rH�08-A��-\08�N\"�( \0����\0���@g\$�� 4\0vK�@e\0{\"�� u�� �\0���c�l�1�`�� O���D��!�P�2�`��)�4�0�\0d\0h1��\r�!��\0#@%��\r�������,�.��E�(\0��C���*!�~�D(��@9�Pmd\08H:*\r\0��@�X0��� h\0���\0��q5��@��	PY�X@�3@�\0�^���-�+�B Y\0���+\0\n��\0��0+�� &H\0���p	�(�`+`-�k�\\s�P2��C&���X:�T�e��O�\r4Q��c�� ��1A�\n�,���R\0X/��	 &T�\$���\0�3�p@4�A������0���#�����9���a�D`o8A�; �\r�8\0h\r��a�8!9\0�=�@����@�(�9��\$-uT�	Y3\$�L�8�6ND��3��L�:�>N���4\$�M<�FOD��4��M1>�NO���5'h(ڊv��gp(!܊\nwB�ؠ�x(Aފw&ҡg(a�Jx2�&��(�n���&�7ٿ�po sȀ��@(<�\n/(�ʀ���*<�\n�0�̀�8�,ND3�{������ \\p��`*t@O�&���TΔ�B�0@|�d��\0����pm\0�\nϢ+�������s�+�^�(�7�<\n�,0eg��p��4�:\r*�A���\0�5\0@\r@CP �\n`�`k��Y@*�\\��e���q��\0hh9\0��r�	������ނ\n��5�4��t�L������u�l�QA\n�Mb\0��' P�! ��)��U0@�-�,@Y<h�	�\$�	%lQ[\0�V�'�\n��& �	�,2a`_���AI�A�Iق���y����=_`�XT���5��̣x3��4 �j)��Pf4�|ɣ���gR��x#��\nJx@�(7�\n���\0��8�����\r��(9��ps�,�����\r;Z�T �����Pqiq0p�'����k�\0�x5��k�H`���P�����7��CN�&(<MjlPv/H��^�=r,m`|/|A0�7>�=��y����_����o���\0 ��-�(`C��@q\0�\nAR\r d� >��i�����@DH	5 \0H,�(\r�T���p	��	p9�� H��T��\$`�<�PA\0 � &\0~��(���1@���\0`�hAt0u�H �#�D~�\n��	`)�&�#]��p7\0�\r�\0���q��PO\0���\0h\$��	\$@��7@d�P���J��D��	�!�����2�{,\0] *D��y<�5�A�r�h@��%@��\"\\��� *�0\0Cp(@\"!����P8��z����\"�\0�4�,��	�!@�a�\n@�\nP&�@�7p~p@w�Ajy����h�\npL\0000@�������)�z0\0�`��b�\0�`-P�P!���x\08A�PZ\08�����w���`:�f���R��	�%�\0���y��A�\n\0 t�4p*@1Ƽ�@F@3�&��\0��qd�@p@H �0\r F��� �b\n�\0 	��\0` [��;�|��#�`np`�3�h�E\0�@D @@i��q�,A�\n0?��B��\0A&�pT����\$���p�`k`��	��D`K0*�r�!�\0h@(�}9��x�YD02P	&���7`dU�A��T���QHL\nY�,\0��,�\n���\nu�@��0v��\n`�T��I���\r\0@�H	\0\"�X@�l\0�	������ h��@����d\n���Ch��@!����\0@4��W��`�j8�h�(�2P9\0�\0I\0T	�#`\0%-�x�����X\n���v��Q\0h�!Zh`^��hPjH`��/5��v�p��`�v\n�lJ� �\0\$��pv\0��3�*��� ��j�X	p�T�8�D�\$�ۉ=�i����&j�i��*g)Ι�t�z�i��jh)ޚx���i���i)�Z|���i���j)����/'5k��b��Z���/75��͂��b��/G6k�͢mN�w��^�W�����t��ݞ!7��M���x��^?8[���s��<��8���BrS������\0{�@_� 8��`C�\0��00���\$x,���a\0�������\n�����jVS��|��1�[�8X��:|�z����` ���R�'L +�@`\\��2���	��H9@�P?�\0H0 @�pA�\\�W@��/3��,����s�0�\0�aq��pB<��.\0��`<*�V0\0�`9d�p�@�p;���z2�@��?�\0��p���@�� ��:A�u��nLA\"�sT`��#A�	�G���F0.u�\n V�T@��iA2�9���:�%\0lPI�*���� ��H��Ȁ��J(�J���(�Al	�o���	��n`���'��`p�w7���\n 4�\r>\0��3F��\n-` ��0��F�dt��01��<V �x5i*\r�m���4�-�� ^c8X�@8��ޓL���58A�\r�tit���<A�\r\0y�D�xt�6�j��2 �����A�*���E�%OClp۫�����0o ~��<-*\0Po��\0�1���\r�X`Wppi��`��0.��\0001�d��\0��`)\$��Lh/A`��`����0y\0���8@�<�D@J�*@Qh�r�'�O\0�-@�\r���Z)�@�\0����\"�\0.�l��\0�\rAN\0�w�4�DCfU&�p��\0�+\0�P6�D@Vx8Az�vH �\0��\0�n��`E�P7@����`4\0�,T�	H`�\0�jA��W�\r���@\r�M�( &���X\rpD��\"�X6���r���P,4\r���h�����v�~��\0��\0�)��\0%�`\0�&�h�\\����2ʴ�\0t����*�x���p��\0{��\n\ru:�\0 d4�\0��<��8���	��\0h\r��\0	�0`��1��<ٜ\0�)`\0������=��\nPL��3�p�TD�Y�x?@�0L�h@���������]h�p<U����p<�H q�t��ŉ��@�0�=P\0��T`�V�)�q���4x\rA�@,�4\r\0�4��>��-i�5Q*4`�`<��0(4 ���n�\0�'�O�4\09R�`�\"\0�&������P4�\r� ��\n���0��\0000G����HA�0d\0�` 5���D`��NRD	����0,A�\0��p\r����v���4��\0���@m��`P�:@� u���&X��ېf���>`@T�h6�;@=�P����\0��\n�x�3��9����r��m�� \0f��&�6\00'��Q`J��\\�\0��	 TT�\0�	�F\0�Πh\r��\0q�����	��\r`\0�ܓ�,A�π1��@o��3�@^���������@<�`\0@d�� ;0�:��h\0SI�v0���)�d�'\0���s�:���K:iӎ�2`3@���������uN���陧t���Z뛾:�vn����r�[�;#w�˼s����7j�]�.�v���wܧs��wYݧw��w�ާ{��(��鵯R�j�i��Z��ng��xy�'��*�Zo����z���/��B���/��b���7�����/��\0.����@P������(`:���3���*�\0��`)���s�c\0��_\0,��\0�\0���+��8 �\n��(��|���\0p\r@�\0z�	����@\"'�*}�\$��i�\0)��x`-���A��{���'*T�\n���-D�<��T�\n�'9�@>����@s\0��( .8��(PRA:P�A��8�*����,!�p\0E��F�	J	������HAw�bӁ �` &��\n``�0��>�\r\0C�J�\r�U\0�:��\$�dP\0Z������8�\$	� ��&0������@a�PyN	����b	���rp'�a�8� e�b\0�f�Xh��I�d\0�.��\"��7��P@<�X�\\��C�Rj�@e��W�@f��l����stp�\\�Vp@V��� w����0`b��x����ǐ\0u����p\rR��\0o1�� u �h�w\r�6 �F����������썲:�몪}�*7Z��*�2j��T0`s�\\����`#\0��rA@mAB^b�@K��H@c�\0��J����}�p��5@��C�b\0�`9�� �Pp�%�~�]�(�G��ax\r`[��@9\0\n>�`����T�	�`\\\0\\V��V���@j��� <B�(`C�H�%��p@&\n\0�`\\\0� C�P�!x��N�� �3���`��`@V�h�\0�����\0A\0\"�`c\0�p�b�b  5l� ^\0��\0t�lh�P�v��L\0002X\0����`9\0�0 6���H����s�� q�f��a\0\nlB�6v���X8	�]�Z��g\0006���(��\08�=�j8��]J� [�b�X�!��`%��86�\0�`E�B���7�� f88	f����t `7�`p��H\0Y�>p\0{�p\r��r�\0� \0@1��`��\0\\ˏ�(�B  c�:m�`p�b��1.\0R\0l\0��?�V8 ##Vp@\\��`�zj���( �J=fK��f��=`f\0�B��H� \0#��<\0*�\"�	\r���I��`�I\0l�؊7�\0@@\0l���ׁ��@%�b��w�+\$��H\0��A0�R\0T\0O��	D�\n\0F�,\0�`E�^(����2tP�:����N\0�`f=,\0\0`4��\0`�<�x�y\0004H�op�!a�\r�����W����Y��� #�B��9�V�<�� \0�\0p`s�\$��[�� \r`�R\0�@0\0t�@z\0P��\0\r�H�\n��� j�\0 \"\0����`\0`�8�A�r�(�	�\0\n*��P\n0)��`\0�H�v\0�\n@\0�X�2\0 	�P�#\0N(�cT�@@�*b`u��\n1��h��\0n�@7��\0�����`���� \n\0�`6��� b:vܩ5`l%l�;s����c��ݦbM��'X��u�ug[��u�v'^�\0vv�a�v3w�d�vcxgg�����������P\0��\0\"���\0R���������\0��M�h�J��/r�rp���~p��;~������*\0��\n@5\0�p�4���\r�\$��<�|���F�T��D\0fx bX&�a\"��h�\$nh��	��j�����\"����B	@�oR����b0������ew\0lY��p�l ��āp��\\j	�!pp�\\Rp�Q\0�� 8`x U�\n��=P\0�\0M��x�-���{\0H��*��t(C���D8�(��?8���:��E+��\"~e�~�p:\0��	i����}� ���� ����<������\0�(\n���N�\0m'\\��.�`\0d\0�(�/���\r�:D��K�( K���K����3�v�^�F �U\0�h`\\�� �^��(%��X`�o��(��o��8ɠ5���(�H����B��x��,��E�X[�CJ���g�[`\0t��r�`_dx2����`�F :��Z��8�y��@g����z�`�o��\0x�ڄ(�v�+��x��^�,�C��h�̋\r��{i��^�#L��ꢟHS� �@z���*J�2Rp`e�@�Q����V\0��\0�W�:8 y�J\0f\0�X�M�����m�n��w��gH�]\0�`\r�n������ \r�j� \$\0006��L�\r�l�@�@T\0R�&f�W\0b�	@[��\r���`P`@W\0���f`\0^\0ڠ�@mv�[영9�@[���\0�\0�8�K\0}��� X:��2�\0��a�2�@A�⋰`e����z���`\0�\0h�`�\09L\0��[�������@X�@ !�8\0p`B����E\0�\0h@\0� �X\0���}6�`p����)H(�\0�\08`o.x�7��\0 2��\r�H�HH�#~��J�����b� r\0D)�2\0	> `B�\0��f��� .�� ��X/7�l�\n�)&�\0Z�`�0�Dx�t�p\0a4�ܨ�&\0�\0h\r@��p�8���H@\0�p	@�d�\0C�*�X�!�]�f*��`�b��C����E�N��@\n�����*\0���\n �>`�I�\0�	 ���\0mG\0�0�!���`\0�X\r�q\0N(\0,���\n4`@�\0�b`v�0��&#C�\0���`\0�� C�С@��\0`K�x��\0R�&ځ�`w\0�Ը�\0p�\0I�\0!��H�.:NP\0��``=\0(\r\0!8�M�jE\0H+R��H\0����c�����@x0@4�RX`s�*\0U����T\0��^�� )�\0h\0Ř�0` �\0���G(\\N\0�`@b��ph�OĦ�󤭰\0p�1�`2 �q���@\0;�\0��b\0q��.�i�A\0<��Z�9��#.\0���\0@�\$���j��BrX �x�_ ?��\r�D�\0�dP�ŀ��A �dr�pp���`8@Y�����2�h��X\09�`��GZ��D��ڈ�w���)�=�F\08�p\0�\r�|�m	�<?�u,�Q�&_R���.���m�.��R���.��r�m�.�ݒ���/ݲ�m�/\n�����/���m�/����H���K�?���\0�@�\0��@�<�	@ �R7� �|��j��}��H@�����/��\"���/��b���/�����c��\0��@0���p��-�3P�=\0004P�M�5P�]\0006Qm�7Q�\rE-��`�2\0�X 5�6��T\0� @)�Fx�d@j\0��\"����m�t�U@�ɰ\0�k���\0I��rf���!ɁE��`\0�&\0ӟ\0�9�	'�\0�E	������-Q�`r���\r��/�T�\n�H��0o�F�@)|��9-�7�\"-�EL�&��K86��M��;0�5�X�\n�X@@E���0s��V�em�W\"�[n�j�.oB�&\0[f	��\0[1#�@��0@(�e�R\0�d��h�]��1�h��D�����@ȷ�ꏀC�p j&��L����j���l�|�\0k���j�c\0f��2�q�c��@�v��ĳ�f0�f��p\0f���u���\0k���\0s����rQ́�5�@r��8�v��(<���Ș@�\r���`|���4�{����l�H��Bڅ,�A��,:*��P���r����g�>�\0w���\0�F\0p,h`sK����P\0z��V\0�,��F��\0��LT��\n�~�8����ꌸ y��P�\0�H�j\0��@t\0�(@R�h��;6h@-\0�\0� F�fH����L�\0`j�P���4,h�b���\r\0�J�\0S\0<h\r j�P��� 9\0��@U���`s\0�` Y�N��`F�@:;�(�h�d��@[�\"h >�\$� i�\0H`B�v�_\0�,�R���5��\r�� <]�'0h�s��\0�G���\0004� ��F0`p(\0V\0\"x\r�v��0�`��l��6Qj�@B\0�x\0007����>X\0}\0\"�i��N&\0��K��+Ϙ��\0q[5�`\0`b�J�`F�\0�kh��-�\0�[C`,Hp����\0a�0DO���������\0���`�x� E�J�!�\0��\0�~UHm/\0�@�@�\0�� ~�`)\0��`2\0�,N\0%�\\��U\0�]�	��� '��@N@s�`` ZU(P�2(�+�\0�V�F:�@D\0�0 y\0� \0004H C�G�@.�����`�<�N��\0j�c\0Ji� c�(��a�K�tU^`l0P G\0�˔ ���3B�\0`@����\"�H�0�\0001�	@�\r�r��@��w�HX@Rl4�<\0�G܉`C�#�F�3P\n`g\0`��i�\0004��h @�h�Q�� �*�� �z�`'\0��\0��@\0000\0`	'� i��(�B\0V�0\0R�\0�` @D\0�F�	r\0r83�h@��H\0Y�<�	ї\0DA��J`\0��a��� ��@\0D�0� N�!��V�8@R\"� }�8H͋��\0y�8�i�hH\r`O��i5�6��M\0�-��\$�|�Q�@�7/�J���Mԝj���\rםv����ڝ��!؍ݝ��Q�M�����m��z�U��Ђޅm�Њ޵��В�C{��7��t���0�Di�P���,��B��/��Ck����.��\r�@y��2��5\0003��E�4��U\0005�e�6�p\r�.��b�60��cv2��c8�4��`H�,��8P@\r�\0���6�X�J<�\r m�00\0M���M\0�8\0���@�*��: ���H6J�)�\$k\0����U��.`�/��@:����`5Q�%\0-���\n�4!��'�\0�| �*��\n�ي�0��TP�� Qft��Ҁr\rh4�W�N|x��Ǌ��\0(�6��]��@+��`\r�0�\n��J\08�D�|h�c9S]���[`K��\$`O��!\\C���\\�[d�8�^�,� b��A^�c�@	�_��� f�p�b��@\0h�V��`j��[��g�����i���)�R���`l��y��.�o��p f�ޤ8���KhJC���\0y��p#^\rn5x�u��h��c)����j������ΡGL�|�y�M#��2�B2�A6@@.��2#�]�\0 V���`k�f@ [Z�\0��H \r�\\����.�\r�ml�`l����.\0d(@QX@<\0aV�ƀ�l0`��d�h\0�!O�(\r\0'\08��� 4��0`u��h\0��\0V \0.\0x�`h\0�X�K���\0\n�\0����@AXP��t8`l��� 5��� c\0�=�K���`A\0Jp�����~�,��P\0)#�0�\r\0b@Y\0: ���\0v@\0H`\0�\0�\r`=�\0 ��\0\\���H�	\0�� g��T0���\0� 0\0�\0�@\r�(\0�\n \0�\0x�\0��&��@�\r��'��A����[�@6�;��[\0�@{��(`\n�&Q��b�`���X#��>�J\0�	\0�h`f���`L���\0i�:J�\r\0�@�Y����@��@��\\�﵀�@B��\0@d@\0\n�L�i`e�X�\0&\0� \$\0�\0@\r��`\0\n�8�\$�^���\0R\0�`\0��3\$�\r@A�J}�2\0pI���\0|5ϠJ\0�8  ��\0� \$�Hm�B�\$�I�`\0Rp`r� `'B.\0�/����\0�T��\$�M��KJ�H\0�\\�\0��pP�!9p� c����hP�im�����J9�\r���J8@r\0��v��\n B��� r� *-vV�Yml�t��Y#�@�T>nix @\0�H��R\r�\0P�J�w\0P�M�\0X	 X\0@H\n`R��\r6\0JP\0�\\o@\0a��p�\0�L� Z6VP`TDkL���\0�t�	l9#�g�\0~��]�����ITL�6w!\0����`��@JPH�N`J�@��ٰ�\0H��\0�L�`9�_d�@\$��e�`J�8(\0*�j\0007������;���X@��\r��@�~��A\$~���AT��A����A������)����������\0����\$g}���S{�7�ak|��7a��/v߅PP�ߵ/�a���؃��t5�qb56\$P�C�4=��C�4A�D5��7ؾ�� 8�����9���� :��X���x�9��0\r�:�\$� �1A��\r@Z;�	�#_m`�h�~��[��� �N��y\"�ٷfت�ȃ,lf���TY�f\"}6b��| ��\0�e�;�q�h\nVZ�\0T��%���\0001�� \rPF���R�>\0���?�-��E�`\n�#�{��2��h\0I��[�2q��6\00@/�Z-{_�h�����m�2��[+\\�.w1\$9�`Z��(�\\\"�� ^�\0� a�\"0� ���}����*�r�\0g�����h�\\�@f��5�6�����l�B�G\0l��� �e���Ѡh���H`x�( g��j��y��R`g����{�������`u�#\n���B�+�����L�E��V�2>_N#���:_T���\0���-r(L�8��l�pH\0��5�2�`v�dT�\0\0�h@Y��B���@3:�����#�T��\0&\0���s(�����|2�@\\(\nQ��(\r`S\0U\0�Y�D��Jh0c .��ү�k�(����`K���ȀJ��\0\0��`[\0��\n@K�X�����p��H�\$�`��W\0�`@U��	�X\0���\0t��X \0�=�`9\0�\0X@/�`L@!�h@d\0�� 3\0�\0`4�v�@�v���*�V� 	�rh \0��\r\0O�<0�W�h +�x�x���@`\0\0(`;\0��6����`r\0��U\0އx@e��`�a\"h� 'j��mp��p�� �\0 �m(9�P:��E�`qY@\0�T��l\0�\0D��\0 g\0�0 ���h	@��`&\0�J\0p� !�W�4�\0�`N��5�����`#��	���@�)�� @��\0Z�&\0{\$\0&E�� z\0ph�\0�	@��-\"\0��\$(��@J>��1\0� X�&h`b\0@�8���h@��M�T\0`�h�hX��\0�H\0��Z�\$r�=!��<.��*@@��`3�P8 :\0006��/\0�( 0�� Cl���#���\0<��\0�{8\0T�R�\0\"\0���U���\0\$\0\0H�8gy�=�v� 0\0004��y\$\nW@A�����Y����*� 	�v(\0U\0L���TD��P#)Iz�u8� U\0H�@F�x�{��5� :��H@\0^�`��1\0R\0\0��\0R\0��N���\0w\0n`�NNP@<\0�`j�(��rP@�T�(dY��I8�J 8�\0���4�\0��h\0n8���\0@��x`�V�\\G~�j�\"�*G�H�\0007GY��G��-����`d~���!��6\nG�����HA��Ѕd��#_� ���H+ab��g\0�}V@;B���m!%��u!U��X}C6t4؉Cu9X�b]��'أb��7�� �7����\n GX��!@X�S�Y\"�W#14 YA֢\$�j���V��%l���!��\$o�\0g8@G\0l�p�V��@�&�DP�L�ph I`TH�6�!pTa���8t[��`�'\\���Ƌf,��f�̟@vb�PK뇕ׁ�\0��3�.c\08����T���s�/	��v8\n@r�Z,\0r���1̉��=���\r�&\0� @8���@p��<��%\0��ec��� F���M���\0002�zx��8N��`����y\n1`�:^j.C���IXt�����	`��`\n�`��@+\0b���\r��LgE��8�{���Pg���@��J��Y:,�������g�&X`e�x�eZ�h i����i�� v��\n֥�<6!E���\"n���6��z���@쌡8Y{��Z�7��\r�7�j��O(\0����h\0rH 0�rH�����@���� �����@����� ���\0YL��\"����b��	� ~Pas����S3�-� O0��4�HP\0�rX`8�P`�\0��?���G\0T��9����`��\n&t�l\0� 4R�k�`z�Z�2m�#\0x\0\\p��60��\0~��\n@�Nh��P\r 6\0���+��i(�\\����`dLh\n`#��\0�@[\0�D\0`U�\0с�B�&�`.���\r`Wt�� ;�ۥ�W�UO��\r�����L��8	�C�(Q\0x�k���� �hPG⁀tP�|\\�\r@\0�`���=�kռx��L( \n��#C����8��&H��\0��R�F\0``e��	��\n0F��\0�l���Q�P���`\0�@r\0 �ș��@�\0:����\rH`�hp\0`��	oÀ�,@�-\0I���Y\0�\0p@m�X�ؠe��=S���l@8\0��� d��\0����\$�\n\0�; 2\0bP�{\0p( \"�x�(��ш �(\0p�Y�����J@.���D�x�U�`�@|`\0oY�\0�P%�ـ3f�����\r k\0r�`X; �*_^`�6�H�@(`N��`j���b�\0�8��(���\n@\0��JW�lH�}�ZY\0a�X� e���]\0D�p`^%���j�\\mN 1��\0-\0` K\0�l��i��`nx\0:�m;��qњ��y�V`\0xJ����\0���\0�/��X Vdp\0��ԀV�0` e�\"-�M�y(�@��v M9�dB\0 '���Ҷp\nـd�n8	�\n\0�T�v�\r��t	�B\0���~e�tX@���XA-�tXA]��X#A��tX+A���g,�D�����t�M��ޤ���� ��t0_�\0�����3B���*gWa�u�P�ߥ�P�]�/�k�@>�T KP�`V؜�\r\0005�b�v*�D\0�@�`\r@oX�\0�4�\0�p��@*��K��R	- !��&���ɞ�3`��+ �W.���Ҁz\r(��/�\n�Ҁ�\rx��0@'��	�n�f�\$�e�~H@5�TJ���D���X`Z��s�V������`[9��� [�(\n��.��[���x�P�d� �PW��ab�\0Z���`]���`^��ŀbY ���~��\$�n��'�o������X��\0i��^�ih��@h�Z��^[�� %�|��h���0d���`h�B��jZH��\0kZV���ki:� i�����E���b�R� b�th�k��� BW\r�j���@f��K��\\05��0�y��F �u�V�%Ё�P��]9t��B�5�<�b�B��x� �h� z\r�6p�hI!5�����������X�b3�խ�)��7���SLP��� `��\0�f2�F0 j�(\0)k�x�@غ�c\0����w��\0�V��`\0����65��'�� �>��\0����2>��R;���+\0�¸�FLP�\0\n�W H�\0��\0��H�^�,x�J\0���\$��\0� *�p`7��c6 2��B��j��P�+\0\$  G�]�P�`k�Z���N�SrP�o�\0(j��`�c\0M;�\0�\0�X\n�T�>\0�O���o\0���K�H� `�d�Zx\0�x	�%I\0\"��\0�Q\0��\0007��\0�@����\$B\0��}�,X W��P`�?� [\0J�C���``V\0���c��\r 8\0�W������`1�nM�@�r����n*�����\0�b�<^D�`��ћ�NF����\0��)\0�`e����#���vd���=?%PX�p\0Hr�b��@�\0�\nK\0//��`\n`	\0o�@F�����r��h�t���H\0i� +1�*�y�h�����8�x��t�92� ���@!����H�d@	� ���2���a��q9j��h�\0��6���\0h�n�\0��@�@4\0006�E ��\0�\0� �� T�@e\0X\0^��u��&�/�XP�=,��2�d�\0PQS�c!�Q��\0�@f�L�������� F����^��q�fr8@]H��`\$\0B`Q��|�`\0<��`w|�	@����d\0�Y���~�9�\0<[@�S��� �4��VM��@)2���&v��C���\0�@Q���	`F�j��`\0f�\0 ��m�&��o�L�\0F\0006h\r\0�\"��~݊(\0006h��F�`&Q@�p;�G�ue9��=�=�PF�E��PR�e��P^��O�Pj���7� &��7�Ky����h`�P�5�l���zH\0���;�\0��Dd���\n�\$��	 g��P����h	 o\0�P�%����@=8��@Ap�s� �Q�mP	�t#� '�&�\0D�> �`?���P�6Q��r/��a &Ɍ��p��f-�2hP���T\n����°%E�\nZ���P���|X&r���\n�f����Z���M��\0��`8�\0�3`=\0�x�<,\0@N����>�RH��\0��~��\$ `�(�B����h=��`E��`m<`�O�`q�\0G��`Q�Y�a�H`�32�Hvz\0�?H�'����e�����wr�w;�>U	[⹑P&'��n�\0K��;�`M��� O�z��Q\0�����Lp�T�R�U�,Y_`V��\$@�Xn�!�Y�V�ƀ\\��\0�]��@`���z�W��z9�~��(��ƀi �X`i���` j�\0b�0�j��8�I�K��H����i.��ā�J�O�O�X�\\��`k����n��`i�2\\�;}��`@h���� l�]`�p¸��e��j� v�@�`t�T]�v�ܧv� ��|�chBΧ�ziܯ�����D.)��c\$�Һ��M�r��\0��78�\0\0\\ �\0004��\00060�lfHݒ��\0r� 0z��]\0����-\" �@{\0X�@y����:.p`\0�\0�\n���`2��l��3�\\�v��K�K�`�@2z�z j�f�\0`r�\n��F�:�x�I\0\0�`e�x8@P\0���y��#q;'� \0��k\"��)\0B���cl�kA\0`˕\08��X	�I����h\08��� �*����H\0��`-��ԇ@q�8� �&0	`Q����#�����\n�\0d�q3�\0	�tX�\0000��N��Q\0�\0�`��h\0]���X\0�`�M\0���6��i�\0�p�H��Ǯ�@f��\08�%\0���q\0�	|O��� M������M\0fh>�ex�\rAi��M�P `F���0؀x�0m�����f�۴x��p\0��J�*����\n/��\n\0�#�W��/�u�1ŀ 29�0�\0�(�B�@�o\0���K�Õ@\0�L��\")�L��`dY�\r B�CEA��J��\0� @�` 2�0�R�\0`b�MW\0+�X��{�w@� ��t���2C�\$�X5\0x�@�\n`�!\0NJ�X��\0��\0��|\0��@	`<\0�B��P�F( +\0(�\0007\0��Z����h W�\0\\x�����\n@Ol>\r��J��\0i���\0�8	@,B@�8�r�`B�\0�C-È	\0(�\0�\n 	5\0��x r��0��6\0FP\0o����`\nj #�\0��@&�(��2��x�l6���+�x\0  ��a��z#e�F��@�b\0�\0� h́���\n`\0ӓ����h�Z�\0ph��v�>\0z��\0�d���\0�p@�x\" ��*�# ��:�\$ ��J�\$�S��	@��T1\0%����Q[r(SH�& ��n�@`��v3d��E�|	�'ࠧp\n(�\$��\n8) ���()�����*`���`*�P�\n�+`����@,`����,�<�@@?��p.\0��\0���f�.�H����N� `��7�\0V\0000@��p��`�� �\n81 ŃJy�\0��p1�N�&�2��J�3��'�t��ЃF\r(��P\rP,�� �Y�\r�	 qF(� 	@���p�@7�n\r�6�9\0�z�7@܀�8\r�\n���`-���,�d�9 僚x: #�=0�z���;a���;@��Ȉ;��F�de�S8 ^v��Áz�=?Ã���^��H=����p>\0�a�j�`}6T��@g��@r��?����@���i�@!����n�� �zs�� |�\$@�^6��m3Cn\r��r��^\"�Q�G,�e��c���������D#y\0�k�.��!j\0�'�Y�8�O�~��5�*����]p !\0P�\"\0���\0005�;�'��\0006��@|\0�x\0Pp��(\0�`%�P\0�;�p:�&�*�p�&\0000��H\0 �\"�<\0�\0����@�\0�\0h�E�\0\0�\0<|@ (@\n��\0�\0d	a`@1�5��\0�\0�� \0\0p�X���	�\0���2��\0�\0�F�� P`\0�B\0\0�� \r@�@��T\0�@\0\0  1)9������@Z\0-0\0��	` @�k\0R�;O��\0\0D�\0%`\0000\0+\0}\"5\0@� ?�\n�\0�\0(\0���\0F��\0,\0��\0@R+�\0� �@�`\0|\0L`��\r�7��,`7D5 4�\0\0\n\0`�\0\0��\n\0�\0����`@:\0(\0��D(\rPi�)�~8��\0A� �P��\r��\0R��f1m�\0��\0<\0�@\0@;B\0@�I��	@	\0#\0s\0rGa�@��+�\0�\0\09��!\0\0\0�@ `�-���\0���@.��\0\0 /�@�<\0(\0�\0@ 8�d�@\0Q\0�� �q`@V1�\0����\0`\0A|� KcP 4@\0��d�p @*�b\0F\0�\0�\\�G`\0(\0��\0h\0�Z ��\0r\0�Ri8�\nl�\0:/N��\r \0002\0`d	�\0@�\0�\0�`*\0t\0y\0�\0����@6�x\0\$��P\0V@��\0�PX�\0%��\\�\0�P� �\0%8�D��� �(�*n\0�`��!��>T8�\0��`d�\0<���f�\0 �-@v��\0Bv��\r\0 ���h\0  \0�ۀ�,\0001D`�\rY\0\0006��\0�_P`[,\0L\$r4� \0,B��\0�(<@� 1�p\0F_�&���8%�J��(?��%�J�Fqməƺ�g�q��r�rn�Yʛ�_�o%;��sK�7<g<oa�\0��mЙѠn��Ea�:������\"`���B�\0��X��`���\$��\"�oX��̀���\0�1\0elv��.�\"�Z�\0��;0\n���N�\0�+SA �,��\09'�����L�\08�\$�\0����P\n��B �\0̂LcC�g �;�u8��\"���Ѐe��p�	 ��o�Z�� %�5���UdX	3��\$�(�S8<x\0��5�F�T�<�|�\r�(�K�|l��'�@*���\0�Ģ�0@�Z\0�wp\$�fƎ�j>��h ���0�DP6t0�\n���@[\n�l��	Û�H�2\$[ja�: �;�M�;rp\0�\$��|�X3��.\0.8C\$�p@H 1\08p1`\$��e!�f���-���P�\$�����l4�\nҀ*J\0����An�%�:m�-���`7�����IL��5Jk����`<\0c���<9�P�;ߏ����(\r���9\0v!YN��+p\0�\"�H��)���M\0�I_ֵ�p!LB��\n׎�V�De�yR���GF\0���	`5ʅ����H\0��	@8t�s��ƀg��!�(�̉@j��\n�t�	�	\0�\$�����@.@�k;Xh\0 @��Cy�\0 �\r�+���\0<1�8��р�ŀ�\0\\@\0��@ �a\0\r��O6��#�\0f���,� .@=�.D�\0X�@��\0�,��F�o���u�8\0�����<9��P0oC@j��\0��8@`L'\0h6�\0��\0�S�P\0\n\0�8\0��7<�8��(@8��Dr\0HPP��M\0\0H�h	��@f\0��\0�x	0\0�a�wQ�FU\"@\0K�u,t||�!��\n�>@A�:B��P&��:\0\0�\0�\0x	���\0T!�\0ާ���m�\$�,\0��hC��\0+\0@\0IV!h��4�.\0\"d�\0�pp�J+\0�C��(	��\00T\0ɐ@P�@�G�;s���@15\0002�X�\0VSU�(�@8\0;\0�\08���	\0&�������\0���\0�\0��\0x[�Ā\0c,X\0#k�0\0|\0x\$6h���E\0�&�x\n�\0000��\0�p\0H[dS���o\0�\0�\0�ZN��'��\0�\0�/P%��w\0\0R&h8�̀\0s\0�\0�\0��� �t*�F�\0��0����\0�fV��X0\0�\n�P\0�\0(n<�\0P �'\0!L\0�����\0\0�v\0�\\�\0��\0)���\0>@ �'�O6\00000�@X\0%�d��>�C��c��� 	&h��τ\0\0 ��2���\0*\0~�8&,�`\0��\$\0�p�\0�\0�\$�����\n0��e�}Q�m���\0?d\0�\0X��\0��A\0�A\0���#`���\0��\r����]\0)4``\0�\$\0�\0H�	\0��Yk\0�s�th0�@C\0004%hPi�\n\0�\n� (Cr���	��y��\0�BS:�7Iќ2�q\rș�:�g�a�sr�r�Y�/�g(��39u���[�3�u��ua#9����XWX�#:d�L���H>εB�t�\0K[�0Pƀ�a��xEJ�63��^��������,V`4�i�����2,fXб��Xf���x\n��	  @N�o\0��y( ��_�b\0�(�� \0003\0�\0����EZ\0000\0�\0�x���7���\0lO`;i���Lb��\n�><�r��3\0�0D`���Èk�\0002 �\0�X��	0���\n�\0�*8�CS٠9�x �8�q�%P���ȖM��\"�l��\$�/��(lh	Q��%�/\n�,�xP�`�4��\0�@z)� \"���A�+`�����h�6^��a�\0.��o�\$\n8w 1Z@�� ,X0���1�7�y��h�.\0#Թ���|4�\n @6@����p`\r��,CP��\rB�5\0�6Ÿ���`[��@!�@t\0���0@�1\0a\0���Q��2�x�p�\\p\0`;CN\0�\rL�\\�2�b�@t�����j��N���2.\\�� ��D'�<o�|����ı0с6������<\0��������@u\0004\0�J�\n`�/��UZ�=�p  ��#���	`\0	TU�(#L@0��+��\0^|�	\0�\"�#�����{�\$��\0��0\n�@.�6'VD���\0Y@� ��ᣀ�j��\np�)�A�\0j�p@\r�%�\0)\0�������@K\0L\0�V,@\n��\r\0-�	\$�@	\0�#\0H�d����@jM\$�H�!X`\"�(\0^rL\0	�(ʆ�\0\$�Xp\0Bi�N�B\0���(W*�_�<��\0�8�\0)@\$\0�\0@\0�cU� �;\0��� �:[瀐>D�  .�3\0�,�	� ��QZ�[ PbE@L�\\\0��ƓP`�u\0�L(���\0l��X��uW\0�>J\0b\0<p�\0\r@�(�x\0C��A��/�i�6�4H��@�\"��\0��tXa�4\0F?��1@@\0F\0\nN��(\np\0�\r�E\0�:�\r��:\0r\0D�\0�W���!�U\0�!�8\0�E!����5��H��@f��H�l``�b�-��:���@\0�\0Rx\0�� @i��\0Z@�0n1\0001*�\0�\0���\0&\0G\0�\0\0R��^�\n\r\0vͼ\0���\0��\0��	 @3@!\0��@H\0\0\r���n\0\0�A` &\0i\0�l\0�0\rc@~�K�r�����Q�]!�X���@&X\0����#�W��\0X�` 	`+�}�l`�\0 \n`��%\0F�� \0H�\0\0b�t�����w\0P\0a�\0�0�\0�\0�_P��8M����\\\0�@�̀H\0�y��\r���\0��\0�l`�\0 �O?�\0H\0�(@���\0L\0�\\h�\0000\"*'�\0\0\r\"�e\\\0#\0>\0r���\0Ŋ���(��/\05�F�\0L\0��M� ���\0+�R�`�v��>��7���+����~��A���, �����B��q`� ����q7;XF���:���&���2��Y�Cm�\"Ф#n�����B��p�[�ũgr�6-kwЌY�ųX��Ub�ŕ�lX�!\0�����Za���\0�����0�\$���z?d�\r@�ݪ\0�\0xU�zM�\ri7\0o��Q`�\\��Q�@g��Qn���}�	��m8�>�`d�\0d8�И�\0EL�o8Ш!Ġ�EK>�8�\0\n\n��5��\0��H�`k*ʥ\0�qģ�x\rP��f��\0��@@'�=\0�D?��+�P�Nh��@�-����t;���/��\0�o�\0\n #��1��AB���`-�Y�9c�-H	�\nQ��7��>�9��x�T�@#����o�\\��� .^��\0�\0�-�gj�a\0n���Ґa��h�� @�����]�T�\\����0�߀]\0���\r�\r/�\0� �42�N�@vYRص)K�✄@n��y\"�d�pנ<@r��p�B(��OD�a�N��S9����Cy\0�SH\0���PL�	�\0N���~\0�\0l��PCR�\0@\0y��\0�hP�@}\0�\0�����@?B�> H�\\� <�M��2\0\0�@.\0I�'K&|4P�`:\0N��)����@�\r�;��\0�*�T\\�@@��\0U�\0h07@f)���\"8(0J��\0�t��@\r� @t�\0\0hP�3B�N��p��(�`\0�\0P\0q��� @���\0�3P(gP`0@D�{\08�\0��P`3�\"��x�s�` 	��C�l�p@7�\0b�\0004��\09�K\0�<�\0� @�J�Pk0\r�#�;B\0�8�\r��9\0 \0��0�J@.�u\0�jn��� @\r���G�,H0	C�ӉHDlh�p��\0�\0�\0��Q� :�\$��\0� �� ��q�-�8�9�� ���\0vt�N� C̉64\0��^`R�D�'U�\0P�x\n�n@U����&@ 2��+��\0�a*�&\0D\0�{HY?���	�;\0���h(� &�x��,�pH�!�0F��f^�\\�p\0ـv\0������4�\0M�\0�\0qp�@e��4jD���5�\0\n�\0�\0fL���KL�9\0����	�G`�u��\0�\0��p`�[��s��p��k�ӝ\0.v`�@�<\0t�@�)_��\r@%\0O\0�\0\0xq�.�	`�'y?\\�.j'�q�Z@\0�\0���(\0P��\0��&��J�NGN�\0�\\t���l�\0Q��	0�����n�����\0R\$�h�rD�@Q\0�\0�� 0`�߼�t�`	 \0@v\0��\0(�@\0`�\0\0�_`i�\r\0Q��\0(`\0�j�1�a\0@�\0�8����\0�U��p�A�PȰ�pGD�\rp�����.X0h �;��	f�8�\raHG�y�:@�`�\$�E`X)��K�@\0�>���\0�>|@i�;\0`\0�\0��^��W�\0\0̎�X��`��_��)��WY�����nV�uֳr��.���nj�u�s��#�@\0006\0h�x��@�7�\\\0�8�W@ '�*�Dr0�P	\0001@(\0K�(h\rP�)@�QQPHb봱UrSG��\0h`\$��f�`g�@��>Za@\n�u\0�\0�M �rX�4�8��P�T���f�>����!����@E��s����\0t��\0�zh8��@!��k�.xi� 9��&x � !�F\0�8h� 1@F����`X	�\0&�2\0�H�\"��R�8��L�P����\0RxD��	�B@%\0�h�@� ��\0.I�FN9�?�H@�\0N���n�	���<�7��>���\nu\"Ю�o>�D�#8`�\0�\0�P���P�8�&@�\0�H���:E�rru>(�P� &�%���|�9��@b#���T�t0�\0f��\0���*O�e���A��	`.JR@i�>����\r0��@g\0��\0P9�@9A��y(����Y@Z���6Yx\0 .dQ\0�a���H��<~\0���D��i��7�o����5T~B\r�)�X8X1��m�ع���s�V��\n�k�� 9C`��:��m�lD�`<@q���d(�hb�@z8\\����Hl�a�I����/`�JW��B��\r��J�(}��nyD,G���b>��2ѿ�e�l{�B�\0\r��@S�F��� �6\0��\0@\0�\0�� @W�s\0BL�@�1�z��\0�(\0�԰�2�\0�\$���%`.@0(�t�T0\r�	�'��\0\0XX�\0�@B\0&�\0����[�y��\0@0@\0J�J\0\0��  !\0J�i���&���a�\0l34,��`@H\0�\0�l��q@.@/\0`3�\0�@3��1�\0Z����/@U\0H�\0p\0(���H�\0�'\$\0004��\0�\0h\n�@:@=\0�\0�\0H���@c\0003�tq��	@�\0lޗ\0�\0p�%@\0v@8�	`@)����a��p\0\0��\0v�p�`	\0\0�\\*��	P%��\0AQ�\0\0Hp��1�;��\0�9�@�\0��\0�\0D �`�d\0�\0����p����\0001���`�\0 �\n\0K\0<�\0I�`���6h`\0	��\0\nlX��Sʀ\0&\0\0@�#� �<\0���p��g��@\0R#�;�\0�\r����3���|NQ� �\0/\0�\0h\0H�x\0!�F��UB\0Xp�T\0&�ڃ�D�P���Ҁ�B\0Y�P\0���>\r�\0�����=@,�NPe�p��@\0\0Z\0TB[���0\00019\0�\0`P\0p�_W\0��)�%\0a\0Jn�B�@\0 Hr\087����1ȣ&@\$Gp\n�\0]\0�p�Kڀ @:�\0K6�\08S�}��`\0rF�\0`���@Z�7\0\\�\0�c�`\"j�I\0RRd@\0\0001\0��\0Rk1��\0P��	P	�\n�;�\0�d \n�@@0��� �:I�*iǕr\0K\0V���� ��e�L��@	��+�\0���S�(�(��C�4(d@�u�k\0��*,�RK#\n�\0n5�p��,\0v&!0`\0�0���i\0��`p	G>�	��\0�,�\$�n���SdP��@i\0�\0�648��@\0���H�F��@��\08m��@��1�B�f��\0��\0Y��5�\0\n4�\0� ͯ\0�\0D8�@�\n�)�i1e��y�m�W���nB�M�crU�����u�ܵ~����+��ĺ�7�\0�H� \n��,\0j�h� �\0�����0\n��/��3\0��l�\rb|�@*��/�x��A��\0�'�A��BNgH\0#\$�\0�y�S27�\08�,�\0�� �~�*�\0�O�U	��/6\0��h�@h���)���%��ദ�;(�\0�a	� �-&a�%An)���@#(]�h-�Agc�\$�B����H�� &@.��6=l�� '�6������\"�@P\0z�x�P� \0q�r���\0� �g\0�\0���@\$�x��\0��`u���L�����\";�h�\0~���@\n!@ h�I��J\"@!h�\0��-;88�#@\"h�X.�\0�\$@#h�\0��L>�@	\n%@\$h�\0��\\N��	J&@%h�\0��l^��	 &�F��@ �\n@�)@U�cT��`P	�,@\\\0Gf�#�`�k�=�n�D�Xa �0��9�D?�	����L@RFCD}H�c��0�oB�A��,,�' &\$�Γ�\0��,�:��\nd���\0�(2@\\��|C}z�p�MÅ�w��Q�h\r\0�3�h�0�9���`��Z̮'�hX\r�`,�o��s)@��/��\0£T�P�@7@h\0�:dR���\r`/��K�1���\r8d�5@x?�-���	��8\0w��n��;�e\0FDCb�d)�B]��3�DZ�[�'\r�7��`9�ҧ��}�����MK/(\0�J�!ưy�H�@u�\0��\0��@!\0\r�Yd�|*��\0��f���*%h��OV\0\\\0\$\0d� \0��g\0p1�\0�H#�\0\0\0:�\00�@(���P�0`�\0��p�j����0��\0��`E|@0��\0�T�@�@<!?\0��	r�CL�]�\0��Gp^`�8�s\0���\0�H\0	`.�nb� � +��M(Z\0L���3\0@�\$�D i����v�{L��n� ������\0000�b�)��4 \0p�1�+�!Z\"\nV8\0`��\0p�\0�A�0�@8\0������6�ǀ�\0>\0P%�� 5�`�]\0��\0p\n``\0A\0�(\0\0�p��\0�\0:x�@\0-ә\0�uI9�8\0c�@\0��\0004\0�@� Ht�R\\\0�8�����\0`�S�@v�X\0�(ː	��`@j��b:x \n�\0�O{hU�\0\r��@�\0(�@�X�\0>\$��BX��]#ˀ\"@0\0�K(\0ч ���<\0F\0��@0�\rIP�2��\0E^pP��k\0K���  \r���7\0�0�a#���+�i=g�\r	�\0�\0h?�0���\0R�\0h�4��k`&S�'�\0 (�  �-\0�\0�\0L`�`\r\0q��\0�lq��\n ?@v�� \0�z�a���D\0hp� ��]+\0`��x�`/�ʚW�!24������E�d��`3\0Kj\0F\0��\nP�D\0\0�H�4 \rX��\"���\0\0�P�H9@g]4>Pk`\0\n	�M9�\0V%�[^I!�@�L\0P�l@+�@�\0\0�\0����Dq�\r\0���C�\n`@\$���H������\n�\0�\0#\r���\0P;�d;�X#F�3�N\0#�t!��i`0@^\0l\$�\r\0���\0f��fG��x@(�G\0i�bN���Q�X\0{,��O�\\\0�\0�\r��(b\r�d���\r@\0003@h��\0�\0��W&�1]h!Ewu�A`2?������X���z�\$���\$?��'�A���Ay\0�Мy�\0`\$�A\0�X����n��D���8/p	G�@&	U��.�`�@`'@s��@����@;\0V�����d�`*�M��R\\P\np`:�S\0���:�j  '\0?��<=��'`�4H�P�\0�C,��	s�%��\0��4M٬gcĠ�H����PX��P@#IA\0�\0���[�k�{\0~-Z�-8@6NS�ٴ+0o%���T @*���p{�d���C\$�\0��H�u�3I� k8h`,�I=�{\0B����\0�P \0,��'|l��\0\0/<\n\0�+� P	 �_�Lj�<P0\n��\0n�<�(���%�P�\0�(�*\"�K�gQDp�@Ŋ\$\0\"�ȢKD��M�%�K��3�kDމ�	��A|�L���,0l�	ʣ@'j�\0��<�<�	@n�\0S\0�\0�0��*\0004���eG�xB2�-F�\0�c�pb�ᅀK�6���0V�>��4*��#9����y8���y\0�r�	�\n0( )����9�M��@\0/G�Wp���pƀ1�}Ҍ�i��\0�1`��x\rP�4�m\0��p��p 6@n����!\0�7�p����q��F3�a\0�)Z�董��ա��t#�\r@�O�p@<���a�d�S`=H:��1��|�(�p�gRֲ%y\r�S�^�ma|\nf�i�Gr5��GQ��}��Zqd�S���\n�5\0<�4@\0���!��\0���k�P@@c\0�n���PI\0c\0��0�`8\0)�\0��ۊ�@2'\0r\0 ��P@1�\0���MCa)Wg\0/����0�\n\0M\0�\0� �2P\r�1�H�.BH���3Eh�K�P�� �-�\0[p�}X{`K��p5o�\0����@�M\0�&\$h0�`�v\0�\0�0�d��r�J�,��\r�1:�]\0���p�-`3�Ȁ��T�z�J�\n��<\0j�0\rh��8�>3E��#�����3~�\r��@�m�O(�`��2&�\0o��`�<�M�\0�\0��ds@-��[�L��շ�=���\0��P@\$��Z,\0��t� \0s\0�.`�/��)� b\0mV���`\rע\0���\0 �C�)\0�|�Cq����v@b\0\r\\�3`�\0%1�#�Pn�8��\0�\0B��^�`dE�0p\0H0�� �\0003\"�`�@��9@\0	K�@�	�%\\�:��7�h	ph�\0G\0�\0 P@6X[\01HP`;�D\0004�\0�A���\0\$��\0�X��	\0���\0�\0���_8�� �:\0:\0��t�\0`\"��\"�D���(�P\r�\0&\0�\0��������-��K��@1M\0002J�ظ0\0%'���Zz�Pwp\"Ѐ�7��\0��\0x\n`3\0W��\0lXGD\$�\0�|\$\0cd��\0�V��\"�6H�\n�\n@rLE\0 LQtP�>�(N�\0��H8s��]\0y\0/�iP�&@.�}��� ����_�ǀ^�\0�\0,�\0`\n�8��\0xh�8\r���D�\0�TL(\0P���0�`\0]Pm�0@�\nB-�b��,�\"�a�.K�L��`-\0E:�ch:X����G/��h�8P: )\0P\0�F��� �c\n����\0����\0��\0002�Z��n\$\0���&�\0�8?�'�GSqss�	��C��P��B�Yu�T.nQP�֝C%��\r�T6u�P�B@u���\0x;��0\0\0�\0j\0� `@\$@U�n�������\0L�\0�p\r0\0�l\08\0�n�2\n 7���\0�`�E���v�-�H0\"��>����O��Ot|�����XB�u\0X��@W+	A[l#�E���� U�aYG���>iр�3��6I���L�8�E�@,[\0��F(\n�	�&B\0c^�(P\r`�4�n\0�F�;��1p��y����\0�@,BQ\0�D���B�@QD:��\"tEh����7Dv�\$@&\0HL�*Hp	���@K��>d(\n n�&��R���\r��-��Q\0�S��x\n0@,[=���:��*��u\0�N|�5��o '�v\0�8��	p`'\0J�Q&�A��:�I���{��I�\n��\0��e0��#�B�q�L�s�<�{\0�� H\r�F�\0'��\0��8�,�`�P�p\0������@W\0�\0|�<��d��L=\0�g�Ϛ��+ -�Rɾ|�\n	jx�m�\0a��@<)@�P���1DD��\rb��6fr��&X�	�@'_��j��P�g�,@o��tt|)�`A���\0��VP\\`k� <@qU�1�.�b���u��8��m7��f*۴؆z�~QP@JgVa��s�vux,qf0r��x\0�6�����eQ�(\0�E\r,p\r3aB�F����3 @��9\0����\0����H�Pd�>@)�T����4@>��\0j�Xp\n`!@4\0d����H�E\0�:9H��6��\0�b�\\��\n@1i�Q�\0�;�p@+@��pxh\$<��\0V`xPXi�J�\0�(����	@�/�S\0>�\0 {���\0\n�7�(@@/\0�)N�e����@��c��\r �5���x�P)����\0z���\0�x&g��U\0���\0��+\0\\\0�7�x��2�W�X\034�\r�	@)\0D�D��\0�b�` @,�\0V�S�@�R\0@����`�@Z��U��l�@\n *�X3�^���\0J�0 8�E\0{}�O�Dxđ�`���L��m /�Ϛ\0�\r̐0P�\0\0�H�����z�qO� P�\"�f<�b����)�P��\0���`4I\0�\0���28@8�\0005K�PpP	�����\$3�EH�7�\rV#1�V��q���Gʛ��1�S\$c�u�\0d�(W�exm3�E��\0�\0�.��Gզ�<T�	�\0�\r@]���WI�� �|�;��� ��e�Sr\0�\0*{�G�`*�\$\0�\0�8�v�/��\0d���\0\0C\0icR\0<-5�� �/\0��v\0��5n@�+\0��H�\0�l��X��L\0X���\r���\0��<��~���ƀ\0)>T�\0�	��\rO\0�<�8\0�\0Ġ��\0�>k���g��7\0�.\"\rd=�x \r��V	l��\0`���K�\0000�``?\0F[\$%l�p	�ň�L\0\0�\0e(\rP\n\0(��5��\0�c���N���aO\0�`\0�\r@B\0�\0p�\\�9�=@j�R�@��Ge��	����R�J�[rJ�����ڨbܲ���z�����_�����m\0V\0���`���5[\0Z��k�p�=��Kf\0�W��\n\0001��Q�L��\n�5����\0V*�+����Vu@o��z����6�m�s���q=��d�\0E�'�\0Ѽ|��l��\0r���H��Rh[�\0~�/;\$6� \0/��\0�o�+x���#����0�����^�o���w1��n[��N@ 3�#	\0���+�@	�@W\0S\0����@@5����f�x�\r�@=\0p\0�a�.��ـ>\0k(	@��N\0�\0��`Fz�\0T��\0��� \0 �Y\0�\0t#`�\n2Q�Nj\n�*ҩ�\n�&�'�� �RX8� O�U�AZDxF@.�\0�~?�2X`(�FC67�x���� ^������FT�ɷҋ���At�@4��g�D��\r'�3�?��E���5.�m��Ȁ����  \0+�T��<�Z��\\ @n(��,�b�b�T��8�0�A��Q��Z\0�\0����8 \"�0L�\0Բ`˸	p�:��\0���ZjD 9�\0���J��.\0o�2�V���%�%9\0m�����i�F3�j\0��Tƨ\rg� 6C���p�}�Pkx���\0t\0�\rv.��la�Cd�bB4�hD,��\\<��!0Bh��	������؛��y��\n��@�eռx�,k��@�V\0\0v�����\0�e\\\0e��`\0p\0�\0BQ�8�KW��Xx	����;0���`w�1�v��	�T��\0������\0�8\0z&��\0c��@\0N���:��XP@@>�8\\fU�D���-c�e\n�@ �#��\000��\0x���h��	���@\0m\0B�(���!\0�Wz�� �J\0=�\0��W��5@\0\0D\0T�p����\0�k��؄��5�\"�Uh�rh� 7�1	\0V�\\p\r��\0C�\$�\0004p\n�R�\0\0.\0�` \0?\0003T�p`h� ���v \0��\0��@?@\"�l3�V�vh\0@\r�)�*�\0p4\0���	\0�\0r\0D,�P�7�\0�\0���g\\�\0�N�C�����@|\0007\0\${� 6`�;@T#c\0�\0���@@\$��|v����P����AF�8x\0D��q�Q�e#�!e��_���i\\E<�\n�\08��!{NJA`5z`\n���lX�0j/�?@@\0B�r�� \r�V \0P%`xP\\��^�\$ix\n�>�9@ �� JZ@L�\0w\n�p��VF��\0'H��h0 )\0d\0N��B���I�EK-�da��\0+\0>	T��.�h\ni:@@UJCR� @\0Tt��0�m��*S�\0�>�` `	(f(h(@ώ�2�\0r���R4�\0��\0 \0�@{v��L���\0��`I�j�-\0L��]�`+�΀����x��\n�Ɂ\0L�8���  -,ˀ3�BP���;�e\r+\0RI�\0��\0)�R�\0l��}��\0}\"�������cցc6��&�`7bW+�Uj�P@'�\0'>�\0�+`)\0I�g�\$K�r�[��\0h�cu#�GM��l��M�W%����Ŷ���������Le��A>�Lƨ��\$�[�nm`\r�\r�U�K���H_�MaR�d����P\0���@��#�n�����H�I��\$��5)�a��\0�(���`	\0006�E�H�4��Rh1@s����G���d�@H��q�5J� \"jQ��Q� T��`� 9*U�,��jC``Bπ^4\\,���	��\$�Y�����`1���%~|�q��{D����4\n�~��h��`dB�_��\\V9��\0��i:��m&� �?\0a( y� �%@D\0n�x�\n3�C~�#9�+d		��\nE@[I�l�	�бf��u\0��<+�0z�px�2�=\0�\0��=�����b\0Qia����\0`\0e�>��Π�:�m�|0� 2�b��%�׫͙n��(Ȁ��9���X��S@N\0����\r�U_ʼ\0�fA���L�7@a�k�Y��\r1�d@p\0�z�`ik ,��\0�R��9I�qp�\0XF�h���8\r4_`6s�������0�/�T�y\"cTQ����B����f�+X���gM��u86�W�څ=޼m�oV�7b�D`�X��mqt=��\0P��;��\0tP,�\n�`.\0X1\0�0�\n��@@�<��	hʊ�@2��R(��|����I(�\0�h��&So\0�z\0Ɗ0	P`��\0005%�ˮlX@ @\0o\n��`���\0�O\0�e��\0��)��I��\0f1� \0@I��\0���:�q����M\0AW�+��@D��\0����p\08���86��\r \0,@8�5Y��@\r��@��@���P�@=b\0>���#�`!L�\0r�pB�P�#Sـ�������\r��\0S\0�\0�K��k1�?\0\0W,6�\\��p����4\0d����O�/�q�p\0��\0.��\0l����3��B\0�����\0�+@��k�%7���<�%Դ7\0h���1�=+	��\$�G���5\0K�&ؠ�\0 �y�L\0��D��+@�\0Nm���\0����c\0�\0a\"P\r5`\$���E\"\0z\0Us�	 ��	�6�R\0LX���\0����J�F(��̨\0\"��{��@>.\09\0j\0*vH\0��@s\0\$*aT�`�+\09\0��\\sh�'�\0^\0��� ���(�\0Jqt����\0+7�P\n ��a\0	\0\\\n]��⦎@��\0�|\0��\0:�����a��D8��\0B���	\0\0\"�d�-��X�S�@%\0000D7�X\n��q^s�,\0�`8�Vq(�/r\0����`�j\0/ 4H� 	\0-�B,��?�i��`(@\0m�( \n��p@5E�t�\n@@�Hڞz9\"���P�\0m��\0ad���#4�F\0�%5RX�f�\0	�L\0^ 	 \r�,N�O�-̴���fP @��c�\$����`)�S3@�[O�Edk�۲N\0t�\09� \0\0&�\n\0_|\0��� >�'���bx\r�B)\n���FKD��k	�XQ�iB85U��Y`QB��\0�\0d�\r��&D[��\0�4�\0 ]*������`��\$ʀ\0\0���m�~u���6�+�ۂ��n���j�6�+�ۊ��n2����\0007��Xnt���s��Є��E�\nN\$��t��F\0�\0�HPX�-t\0�\0n\rP\n�\"�@)��\n� *���4¬�\n�l�����\"b�7��|����`d�l�����XXP��a�҅�~��qf�����iL\ra�0F(��T�\01�(\0�^����S���	�0i0 B�yAV�.0�pi��z#X9A�����c\$%��������0�i�X	�� &�.��6�T���)3��\0�+n��P %\0-\0���0rb�X\0�\0���P@'�G���28\nPm�\09��n����L�^�ƚ�`����!�.�e0���QI1��\0P@cTr�	��p�I[x 9�4�i��*-@H�%�H��=Tc�I@���9�s0	�.㘡�\0K�����@\n�/\0T�g~�t(����c\0�Z��@ �9@_���p�X�Ғ����l�l`h8b��\0b\0��Dy��� 3@v\0�����9@p��\rRuK�d��7\\-��<�7+�p��@o�H�6�Ð��`9��\0蛔dY~���d%��\0�\r��6��52��\0~�S.�m�pn6#@��y\0�����\0p��g�\0v�#�����f�:��`�v�\\��^\0wH�\0�@����*X����7UH]��@!�g\0�\0�\0p��@4\0�Z���P@�1I`\0�\0�\0�\0��i\0�\0��0� *\0	\0�\0�#��� ���c\0N<�\r�@�#|\0g����\0�:�d��n�\0�\rYP�]�5��\0000���l�D@��/�T�p �I[\0I�\0�\0h@F��l\0p\0<N\"h��4�OFXw���\0�\0)@?\0�����;��Ｔ_\0�W�0\n��@L�4\0�\0@�\r	 0�|��x��\n� =���ͻ6��#�\0�[�8@S��\0m���\r�X\0F\0	f\0���w\0�/�4��0\0a 1\0S�]�3�\0��\r@\0k\0\0.`�\0���H�1\0�ִ�h�@3@I��\0�\n�U�`&'\0�pI�4\0 5 \0������[���J(�	\0\0e�-�V�Ԡ,8�\0�0K۪\0�&_X	�CmpM\0T\0�\0���\0�`\0�>h��W/@4�+V�xe�	\0�y<� ���C`�+�X\0�NZ�HF�\0��D�\0)��*�\0\0#�����`h\0ϾRP� PVR�F�.,�/@�T�5\0B��\0\nP�)�\0+�\"�*kR���\0�\0�yd1��N�\0I��� s\$�8�����Pfx\0P��/�z\0�,Bm�X�f(�����\n�x�Ӫ@q\0�G�\0���\0y\0�<\0\"�w���w\0�P�lkP@@��\0��\0�ϐ0%�@A7\0#���\0\0喀��4@� 0����W��a[�\0����\0�4<\0�[��΀�	\0h=��?��c�G2���%	85���0[P+�}@``0郀���N`b�BP��\0�,R�\npTـ&����5�\0�m\0�?n��\r��_\0(�>���8K�����H��1���p�\"��R��1�,T�+����z�������-�/���gN�M ���\0\0(\0N\\�<�Q��c\0*��X�-am�uf	�_�N�V,��@\n\0005Ola�N\0��h�\n\0007��P�\0��{�[l�2�^�,Ff{���,E�`���X�����Ǔ�koT�I>�	C�\0(�~	q{�rʌ�3�\0҂<\\`�\r�\08I\0��SDø��@��r�-�S�N��;��F�	 F@<��*��` @&\0C�QcLu��@;\0O���|a\\�	��*@Of�>�P\n\0�*�P��d��\n0�\"�K��J�jlD1��`<	8Iw�h��y��o\0�rTh�~\0f\0�.(�@��c\0�\0�lk \n�\n�0\0T��5�'P1`�@�Ph�>����%\"���\$sO��#�m\n���5�@LSe�0l�	�\0'�0�g\0� H���2Ǣ�Y�E�p\r\$#�i�+.H�	U� (.\0�\nă���1\0r�\\άq�2�0���§\n�/<hp<�z\0x\0�\r���a	P��dҼN�]V�	��I_&�-hK��7Po �����l\0L8�\0���2��\0�FA\0\n��7L�^�\\��[�\0H��@4�[�wf(�a(�3�(\0\0h\0��3/�f\0If|0 �!�\0001*��Q�p���L��\0�f7�\0\n��B\0h\0 \\��\0\0�-�A�^�;���u%\0㮾�\n���q\0Q���e�j@\0@�\0T\0L�5��<�\r�G+0T;��\0@	\0���q��7�0\0��T\0��� /\08��2�\0Dd`\r� @Y\0����M�\n`��x\0x\0004\0�U(	�\n@+\0/���Q�\0000\0d�Flґ�r� @9�F\0l���5@o\0����j�� �\0\0=�l\09F�ʸ�	��\0���ڠ�@�\0x	�\r���\0�\0&u�����B\0�8\0h`\r@\0���6\0�%}��;@\06��\0p�\0��5�[�H7@R*DN�4�&��Ũ\0\r\0k��8p�	\0R\0�]V�ľ������\0X@�\r���FCg\0H� �U�*���\0����9p�:M�]��\08уE\0h<\0�	`'�퀵��t��	�\r�\0�@�Ƨ0\rQ`{�6��P!�\0e�2\0Il�h\nS0M��\0��88�@@@)�\0|d\0�H�\r�m&��n���/��(5O��1��\0���q�\0@m\0�BZ7(�\0���{^\$c������\r \r@m�,0m1\$���Ӱ\0���\0�` ��F��4���\r�\0��\0L��i0\$˲@4\0[.��p27�6�P;�\0jL�	0�\$�zg�\0l\\��� �(���\0`Δ��\0000�v\0E�~��p�`(���0�/�#�IB.ud8�\r�\0e��|pP�x<Q������ ذ`�ǥ\0F���\n�@6,�Z����;@:� }U�z���Y��^h#W���]�Y��`�(W�����+9����7�\$!���y!p�\$�ŘP����!:Q�I�\0n\0�,��	��\0&��\0�6��af@R��\\^����Ȁb������	�54�\0�CTi\rS���j�@o����`�e\0`f\0!�\0�A&n�\0�f\0��\0vc3����r�����S������\n'Т���@@�q�JàL@`EN�f�(�*Ġ����i\0��U	�e�(;�o`���0�7��s\0�{3.\"��.�\0M����R�9�\0�3��0-M\0]��\0�X��`�*߀W��)��\0�j7�&�ܕ)� '�&@�\0��UH�\n�����7踸?���\n����c�\"�1\0y������(���i@\0/Љ��Rh�tB�II�����Q�\r!��ز\r:�	8�0Ѯ.mP��i��Zv\\�\rP0J�\0��6�0�7�\0_Ȣz�c� �/�y��J�T.��`0V��N��C� `4@y\0�:j|��<�\08�n8UǺ���t��:�ƭbJ�u)#�r)]Ch?����7\"�,�&������r5�&�1%�\0]\0�W���`�V��g)t�P�`;���u�x�d` @Q����Dװ\0��`�u��\0zȗ����!�I\0\0d�\0s�\0	�����i0\0��џ���JH\n@B6�\0004\0�\0���i�@\n��\0�\0lXW��k�2Z��u�\0-@\n��y�*8��`�6�Z�[\0t�\0(�@	ۉ6\0��`�ޠ[�\0�E�\0�� \0��\0X���p���\0(�\r�\0t	`Hk�� �� \r\09�*s�\0��� �f\0��\0�Տ���0�;\0R\0006�@\0@�\0007��U��?-�\0���|��\$�?\0�\$\0Q2P7�m���u4T\0��P��\n�w���	��@VG�\0t�`\r ʀ?\0@��\0u\r�'�\0\0006@S\0�\0006��8pyDպ�.\0��M�`Q3\0|\0'9oY�c&\0=C̀�e�o@��Z���h�c��ؘ��Hg�\0LΐF��e�;��,s(�@3\0:�m�8��\0i\0��6B0 @A\0d��\0�0���3\0�\0pBn �>\0+�O3e��\0007�'p\0@�aXp�	u��jd��PQ� ����\0�L\0�\r�\0	+�\0�\0�\\�@x�E��p\0h{Q��@�ԣD�=��P�0\0\0�e�\0\0\$���\0q�&,��x	�@:@l\0rJ\"<�i�@ �\0000Hf�8�U���\0 �@I��\0����Xt@!����g\0002JRt�� )�N\0�\0�:\$2(�\0.��ʗYt\\�}��9��M�\n\0Ye[�rE����\\��`��l\0D��Hõ\0@\0��p0\0'�\0�E4\$P�6�\$j��X\08JG�@{�Z\0O��;�\0000����jzj�����	\0m��Y`7�Z(p�,�v\0d�\ry_��p\r\0���\0m��\$:����/����nc\$X	���4���n6cs�� x��9Ő���BTYV�j��\r\0�*�\0��ͱ�_#��@\n \r�A\0:�H��E��V�\0k���G���P\0D�Q���8@�A�0`�\0!��\0�(:��.�AX)C�&g����1��� I\n~9���K@\0s\0j\0��F��\09[�(��\$ݶs20	-�Rr���_�,e������q��v\$P0\0007@�Y�PN�/UN  ,@,\0i\"���@ @&;�\0�Jd�\n�W�QxG�Ϫ�0���7�>f�>+]�+SL�1V|�a�À-�t�1b8Ā<C�F%���wpበ�9�_\0�8�(���H3�>�k����(�:�'\0��@�_@2\0D�V^�xp!�\0O�L�\$� Ϡ01���-x��x:�.�������<&�C	6x��,�w�i`��Q~\0��5��\rP:�\0f��:pp�Y�{z�\0y\0��ʍ�ހ�<��ͼ�����=����qƠ��H]��t.�`�H\r�+�Ϡ٧Sҽ��g�:�n�V�w����	NQ6��\0�\r\0	�V���\nu��5C��\0���;@Q����H�H��K0��H(�`@q���H(���\n�)\0�.��yhj[ �S\0���l����H��d	��\0P	��U�Rl��\0��W�\$�����\0�\0�8�X� \"�q\0v\0�\0d�͠�)���\0���ZaXǮ���\0`�e',�-�5\0���0��\0\0]\0��@�`;\0s�������d��h\0R��F��\0� A����N��i�����\0;\0��	�L�-f��A\0���x �@\0E\0��K�t���L��=��M{@x\0K\0��p\0�-�4��C��p���;�fr��!q\0��\0X\0��#�G@\0004��Bh\0�n\0�1�\r/d\0�p@r+��\"��`D�`.�HVh\0006+��?�5\0;\0�\0�����O�A���\0ibp�w}Z���h�xU0��6�Kr\0��\0 H���!�b��E��(\0�����c\0�\0P@�0�F�G��6�E]rh�@7�2\0�&� 	-����J\0B��m�}� 1��8�������@#�zB��8`L��2\0�{�\0 `\r��D`��Y�\0��\0000�3�AK*k�Xp��E\0\"���z�{���=�r���\0���s�@@U\0Bi��t\0���+\0C��2�8�\0`.��\0005R\0�����\0�V��\0<X\0��d�9���D�\0��7=t���\06�8\0XPW�Ƀ29\0�}�P�No@)��@��xw��	���%�8+��� *�`C�\0n`s�'Q�OA���W>����Z�&@:\0��d  @32���Bn��q����y��1=\\G p�%������0p��/��\0��\0�`�(��\0��4!�zhP�6�8&C\0ld`\r��8�51�T�t�d���s	۪�,�0��lT7�1Q�*��|�E��7�1V�2��|������A/�:�nz����!�eۢ�7�<%�P�@9�D\0�	�6Op	�&�lA_\0���0��*\0�QcA�U�vן�����'����	�\r:m�%��D\0�|\"˨J��\0&B\\\0����0\nc�\0*�x/��y��8K�jZ�_�j�@���\r�5�����*޳�!@<��~����0��!\$����oH�c��\$��(�\\�\"����\0l\0�\0��`\n\0��S\0y\0��\n�ю04]\0|ϼ�ErQ:d���UkXP���D�\n+#��@���|�\nC��-��7�q-��U��a�\0e���s�\r!�\0i�\r��t6���\0j�@�xzds&`|�;��\0X� 3w�\0�2,�\n\0{�.?��(hb�\0/�����\$��ڞ�^Ju�\n(��6@_\0��VR��x� .@v��w��&�T�e9��l� ���k��b�5�j`���\nZ��҅a�	A�.t���꩜s�U\0D �x��)ԕ�34�;��f��i�dYL�l~\0=�ʀ�)��L���31��Y-i�j����V�E�<uZ�t8\0\0���%\0�T���@�T\0000��\0Z\0NY�0��M\0\rwB��)QP�:\0���,hx�@#H��+\\�4�6���^XZX�H \0b�Z\0#;`� \r��\0�\0�c0t?�Z�|[�\0�E�R�0���;ھ�iL2\$���\0���ۉqg`-�*��\rX8\n�\0�]P�&\0X\0`���\n@a\0��\0@r����4�f���`�6\0a�o��tp`��e��\0004��VK�\0\"�J\0\0d:�@!@k1�\0�\0�2D� >�#T���!�\0,\0�\0�p\0H\n�`%@D\0��00@̡��4�I� ��\0t]���\0�\r���,\0�My��W��2��\0�\0[1�)��\0\0&�2p<v��Px��FQX�\0001��oB(p@0g\0�xH�8\0S�Ɂ*��@�\"�	\0f\0��L�`�#@�6D;@�Bu0�+�ZC\0v  \0�\0\0-\0�\0C)�,�8��jW���\0-Ν�\0�@p\0���6��\r	��i\0^\0E�\0�(p@4\0:����ϐ���@H�_�6\08��o�g�,\0h	:\0Hvn@�\n0@,@a��}(���@u��\0���l�� \0z�q\0�\0D8 �\n�\$�+�*�NqD�SH�9��*F\0H(P \0�Y\0v��kj4�饀G��H�Hl�\n�@+@�g������\0X�`\0�8��T�� !�\0o~�����5�`^X���H� \0�	�<���P�b��E��\0�##�Fэ��\0007�\0\$�h\n�Вw�Q��\0��-.W�\0	���[T�\0�=��>�W݈���\r	��Yj����C�\n�c�ഖ�����a�E��t�X�J�`0y�t�\0)\0\$L���\0�~�\0(��8p����;�<l�``���s��\r��G8�q\\���s���?��9r\\���s��-�_��9�s\\���s��=��:t\\���s��MΟ�G:�u\\���s��]ο��;v\\���s��m�ߝ�;�w\\���s��}���<x\\���s��\0\0");}
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
