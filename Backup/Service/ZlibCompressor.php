<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;

class ZlibCompressor
{
 
    const FILTER_ZLIB_COMPRESSION_ENABLED = 'wpstg.backup.compression.zlib.enabled';

 
    const HOOK_CAN_USE_COMPRESSION = 'wpstg.can_use_compression';

 
    protected $service;

    public function __construct(CompressionInterface $service)
    {
        $this->service  = $service;
    }




    public function supportsCompression(): bool
    {
        return function_exists('gzcompress') && function_exists('gzuncompress');
    }





    public function canUseCompression(): bool
    {
        static $canUseCompression = null;

        if (!is_null($canUseCompression)) {
            return $canUseCompression;
        }

 
        if (WPStaging::isBasic()) {
            return false;
        }

        $canUseCompression = $this->supportsCompression() && Hooks::callInternalHook(self::HOOK_CAN_USE_COMPRESSION, [], false);

        return $canUseCompression;
    }




    public function isCompressionEnabled(): bool
    {
 
        if (Hooks::applyFilters(BackupMetadata::FILTER_BACKUP_FORMAT_V1, false)) {
            return false;
        }

        static $isEnabled = null;

        if (is_null($isEnabled)) {
            $settings = (object)get_option('wpstg_settings', []);
            $isEnabled = $settings->enableCompression ?? false;
        }

        $canUseCompression = $this->canUseCompression();

        return Hooks::applyFilters(self::FILTER_ZLIB_COMPRESSION_ENABLED, $isEnabled && $canUseCompression);
    }

    public function getService(): CompressionInterface
    {
        return $this->service;
    }
}
