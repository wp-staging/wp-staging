<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backend\Modules\SystemInfoParser;
use WPStaging\Framework\Filesystem\DebugLogReader;

/** @var SystemInfo $systemInfo */
$systemInfo = WPStaging::getInstance()->get("systemInfo");
$systemInfo->setStructuredOutput(true); // Enable structured output and get sections

$parser            = new SystemInfoParser();
$structuredData    = $systemInfo->getSections();
$navItems          = $parser->getOrderedNavigationItems($structuredData);
$processedSections = $parser->processStructuredData($structuredData);
$assets            = WPStaging::make(Assets::class);

?>

<form action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo")) ?>" method="post" dir="ltr" class="wpstg--tab--active">
    <div class="wpstg-system-info-header">
        <div class="wpstg-settings-header wpstg-system-info-header-content">
            <div class="wpstg-settings-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-computer-icon lucide-computer"><rect width="14" height="8" x="5" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 18h2"/><path d="M12 18h6"/></svg>            </div>
            <div class="wpstg-settings-header-content">
                <h1 class="wpstg-settings-title"><?php esc_html_e('System Information', 'wp-staging'); ?></h1>
                <p class="wpstg-settings-subtitle"><?php esc_html_e('Complete system and server information for debugging', 'wp-staging'); ?></p>
            </div>
        </div>
        <div class="wpstg-system-info-actions">
            <div class="wpstg-button--primary wpstg-purge-backup-queue">
                <a href="javascript:void(0)" id="wpstg-purge-backup-queue-btn" class="">
                    <?php $assets->renderSvg('trash', 'wpstg--dashicons');?>
                    <?php esc_html_e('Purge Backup Queue', 'wp-staging'); ?>
                </a>
            </div>
            <div class="wpstg-button wpstg-blue-primary">
                <?php $assets->renderSvg('download', 'wpstg--dashicons');?>
                <input type="submit" name="wpstg-download-sysinfo" id="wpstg-download-sysinfo" class="wpstg-system-info-download-btn" value="<?php esc_html_e('Download System Info & Logs', 'wp-staging'); ?>">
            </div>
        </div>
    </div>

    <div class="wpstg-system-info-wrapper">
        <aside class="wpstg-system-info-sidebar">
            <ul class="wpstg-system-info-nav">
                <?php foreach ($navItems as $navItem) : ?>
                    <li>
                        <a href="#<?php echo esc_attr($navItem['id']); ?>" data-section="<?php echo esc_attr($navItem['id']); ?>" title="<?php echo esc_attr($navItem['title']); ?>">
                            <?php $assets->renderSvg($navItem['icon']); ?>
                            <span class="wpstg-system-info-sidebar-title"><?php echo esc_html($navItem['title']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <div class="wpstg-system-info-content">
        <?php
        $stagingSiteFields      = $parser->getStagingSiteFields();
        $seenSectionIds = [];
        $isFirstSection = true;

        // Render processed sections
        foreach ($processedSections as $currentIndex => $section) :
            if (empty($section['stagingSites']) && empty($section['storageProviders']) && empty($section['infoItems'])) {
                continue;
            }

            $sectionId    = $parser->getSectionId($section['sectionName'], $navItems);
            $addOddClass  = false;
            $isNewSection = !in_array($sectionId, $seenSectionIds, true);
            $addOddClass  = $isNewSection && !$isFirstSection;
            if ($isNewSection) {
                $seenSectionIds[] = $sectionId;
                $isFirstSection   = false;
            }
            ?>
            <section id="<?php echo esc_attr($sectionId); ?>" class="wpstg-system-info-section <?php echo $addOddClass ? 'wpstg-is-last-odd' : ''; ?>">
                <?php if (!empty($section['stagingSites'])) : ?>
                    <div class="wpstg-system-info-staging-sites-wrapper">
                        <h2 class="wpstg-system-info-card-title"><?php echo esc_html('WP Staging – Staging Sites'); ?></h2>
                        <p class="wpstg-system-info-card-subtitle"><?php esc_html_e('Configured staging environments', 'wp-staging'); ?></p>
                        <div class="wpstg-system-info-staging-sites-grid">
                            <?php foreach ($section['stagingSites'] as $index => $siteData) : ?>
                                <?php $toggleId = 'staging-site-' . $index; ?>
                                <div class="wpstg-system-info-staging-site-card">
                                    <div class="wpstg-system-info-staging-site-header wpstg-toggle-header" data-toggle-target="<?php echo esc_attr($toggleId); ?>">
                                        <h4 class="wpstg-system-info-staging-site-title"><?php echo esc_html($siteData['cloneName']); ?></h4>
                                        <svg class="wpstg-toggle-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                    <div class="wpstg-system-info-staging-site-details wpstg-toggle-content" id="<?php echo esc_attr($toggleId); ?>">
                                        <?php foreach ($stagingSiteFields as $fieldKey => $fieldConfig) : ?>
                                            <?php if (isset($siteData[$fieldKey])) : ?>
                                                <div class="wpstg-system-info-item">
                                                    <div class="wpstg-system-info-label"><?php echo esc_html($fieldConfig['label']); ?></div>
                                                    <div class="wpstg-system-info-value">
                                                        <?php if ($fieldConfig['is_link']) : ?>
                                                            <a href="<?php echo esc_url($siteData[$fieldKey]); ?>" target="_blank"  alt="<?php echo esc_attr($siteData[$fieldKey]); ?>" title="<?php echo esc_attr($siteData[$fieldKey]); ?>"><?php echo esc_html($siteData[$fieldKey]); ?></a>
                                                        <?php else : ?>
                                                            <span class="wpstg-system-info-badge" alt="<?php echo esc_attr($siteData[$fieldKey]); ?>"><?php echo esc_html($siteData[$fieldKey]); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php
                                            $itemLabel = __('Complete Site Info', 'wp-staging');
                                            $itemValue = $siteData;
                                            include __DIR__ . '/system-info-item.php';
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($section['storageProviders'])) : ?>
                    <div class="wpstg-system-info-staging-sites-wrapper">
                        <h3 class="wpstg-system-info-card-title">
                            <?php echo esc_html(sprintf('%s (%d)', $section['sectionName'], count($section['storageProviders']))); ?>
                        </h3>
                        <p class="wpstg-system-info-card-subtitle"><?php esc_html_e('Configured remote storage providers', 'wp-staging'); ?></p>
                        <div class="wpstg-system-info-staging-sites-grid">
                            <?php foreach ($section['storageProviders'] as $index => $provider) : ?>
                                <?php $toggleId = 'storage-provider-' . $index; ?>
                                <div class="wpstg-system-info-staging-site-card">
                                    <div class="wpstg-system-info-staging-site-header wpstg-toggle-header" data-toggle-target="<?php echo esc_attr($toggleId); ?>">
                                        <h4 class="wpstg-system-info-staging-site-title">
                                            <?php
                                             $assets->renderSvg($provider['id'], 'wpstg-storages-icon'); ?>    
                                            <?php echo esc_html($provider['name']); ?>
                                        </h4>
                                        <svg class="wpstg-toggle-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                    <div class="wpstg-system-info-staging-site-details wpstg-toggle-content" id="<?php echo esc_attr($toggleId); ?>">
                                        <?php foreach ($provider['settings'] as $setting) : ?>
                                            <?php
                                                $itemLabel = $setting['label'];
                                                $itemValue = $setting['value'];
                                                include __DIR__ . '/system-info-item.php';
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($section['infoItems'])) : ?>
                    <div class="wpstg-system-info-section-card">
                        <div class="wpstg-system-info-card-header">
                            <h3 class="wpstg-system-info-card-title"><?php echo esc_html($section['sectionName']); ?></h3>
                            <?php
                            $subtitle = $parser->getSectionSubtitle($section['sectionName']);
                            if (!empty($subtitle)) : ?>
                                <p class="wpstg-system-info-card-subtitle"><?php echo esc_html($subtitle); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="wpstg-system-info-card-body">
                        <?php foreach ($section['infoItems'] as $item) : ?>
                            <?php
                                $itemLabel = $item['label'];
                                $itemValue = $item['value'];
                                include __DIR__ . '/system-info-item.php';
                            ?>
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

            <!-- Logs Section -->
            <section id="logs" class="wpstg-system-info-section wpstg-system-info-logs-section">
                <div class="wpstg-system-info-section-card">
                    <div class="wpstg-system-info-header wpstg-logs-header">
                        <h3 class="wpstg-system-info-card-title"><?php esc_html_e('WP Staging Logs', 'wp-staging'); ?></h3>
                        <div class="wpstg-logs-action-container">
                            <a class="wpstg-button wpstg-blue-primary" href="javascript:void(0)" title="<?php esc_attr_e('Copy WP Staging Logs', 'wp-staging'); ?>" onclick="WPStaging.copyTextToClipboard(this)" data-wpstg-source="#wpstg-debug-logs-textarea">
                                <?php $assets->renderSvg('file', 'wpstg--dashicons');?>    
                                <?php esc_html_e('Copy', 'wp-staging'); ?>
                            </a>
                            <a href="javascript:void(0)" id="wpstg-delete-debug-logs" class="wpstg-button--blue wpstg-error" title="<?php esc_attr_e('Delete WP Staging Logs', 'wp-staging'); ?>" data-url="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=wpstaging&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">
                                <?php $assets->renderSvg('trash', 'wpstg--dashicons');?>    
                                <?php esc_html_e('Delete', 'wp-staging'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="wpstg-system-info-card-body">
                        <textarea class="wpstg-system-info-textarea" readonly="readonly" id="wpstg-debug-logs-textarea" name="wpstg-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(256 * KB_IN_BYTES, true, false)); ?></textarea>
                    </div>
                </div>    
                <div class="wpstg-system-info-section-card">
                    <div class="wpstg-system-info-header wpstg-logs-header">
                        <h3 class="wpstg-system-info-card-title"><?php esc_html_e('PHP debug.log', 'wp-staging'); ?></h3>
                        <div class="wpstg-logs-action-container">
                            <a class="wpstg-button wpstg-blue-primary" href="javascript:void(0)" title="<?php esc_attr_e('Copy PHP debug.log', 'wp-staging'); ?>" onclick="WPStaging.copyTextToClipboard(this)" data-wpstg-source="#wpstg-php-debug-logs-textarea">
                                <?php $assets->renderSvg('file', 'wpstg--dashicons');?>    
                                <?php esc_html_e('Copy', 'wp-staging'); ?>
                            </a>
                            <a href="javascript:void(0)" id="wpstg-delete-php-logs" class="wpstg-button--blue wpstg-error" title="<?php esc_attr_e('Delete PHP debug.log', 'wp-staging'); ?>" data-url="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=php&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">
                                <?php $assets->renderSvg('trash', 'wpstg--dashicons');?>
                                <?php esc_html_e('Delete', 'wp-staging'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="wpstg-system-info-card-body">
                        <textarea class="wpstg-system-info-textarea" readonly="readonly" id="wpstg-php-debug-logs-textarea" name="wpstg-php-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(128 * KB_IN_BYTES, false, true)); ?></textarea>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>