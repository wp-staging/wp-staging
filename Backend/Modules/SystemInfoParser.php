<?php

namespace WPStaging\Backend\Modules;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Backup\Storage\Providers;

/**
 * System Info Parser
 * Parses system info text into structured data for display
 */
class SystemInfoParser
{
    use SerializeTrait;

    /** @var string */
    const SECTION_STORAGE_PROVIDERS = 'WP Staging - Storage Providers';

    /**
     * Section definitions - single source of truth for IDs, names and subtitles
     * @var array<string, array{id: string, name: string, subtitle: string}>
     */
    const SECTIONS = [
        'SERVER_AND_OS'               => ['id' => 'server_and_os', 'name' => 'Server & Operating System', 'subtitle' => 'Server software and system architecture'],
        'DATABASE_MYSQL_MARIADB'      => ['id' => 'database_mysql_mariadb', 'name' => 'Database (MySQL / MariaDB)', 'subtitle' => 'Database configuration and connection details'],
        'PHP_ENVIRONMENT'             => ['id' => 'php_environment', 'name' => 'PHP Environment', 'subtitle' => 'PHP runtime and environment information'],
        'PHP_LIMITS'                  => ['id' => 'php_limits', 'name' => 'PHP Limits', 'subtitle' => 'PHP resource limits and allocation'],
        'WORDPRESS_ENVIRONMENT'       => ['id' => 'wordpress_environment', 'name' => 'WordPress Environment', 'subtitle' => 'WordPress core and site configuration'],
        'URLS_PATHS'                  => ['id' => 'urls_paths', 'name' => 'URLs & Paths', 'subtitle' => 'Site URLs and filesystem paths'],
        'WORDPRESS_DIRECTORIES'       => ['id' => 'wordpress_directories', 'name' => 'WordPress Directories', 'subtitle' => 'WordPress directory structure and locations'],
        'MEDIA_UPLOADS'               => ['id' => 'media_uploads', 'name' => 'Media & Uploads', 'subtitle' => 'Media upload paths and configuration'],
        'WORDPRESS_MEMORY_SETTINGS'   => ['id' => 'wordpress_memory_settings', 'name' => 'WordPress Memory Settings', 'subtitle' => 'WordPress memory allocation and limits'],
        'FILESYSTEM_PERMISSIONS'      => ['id' => 'filesystem_permissions', 'name' => 'Filesystem & Permissions', 'subtitle' => 'Filesystem access and permission settings'],
        'THEME_PERMALINKS'            => ['id' => 'theme_permalinks', 'name' => 'Theme & Permalinks', 'subtitle' => 'Active theme and permalink configuration'],
        'WORDPRESS_CRON_JOBS'         => ['id' => 'wordpress_cron_jobs', 'name' => 'WordPress Cron Jobs', 'subtitle' => 'Scheduled tasks and cron configuration'],
        'WP_STAGING_PLUGIN_INFO'      => ['id' => 'wp_staging_plugin_information', 'name' => 'WP Staging – Plugin Information', 'subtitle' => 'WP Staging plugin version and license details'],
        'WP_STAGING_BACKUP_STATUS'    => ['id' => 'wp_staging_backup_status_and_statistics', 'name' => 'WP Staging – Backup Status & Statistics', 'subtitle' => 'Backup status, storage usage, and processing metrics'],
        'WP_STAGING_PERFORMANCE'      => ['id' => 'wp_staging_performance_limits', 'name' => 'WP Staging – Performance & Limits', 'subtitle' => 'Performance settings and processing limits'],
        'WP_STAGING_ACCESS'           => ['id' => 'wp_staging_access_permissions', 'name' => 'WP Staging – Access & Permissions', 'subtitle' => 'User access rules and permission settings'],
        'WP_STAGING_EXISTING_SITES'   => ['id' => 'wp_staging_existing_staging_sites', 'name' => 'WP Staging – Existing Staging Sites', 'subtitle' => 'Configured staging sites and environments'],
        'WP_STAGING_STORAGE_PROVIDER' => ['id' => 'wp_staging_storage_provider', 'name' => 'WP Staging - Storage Providers', 'subtitle' => 'Remote storage providers and configuration'],
        'PLUGINS_OVERVIEW'            => ['id' => 'plugins_overview', 'name' => 'Plugins Overview', 'subtitle' => 'Installed plugins and activation status'],
        'CURL_ENVIRONMENT'            => ['id' => 'curl_environment', 'name' => 'cURL Environment', 'subtitle' => 'cURL runtime, version, and SSL stack'],
        'CURL_FEATURES'               => ['id' => 'curl_features', 'name' => 'cURL Features', 'subtitle' => 'Enabled cURL features and capabilities'],
        'SUPPORTED_PROTOCOLS'         => ['id' => 'supported_protocols', 'name' => 'Supported Protocols', 'subtitle' => 'Protocols supported by the cURL library'],
        'PHP_NETWORK_EXTENSIONS'      => ['id' => 'php_network_extensions', 'name' => 'PHP Network Extensions', 'subtitle' => 'Installed PHP extensions for network communication'],
        'CLIENT_BROWSER_INFO'         => ['id' => 'client_browser_information', 'name' => 'Client / Browser Information', 'subtitle' => 'Client device and browser details'],
        'MULTISITE'                   => ['id' => 'multisite', 'name' => 'Multisite', 'subtitle' => 'WordPress multisite network configuration'],
    ];

    /**
     * @param mixed $data
     */
    public function isSerializedData($data): bool
    {
        return $this->isSerialized($data);
    }

    /**
     * Get storage providers from canonical source with system info mappings
     *
     * @return array Storage provider configurations with option names and titles
     */
    public function getStorageProvidersForSystemInfo(): array
    {
        if (!class_exists('WPStaging\Backup\Storage\Providers')) {
            return [];
        }

        $providersInstance = WPStaging::make(Providers::class);
        $storages          = [];

        foreach ($providersInstance->getStorages() as $storage) {
            // Map storage ID to WordPress option name
            // Pattern: googleDrive -> wpstg_googledrive, amazonS3 -> wpstg_amazons3
            $identifier = strtolower(str_replace(['-'], [''], $storage['id']));
            $optionName = 'wpstg_' . $identifier;

            $storages[] = [
                'id'         => $storage['id'],
                'name'       => $storage['name'],
                'optionName' => $optionName,
                'title'      => $storage['name'] . ' Settings',
            ];
        }

        return $storages;
    }

    /**
     * Get storage provider ID by provider name
     *
     * @param string $name Provider name (e.g., "Google Drive")
     * @return string Provider ID or empty string if not found
     */
    public function getStorageProviderIdByName($name): string
    {
        $providers = $this->getStorageProvidersForSystemInfo();
        $found     = array_filter($providers, function ($provider) use ($name) {
            return $provider['name'] === $name;
        });

        return !empty($found) ? reset($found)['id'] : '';
    }

    /**
     * Get section ID from section name
     *
     * @param string $sectionName Section name
     * @param array $navItems Navigation items
     * @return string Section ID
     */
    public function getSectionId($sectionName, $navItems)
    {
        foreach ($navItems as $navItem) {
            if (in_array($sectionName, $navItem['sections'])) {
                return $navItem['id'];
            }
        }

        return sanitize_title($sectionName);
    }

    /**
     * Get navigation items ordered by content appearance
     *
     * @param array $sections Parsed sections
     * @return array Ordered navigation items
     */
    public function getOrderedNavigationItems($sections): array
    {
        $allNavItems     = $this->getAllNavigationItems();
        $orderedNavItems = [];
        $sectionOrder    = array_keys($sections);

        // First, add items in the order they appear in sections
        foreach ($sectionOrder as $sectionName) {
            foreach ($allNavItems as $navItem) {
                if (in_array($sectionName, $navItem['sections']) && !in_array($navItem['id'], array_column($orderedNavItems, 'id'))) {
                    $orderedNavItems[] = $navItem;
                    break;
                }
            }
        }

        // Add items that weren't found in sections (like logs)
        foreach ($allNavItems as $navItem) {
            if (!in_array($navItem['id'], array_column($orderedNavItems, 'id'))) {
                $orderedNavItems[] = $navItem;
            }
        }

        return $orderedNavItems;
    }

    /**
     * Get section subtitle by section name
     *
     * @param string $sectionName Display name of the section
     * @return string Section subtitle or empty string
     */
    public function getSectionSubtitle($sectionName): string
    {
        // Iterate through section IDs to find matching display name
        foreach (self::SECTIONS as $sectionId) {
            if (self::getDisplayName($sectionId) === $sectionName) {
                return self::getSubtitle($sectionId);
            }
        }

        return '';
    }

    /**
     * Check if section is the storage providers section
     *
     * @param string $sectionName Section name to check
     * @return bool True if storage providers section
     */
    public function isStorageProvidersSection($sectionName): bool
    {
        return $sectionName === self::SECTION_STORAGE_PROVIDERS;
    }

    /**
     * Check if label is a staging sites label
     *
     * @param string $label Label to check
     * @return bool True if staging sites label
     */
    public function isStagingSitesLabel(string $label = ''): bool
    {
        $labelLower = strtolower(trim($label));
        return strpos($labelLower, 'wpstg_staging_sites') !== false;
    }

    /**
     * Find storage provider by label
     *
     * @param string $label Label to search for
     * @return array|null Provider data or null if not found
     */
    private function findStorageProviderByLabel(string $label)
    {
        $label     = strtolower(trim($label));
        $providers = $this->getStorageProvidersForSystemInfo();

        foreach ($providers as $provider) {
            if (strpos($label, strtolower($provider['name'] . ' settings')) !== false) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Check if label is a storage provider label
     *
     * @param string $label Label to check
     * @return bool True if storage provider label
     */
    public function isStorageProviderLabel($label): bool
    {
        return $this->findStorageProviderByLabel($label) !== null;
    }

    /**
     * Get storage provider name from label
     *
     * @param string $label Label containing provider name
     * @return string Provider name or empty string
     */
    public function getStorageProviderName($label): string
    {
        $provider = $this->findStorageProviderByLabel($label);
        return $provider !== null ? $provider['name'] : '';
    }

    /**
     * Process structured data into display-ready sections
     *
     * @param array $structuredData Raw structured data from SystemInfo
     * @return array Processed sections with categorized items
     */
    public function processStructuredData($structuredData): array
    {
        $processedSections = [];

        foreach ($structuredData as $sectionName => $sectionItems) {
            if (empty($sectionItems)) {
                continue;
            }

            $infoItems                 = [];
            $stagingSites              = [];
            $processedStagingSites     = [];
            $storageProviders          = [];
            $storageProviderItems      = [];
            $isStorageProvidersSection = $this->isStorageProvidersSection($sectionName);

            // Process items
            foreach ($sectionItems as $item) {
                $processedItem = $this->processItem($item, $stagingSites, $processedStagingSites, $storageProviderItems, $isStorageProvidersSection);
                if ($processedItem !== null) {
                    $infoItems[] = $processedItem;
                }
            }

            // Handle storage providers section - group collected items
            if ($isStorageProvidersSection && !empty($storageProviderItems)) {
                $storageProviders = $this->groupStorageProviders($storageProviderItems);
            }

            $processedSections[] = [
                'sectionName'      => $sectionName,
                'infoItems'        => array_values($infoItems), // Re-index array
                'stagingSites'     => $stagingSites,
                'storageProviders' => $storageProviders,
            ];
        }

        return $processedSections;
    }

    /**
     * Get field configuration for staging site display
     *
     * @return array Field definitions with labels and display options
     */
    public function getStagingSiteFields(): array
    {
        return [
            'prefix'    => [
                'label'   => __('DB Prefix', 'wp-staging'),
                'is_link' => false,
            ],
            'path'      => [
                'label'   => __('Path', 'wp-staging'),
                'is_link' => false,
            ],
            'url'       => [
                'label'   => __('URL', 'wp-staging'),
                'is_link' => true,
            ],
            'version'   => [
                'label'   => __('Version', 'wp-staging'),
                'is_link' => false,
            ],
            'wpVersion' => [
                'label'   => __('WP Version', 'wp-staging'),
                'is_link' => false,
            ],
        ];
    }

    private function getAllNavigationItems(): array
    {
        return [
            [
                'id'       => 'start-system-info',
                'title'    => __('Server Information', 'wp-staging'),
                'icon'     => 'nav-server',
                'sections' => ['System Info', 'Server & Operating System', 'Database (MySQL / MariaDB)', 'PHP Environment', 'PHP Limits'],
            ],
            [
                'id'       => 'wordpress',
                'title'    => __('WordPress Info', 'wp-staging'),
                'icon'     => 'nav-wordpress',
                'sections' => ['WordPress', 'WordPress Environment', 'URLs & Paths', 'WordPress Directories', 'Media & Uploads', 'WordPress Memory Settings', 'Filesystem & Permissions', 'Theme & Permalinks', 'WordPress Cron Jobs', 'Site Configuration'],
            ],
            [
                'id'       => 'wp-staging',
                'title'    => __('WP Staging Info', 'wp-staging'),
                'icon'     => 'nav-sync',
                'sections' => ['WP Staging', 'WP Staging – Plugin Information', 'WP Staging – Backup Status & Statistics', 'WP Staging – Performance & Limits', 'WP Staging – Access & Permissions', 'WP Staging – Existing Staging Sites', 'WP Staging - Storage Providers'],
            ],
            [
                'id'       => 'plugins',
                'title'    => __('Plugins', 'wp-staging'),
                'icon'     => 'nav-plugins',
                'sections' => ['Active Plugins', 'Active Plugins on this Site', 'Inactive Plugins', 'Inactive Plugins (Includes this and other sites in the same network)', 'Active Network Plugins (Includes this and other sites in the same network)', 'Must-Use Plugins', 'Drop-Ins', 'Plugins Overview', 'Network & cURL'],
            ],
            [
                'id'       => 'php-extensions',
                'title'    => __('Network & cURL', 'wp-staging'),
                'icon'     => 'nav-code',
                'sections' => ['cURL Environment', 'cURL Features', 'Supported Protocols', 'PHP Network Extensions'],
            ],
            [
                'id'       => 'user-browser',
                'title'    => __('Browser Info', 'wp-staging'),
                'icon'     => 'nav-browser',
                'sections' => ['Client / Browser Information'],
            ],
            [
                'id'       => 'logs',
                'title'    => __('Logs', 'wp-staging'),
                'icon'     => 'nav-logs',
                'sections' => ['WP STAGING Logs', 'PHP debug.log'],
            ],
        ];
    }


    private function groupStorageProviders($storageProviderItems): array
    {
        $storageProviders       = [];
        $currentProvider        = null;
        $providerId             = null;
        $currentProviderData    = [];
        $processedProviders     = []; // Track processed providers to avoid duplicates
        $hasCurrentProviderData = false;

        // Closure to add the current provider to the collection if valid and not already added
        $addProvider = function () use (&$storageProviders, &$currentProvider, &$providerId, &$currentProviderData, &$hasCurrentProviderData, &$processedProviders) {
            if ($currentProvider === null || !$hasCurrentProviderData) {
                return;
            }

            $providerKey = strtolower($currentProvider);
            if (isset($processedProviders[$providerKey])) {
                return;
            }

            $storageProviders[] = [
                'id'       => $providerId,
                'name'     => $currentProvider,
                'settings' => $currentProviderData,
            ];

            $processedProviders[$providerKey] = true;
        };

        foreach ($storageProviderItems as $item) {
            if ($this->isStorageProviderLabel($item['label'])) {
                $addProvider();

                // Start new provider
                $currentProvider        = $this->getStorageProviderName($item['label']);
                $providerId             = $this->getStorageProviderIdByName($currentProvider);
                $currentProviderData    = [];
                $hasCurrentProviderData = false;
            } elseif ($currentProvider !== null) {
                // Add setting to current provider (skip empty header values)
                if ($item['value'] !== '') {
                    $hasCurrentProviderData = true;
                    $currentProviderData[]  = [
                        'label' => $item['label'],
                        'value' => $item['value'],
                    ];
                }
            }
        }

        // Add the last provider to the collection
        $addProvider();

        return $storageProviders;
    }

    /**
     * Process a single item and categorize it
     *
     * @param array $item
     * @param array $stagingSites
     * @param array $processedStagingSites
     * @param array $storageProviderItems
     * @param bool $isStorageProvidersSection
     * @return array|null Returns processed item or null if skipped
     */
    private function processItem($item, &$stagingSites, &$processedStagingSites, &$storageProviderItems, $isStorageProvidersSection = false)
    {
        $label = $item['label'];
        $value = $item['value'];

        // Handle serialized data
        if (is_string($value) && $this->isSerializedData($value)) {
            $unserialized = @unserialize($value);
            if ($unserialized === false || !is_array($unserialized)) {
                return ['type' => 'regular', 'label' => $label, 'value' => $value];
            }

            // Handle staging sites
            if ($this->isStagingSitesLabel($label)) {
                $stagingSites = array_merge($stagingSites, $this->processStagingSites($unserialized, $processedStagingSites));
                return null; // Skip this item from display
            }

            return ['type' => 'serialized', 'label' => $label, 'value' => $unserialized];
        }

        // Handle storage provider items - collect them separately in storage providers section
        // In storage providers section, all items are provider-related, so collect all of them
        // This prevents duplicates by removing them from regular infoItems display
        if ($isStorageProvidersSection) {
            $storageProviderItems[] = $item;
            return null; // Skip from regular display to avoid duplicates
        }

        return ['type' => 'regular', 'label' => $label, 'value' => $value];
    }

     /**
     * Process serialized staging sites data
     *
     * @param array $unserialized
     * @param array $processedStagingSites
     * @return array
     */
    private function processStagingSites($unserialized, &$processedStagingSites): array
    {
        $stagingSites = [];
        foreach ($unserialized as $siteData) {
            if (!is_array($siteData)) {
                continue;
            }


            if (!empty($siteData['directoryName']) && !isset($processedStagingSites[$siteData['directoryName']])) {
                $stagingSites[]                                    = $siteData;
                $processedStagingSites[$siteData['directoryName']] = true;
            }
        }

        return $stagingSites;
    }

    /**
     * Get section metadata by section ID (from SECTIONS['KEY']['id'])
     *
     * @param string $sectionId The section ID (e.g., 'server_and_os')
     * @return array ['name' => string, 'subtitle' => string]
     */
    public static function getSectionMetadata(string $sectionId): array
    {
        foreach (self::SECTIONS as $section) {
            if ($section['id'] === $sectionId) {
                return [
                    'name'     => __($section['name'], 'wp-staging'),
                    'subtitle' => __($section['subtitle'], 'wp-staging'),
                ];
            }
        }

        return ['name' => $sectionId, 'subtitle' => ''];
    }

    /**
     * Get display name for section definition or section ID
     *
     * @param array|string $section Section definition array or section ID string
     * @return string Display name for the section
     */
    public static function getDisplayName($section): string
    {
        if (is_array($section)) {
            return __($section['name'], 'wp-staging');
        }

        $metadata = self::getSectionMetadata($section);
        return $metadata['name'];
    }

    /**
     * Get subtitle for section definition or section ID
     *
     * @param array|string $section Section definition array or section ID string
     * @return string Subtitle for the section
     */
    public static function getSubtitle($section): string
    {
        if (is_array($section)) {
            return __($section['subtitle'], 'wp-staging');
        }

        $metadata = self::getSectionMetadata($section);
        return $metadata['subtitle'];
    }
}
