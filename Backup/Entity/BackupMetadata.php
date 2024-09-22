<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Times;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Facades\Hooks;

/**
 * Class BackupMetadata
 *
 * Initialize Backup Metadata with reasonable defaults.
 *
 * @package WPStaging\Backup\Entity
 */
class BackupMetadata extends AbstractBackupMetadata
{
    /**
     * BackupMetadata constructor.
     *
     * Sets reasonable defaults.
     */
    public function __construct()
    {
        $time      = WPStaging::make(Times::class);
        /** @var SiteInfo */
        $siteInfo  = WPStaging::make(SiteInfo::class);
        $wpAdapter = WPStaging::make(WpAdapter::class);

        $this->setWpstgVersion(WPStaging::getVersion());
        $this->setBackupVersion($this->getDefaultVersion());
        $this->setSiteUrl(get_option('siteurl'));
        $this->setHomeUrl(get_option('home'));
        $this->setAbsPath(ABSPATH);
        $this->setBlogId(get_current_blog_id());
        $this->setNetworkId($wpAdapter->getCurrentNetworkId());
        $this->setDateCreated(time());
        $this->setDateCreatedTimezone($time->getSiteTimezoneString());
        $this->setBackupType(is_multisite() ? self::BACKUP_TYPE_MULTISITE : self::BACKUP_TYPE_SINGLE);
        $this->setPhpShortOpenTags($siteInfo->isPhpShortTagsEnabled());
        $this->setPhpArchitecture($siteInfo->getPhpArchitecture());
        $this->setOsArchitecture($siteInfo->getOsArchitecture());

        $this->setWpBakeryActive($siteInfo->isWpBakeryActive());
        $this->setIsJetpackActive($siteInfo->isJetpackActive());
        $this->setIsCreatedOnWordPressCom($siteInfo->isHostedOnWordPressCom());
        $this->setHostingType($siteInfo->getHostingType());

        $this->setSites(null);
        $this->setSubdomainInstall(is_multisite() && is_subdomain_install());

        $this->setIsZlibCompressed(WPStaging::make(ZlibCompressor::class)->isCompressionEnabled());

        $uploadDir = wp_upload_dir(null, false, true);

        if (!is_array($uploadDir)) {
            return;
        }

        $this->setUploadsPath(array_key_exists('basedir', $uploadDir) ? $uploadDir['basedir'] : '');
        $this->setUploadsUrl(array_key_exists('baseurl', $uploadDir) ? $uploadDir['baseurl'] : '');
    }

    public function getIsBackupFormatV1(): bool
    {
        $result = version_compare($this->getBackupVersion(), BackupHeader::MIN_BACKUP_VERSION, '<');

        return Hooks::applyFilters(self::FILTER_BACKUP_FORMAT_V1, $result);
    }

    /**
     * @todo Remove once v2 format is set as default
     *
     * @return string
     */
    private function getDefaultVersion(): string
    {
        $isBackupFormatV1 = Hooks::applyFilters(self::FILTER_BACKUP_FORMAT_V1, true);

        return $isBackupFormatV1 ? self::BACKUP_VERSION : BackupHeader::MIN_BACKUP_VERSION;
    }
}
