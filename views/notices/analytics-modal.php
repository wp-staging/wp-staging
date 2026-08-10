<?php

/**
 * The analytics consent question.
 *
 * Every category below is the plain-language name of something the payload in
 * {@see \WPStaging\Framework\Analytics\WithAnalyticsSiteInfo::getAnalyticsSiteInfo()},
 * {@see \WPStaging\Framework\Analytics\AnalyticsEventDto} and
 * {@see \WPStaging\Framework\Analytics\AnalyticsConsent::giveConsent()} actually
 * sends. Change one of those and this view has to change with it.
 */

use WPStaging\Framework\Language\Language;

$privacyPolicyUrl = Language::localizeUrl('https://wp-staging.com/privacy-policy/');

$sharedData = [
    [
        'name' => __('Site environment', 'wp-staging'),
        'text' => __('Site address, WordPress and PHP versions, server and database details, your active plugins and theme.', 'wp-staging'),
    ],
    [
        'name' => __('WP STAGING usage', 'wp-staging'),
        'text' => __('Plugin activation, deactivation and uninstall, staging and backup events with their duration and size, and your WP STAGING settings.', 'wp-staging'),
    ],
    [
        'name' => __('Error diagnostics', 'wp-staging'),
        'text' => __('When a job fails: the error message and the last lines of your WP STAGING and PHP logs.', 'wp-staging'),
    ],
    [
        'name' => __('Product notifications', 'wp-staging'),
        'text' => __('Important security, compatibility and feature updates.', 'wp-staging'),
    ],
];
?>
<div class="wpstg-consent-modal-main-wrapper">
    <div class="wpstg-consent-modal-content" role="dialog" aria-modal="true" aria-labelledby="wpstg-consent-modal-title" tabindex="-1">
        <div class="wpstg-consent-modal-install-image-block">
            <?php require WPSTG_VIEWS_DIR . 'notices/_partial/wp-staging-logo-svg.php'; ?>
        </div>

        <h2 class="wpstg-consent-modal-header" id="wpstg-consent-modal-title"><?php esc_html_e('Enable security alerts', 'wp-staging'); ?></h2>
        <p class="wpstg-consent-modal-install-description-text"><?php esc_html_e('Get important security and compatibility alerts. Technical usage data helps us spot problems earlier, improve reliability, and troubleshoot issues faster when you need support.', 'wp-staging'); ?></p>

        <button type="button" id="wpstg-consent-modal-btn-success"><?php esc_html_e('Enable & Continue', 'wp-staging'); ?></button>

        <div id="wpstg-consent-modal-permission-list" hidden>
            <h3 class="wpstg-consent-modal-section-title"><?php esc_html_e('Why this helps', 'wp-staging'); ?></h3>
            <p class="wpstg-consent-modal-section-text"><?php esc_html_e('Technical diagnostics help us identify compatibility problems across real WordPress environments and give our support team better context when troubleshooting an issue you report.', 'wp-staging'); ?></p>

            <h3 class="wpstg-consent-modal-section-title"><?php esc_html_e('What is shared', 'wp-staging'); ?></h3>
            <dl class="wpstg-consent-modal-shared-list">
                <?php foreach ($sharedData as $shared) : ?>
                    <dt><?php echo esc_html($shared['name']); ?></dt>
                    <dd><?php echo esc_html($shared['text']); ?></dd>
                <?php endforeach; ?>
            </dl>

            <p class="wpstg-consent-modal-assurance"><?php esc_html_e('WP STAGING never reads your posts, pages, media or passwords, and never sends us your backup files.', 'wp-staging'); ?></p>
        </div>

        <div class="wpstg-consent-modal-install-footer">
            <button
                type="button"
                id="wpstg-admin-notice-learn-more"
                class="wpstg-consent-modal-button"
                aria-expanded="false"
                aria-controls="wpstg-consent-modal-permission-list"
                data-label-expanded="<?php esc_attr_e('Read Less', 'wp-staging'); ?>"
                data-label-collapsed="<?php esc_attr_e('Read More', 'wp-staging'); ?>"
            ><?php esc_html_e('Read More', 'wp-staging'); ?></button>
            <a class="wpstg-consent-modal-button" href="<?php echo esc_url($privacyPolicyUrl); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Privacy Policy', 'wp-staging'); ?></a>
            <button type="button" id="wpstg-skip-activate-notice" class="wpstg-consent-modal-button"><?php esc_html_e('Skip', 'wp-staging'); ?></button>
        </div>
    </div>
</div>
