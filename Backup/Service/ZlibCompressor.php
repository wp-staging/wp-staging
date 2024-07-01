<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Framework\Facades\Hooks;

class ZlibCompressor
{
    const FILTER_ZLIB_COMPRESSION_ENABLED = 'wpstg.backup.compression.zlib.enabled';

    /** @var CompressionInterface */
    protected $service;

    public function __construct(CompressionInterface $service)
    {
        $this->service = $service;
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

        $isPro   = defined('WPSTGPRO_VERSION');
        $license = get_option('wpstg_license_status');

        $hasActiveLicense = is_object($license) && property_exists($license, 'license') && $license->license === 'valid';

        $canUseCompression = $this->supportsCompression() && $isPro && ($hasActiveLicense || wpstg_is_local());

        return $canUseCompression;
    }

    /**
     * @return bool True if compression is enabled, false if not.
     */
    public function isCompressionEnabled(): bool
    {
        static $isEnabled = null;

        // Early bail: if compression feature not enabled.
        if (!$this->isCompressionFeatureEnabled()) {
            return false;
        }

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

    /** @return bool */
    protected function isCompressionFeatureEnabled(): bool
    {
        $enabled = (bool)Hooks::applyFilters('wpstg.tests.backup.enable_compression', defined('WPSTG_ENABLE_COMPRESSION') && WPSTG_ENABLE_COMPRESSION);
        return $enabled;
    }
}
