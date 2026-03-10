<?php

/**
 * Compatibility status notice — shown inside the WP STAGING plugin UI when the
 * plugin has not yet been validated for the currently installed WordPress version.
 *
 * @see \WPStaging\Framework\Notices\WpVersionCompatNotice::maybeShow()
 *
 * @var string $wpVersion     Currently installed WordPress version.
 * @var string $pluginVersion WP STAGING plugin version.
 * @var string $wpMajorMinor  Major.minor portion of $wpVersion (e.g. "6.6").
 * @var string $changelogUrl  URL to the plugin changelog / release notes.
 * @var string $supportUrl    URL to the plugin support page.
 * @var string $systemInfoUrl URL to the System Info admin page.
 */

/* translators: %s: WordPress version string, e.g. "6.6.1" */
$noticeTitle = sprintf(__('WordPress %s detected — validation pending', 'wp-staging'), $wpVersion);

?>
<div id="wpstg-compat-notice"
     class="wpstg-flex wpstg-flex-col wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-border-gray-200 wpstg-bg-gray-50 wpstg-p-4 wpstg-mt-5 wpstg-mr-5 dark:wpstg-bg-slate-800 dark:wpstg-border-slate-600"
     data-wp-major-minor="<?php echo esc_attr($wpMajorMinor); ?>"
     data-plugin-version="<?php echo esc_attr($pluginVersion); ?>">

    <div class="wpstg-flex wpstg-items-start wpstg-gap-3">

        <div class="wpstg-flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round"
                 class="wpstg-text-gray-400 dark:wpstg-text-slate-400"
                 aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>

        <div class="wpstg-flex-1 wpstg-min-w-0">
            <p class="wpstg-text-sm wpstg-font-semibold wpstg-text-gray-700 dark:wpstg-text-slate-200 wpstg-m-0">
                <?php echo esc_html($noticeTitle); ?>
            </p>
            <p class="wpstg-text-sm wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-mt-1 wpstg-mb-0">
                <?php esc_html_e('WP STAGING typically works normally during the short window after a WordPress release.', 'wp-staging'); ?>
            </p>
            <p class="wpstg-text-xs wpstg-text-gray-500 dark:wpstg-text-slate-400 wpstg-mt-1 wpstg-mb-0">
                <?php esc_html_e("We'll mark this WordPress version as validated after final checks. If you notice an issue,", 'wp-staging'); ?>
                <a href="<?php echo esc_url($supportUrl); ?>" target="_blank" rel="noopener noreferrer"
                   class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-underline wpstg-font-medium">
                    <?php esc_html_e('send us your logs', 'wp-staging'); ?></a>.
            </p>
        </div>

        <div class="wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-flex-shrink-0">
            <button type="button"
                    id="wpstg-compat-notice-details-btn"
                    aria-expanded="false"
                    aria-controls="wpstg-compat-notice-panel"
                    class="wpstg-inline-flex wpstg-items-center wpstg-gap-1 wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-border-gray-300 wpstg-bg-white dark:wpstg-bg-slate-700 dark:wpstg-border-slate-500 wpstg-px-3 wpstg-py-1.5 wpstg-text-sm wpstg-font-medium wpstg-text-gray-600 dark:wpstg-text-slate-200 wpstg-cursor-pointer">
                <?php esc_html_e('Compatibility details', 'wp-staging'); ?>
                <svg id="wpstg-compat-notice-chevron"
                     xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <button type="button"
                    id="wpstg-compat-notice-dismiss-btn"
                    aria-label="<?php esc_attr_e('Dismiss compatibility notice', 'wp-staging'); ?>"
                    class="wpstg-text-sm wpstg-text-gray-500 dark:wpstg-text-slate-400 wpstg-bg-transparent wpstg-border-0 wpstg-cursor-pointer wpstg-p-0">
                <?php esc_html_e('Dismiss', 'wp-staging'); ?>
            </button>
        </div>
    </div>

    <div id="wpstg-compat-notice-panel"
         class="wpstg-hidden wpstg-mt-4 -wpstg-mx-4 wpstg-px-4 wpstg-pt-3 wpstg-pb-4 wpstg-border-0 wpstg-border-t wpstg-border-solid wpstg-border-gray-200 dark:wpstg-border-slate-600">
        <dl class="wpstg-grid wpstg-gap-y-2 wpstg-text-sm wpstg-ml-8">

            <div class="wpstg-flex wpstg-gap-2">
                <dt class="wpstg-font-medium wpstg-text-gray-700 dark:wpstg-text-slate-200 wpstg-w-40 wpstg-flex-shrink-0">
                    <?php esc_html_e('Status', 'wp-staging'); ?>
                </dt>
                <dd class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-m-0">
                    <?php esc_html_e('Validation pending', 'wp-staging'); ?>
                </dd>
            </div>

            <div class="wpstg-flex wpstg-gap-2">
                <dt class="wpstg-font-medium wpstg-text-gray-700 dark:wpstg-text-slate-200 wpstg-w-40 wpstg-flex-shrink-0">
                    <?php esc_html_e('WordPress version', 'wp-staging'); ?>
                </dt>
                <dd class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-m-0">
                    <?php echo esc_html($wpVersion); ?>
                </dd>
            </div>

            <div class="wpstg-flex wpstg-gap-2">
                <dt class="wpstg-font-medium wpstg-text-gray-700 dark:wpstg-text-slate-200 wpstg-w-40 wpstg-flex-shrink-0">
                    <?php esc_html_e('WP STAGING version', 'wp-staging'); ?>
                </dt>
                <dd class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-m-0">
                    <?php echo esc_html($pluginVersion); ?>
                </dd>
            </div>

        </dl>

        <div class="wpstg-flex wpstg-flex-wrap wpstg-gap-x-4 wpstg-gap-y-1 wpstg-mt-3 wpstg-text-sm wpstg-ml-8">
            <a href="<?php echo esc_url($changelogUrl); ?>" target="_blank" rel="noopener noreferrer"
               class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-underline">
                <?php esc_html_e('Release notes', 'wp-staging'); ?>
            </a>
            <a href="<?php echo esc_url($supportUrl); ?>" target="_blank" rel="noopener noreferrer"
               class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-underline">
                <?php esc_html_e('Contact support', 'wp-staging'); ?>
            </a>
            <a href="<?php echo esc_url($systemInfoUrl); ?>"
               class="wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-underline">
                <?php esc_html_e('Export logs', 'wp-staging'); ?>
            </a>
        </div>
    </div>
</div>
