<?php
namespace WPStaging\Framework\Traits;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
trait EventLoggerTrait
{
    protected $processStatusFailed = false;
    protected $wpstgMaintenanceFile = 'maintenance';
    protected $backupProcessPrefix = 'B';
    protected $restoreProcessPrefix = 'R';
    protected $cloneProcessPrefix = 'C';
    protected $pushProcessPrefix = 'P';
    private $filePath;
    private $filesystem;
    public function backupProcessCompleted()
    {
        $this->writeEventStatus($this->backupProcessPrefix);
    }
    public function restoreProcessCompleted()
    {
        $this->writeEventStatus($this->restoreProcessPrefix);
    }
    public function cloneProcessCompleted()
    {
        $this->writeEventStatus($this->cloneProcessPrefix);
    }
    public function pushProcessCompleted()
    {
        $this->writeEventStatus($this->pushProcessPrefix);
    }
    public function pushProcessCancelled()
    {
        $this->writeEventStatus($this->pushProcessPrefix, $this->processStatusFailed);
    }
    public function updateFailedProcess(string $processPrefix = ''): bool
    {
        if (empty($processPrefix)) {
            return false;
        }
        return $this->writeEventStatus($processPrefix, $this->processStatusFailed);
    }
    private function initializeObjects()
    {
        $this->filePath   = WPStaging::make(Directory::class)->getPluginUploadsDirectory() . $this->wpstgMaintenanceFile;
        $this->filesystem = WPStaging::make(Filesystem::class);
    }
    private function writeEventStatus(string $process, bool $status = true): bool
    {
        $this->initializeObjects();
        $content = date('dm') . $process . ($status === $this->processStatusFailed ? '-' : '+');
        if (file_exists($this->filePath) && filesize($this->filePath) > 0) {
            $content = PHP_EOL . $content;
        }
        return $this->filesystem->create($this->filePath, $content, 'ab');
    }
}
