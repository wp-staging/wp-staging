<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\SiteInfo;

class ZlibCompressor
{
    /** @var string */
    const FILTER_ZLIB_COMPRESSION_ENABLED = 'wpstg.backup.compression.zlib.enabled';

    /** @var string */
    const HOOK_CAN_USE_COMPRESSION = 'wpstg.can_use_compression';

    /** @var CompressionInterface */
    protected $service;

    /** @var SiteInfo */
    private $siteInfo;

    public function __construct(CompressionInterface $service)
    {
        $this->service  = $service;
        $this->siteInfo = WPStaging::make(SiteInfo::class);
    }

    /**
     * @return bool Whether the server supports compression.
     */
    public function supportsCompression(): bool
    {
        return function_exists('gzcompress') && function_exists('gzuncompress');
    }

    /**
     * @see \WPStaging\Backup\BackupServiceProvider::registerClasses For the filter.
     * @return bool Whether the user can use compression.
     */
    public function canUseCompression(): bool
    {
        static $canUseCompression = null;

        if (!is_null($canUseCompression)) {
            return $canUseCompression;
        }

        // Early bail if it is a basic version.
        if (WPStaging::isBasic()) {
            return false;
        }

        $canUseCompression = $this->supportsCompression() && Hooks::callInternalHook(self::HOOK_CAN_USE_COMPRESSION, [], false);

        return $canUseCompression;
    }

    /**
     * @return bool True if compression is enabled, false if not.
     */
    public function isCompressionEnabled(): bool
    {
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
