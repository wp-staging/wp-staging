<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;

$wpstgChangelogUrl = WPStaging::isPro()
    ? 'https://wp-staging.com/wp-staging-pro-changelog/'
    : 'https://wp-staging.com/wp-staging-changelog/';

$wpstgBorlabsLink = '<a class="wpstg-admin-footer-partner-link" href="' . esc_url('https://wp-staging.com/borlabs-cookie/') . '" target="_blank" rel="noopener noreferrer">Borlabs Cookie</a>';
$wpstgFollowLabel = __('Follow Rene Hermenau, founder of WP STAGING, on X', 'wp-staging');
$wpstgBorlabsAllowedHtml = [
    'a' => [
        'class'  => [],
        'href'   => [],
        'target' => [],
        'rel'    => [],
    ],
];
?>
<div id="wpstg-footer-container" class="wpstg-mr-2.5 wp:wpstg-mr-5">
    <?php if (empty($hideNewsfeed)) : ?>
        <?php
        require_once(WPSTG_VIEWS_DIR . '_main/newsfeed.php');
        require_once(WPSTG_VIEWS_DIR . '_main/faq.php');
        ?>
    <?php endif; ?>
</div>
<div class="wpstg-admin-footer">
    <span class="wpstg-admin-footer-brand">WP STAGING</span>
    <span class="wpstg-admin-footer-sep" aria-hidden="true">&middot;</span>
    <a class="wpstg-admin-footer-link" href="<?php echo esc_url(Language::localizeDocsUrl('https://wp-staging.com/docs/documentation/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Documentation', 'wp-staging'); ?></a>
    <span class="wpstg-admin-footer-sep" aria-hidden="true">&middot;</span>
    <a class="wpstg-admin-footer-link" href="<?php echo esc_url(Language::localizeSupportUrl('https://wp-staging.com/support/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Support', 'wp-staging'); ?></a>
    <span class="wpstg-admin-footer-sep" aria-hidden="true">&middot;</span>
    <a class="wpstg-admin-footer-link" href="<?php echo esc_url($wpstgChangelogUrl); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Changelog', 'wp-staging'); ?></a>
    <span class="wpstg-admin-footer-sep" aria-hidden="true">&middot;</span>
    <span class="wpstg-admin-footer-partner"><?php echo wp_kses(sprintf(esc_html__('Partner: %s', 'wp-staging'), $wpstgBorlabsLink), $wpstgBorlabsAllowedHtml); ?></span>
    <span class="wpstg-admin-footer-sep" aria-hidden="true">&middot;</span>
    <span class="wpstg-admin-footer-social">
        <a class="wpstg-admin-footer-icon" href="https://github.com/wp-staging/wp-staging" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
            <svg viewBox="0 0 98 96" fill="currentColor" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"/></svg>
        </a>
        <a class="wpstg-admin-footer-icon wpstg-admin-footer-icon-x" href="https://x.com/ReneHermenau" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($wpstgFollowLabel); ?>" title="<?php echo esc_attr($wpstgFollowLabel); ?>">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M14.095479,10.316482L22.286354,1h-1.940718l-7.115352,8.087682L7.551414,1H1l8.589488,12.231093L1,23h1.940717l7.509372-8.542861L16.448587,23H23L14.095479,10.316482z M11.436522,13.338465l-0.871624-1.218704l-6.924311-9.68815h2.981339l5.58978,7.82155l0.867949,1.218704l7.26506,10.166271h-2.981339L11.436522,13.338465z"/></svg>
        </a>
    </span>
</div>
<?php
// Permanent, subtle "Compare Free vs Pro" discovery link. It is intentionally
// independent of the dashboard Pro card's 90-day snooze, so Free users can
// always find the comparison even after dismissing that card. Shown on every
// Free-build admin page (unlike the dashboard card, it is not suppressed when
// Pro is merely installed-but-inactive); never a top banner or large upsell.
if (!\WPStaging\Core\WPStaging::isPro()) :
    $compareUrl = \WPStaging\Framework\Language\Language::localizeUrl('https://wp-staging.com/pro-features/?utm_source=wp-admin&utm_medium=footer&utm_campaign=compare_card');
    ?>
    <div class="wpstg-footer-compare wpstg-mx-auto wpstg-mt-2 wpstg-mb-6 wpstg-text-center">
        <a href="<?php echo esc_url($compareUrl); ?>" target="_blank" rel="noopener noreferrer"
           class="wpstg-inline-flex wpstg-items-center wpstg-gap-2 wpstg-px-4 wpstg-py-2 wpstg-rounded-lg wpstg-border wpstg-border-solid wpstg-border-gray-200 dark:wpstg-border-slate-700 wpstg-bg-gray-50 dark:wpstg-bg-slate-800/60 wpstg-text-sm wpstg-font-medium wpstg-text-gray-600 dark:wpstg-text-slate-300 wpstg-no-underline hover:wpstg-border-gray-300 dark:hover:wpstg-border-slate-600">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                <path d="M12 3v18"></path>
            </svg>
            <?php esc_html_e('Compare Free vs Pro', 'wp-staging'); ?>
        </a>
        <p class="wpstg-mt-1 wpstg-mb-0 wpstg-mx-auto wpstg-max-w-sm wpstg-text-xs wpstg-text-gray-400 dark:wpstg-text-slate-500">
            <?php esc_html_e('See which workflows are included in Free and when Pro adds push-to-live, cloud backups, migrations, Multisite, and developer tools.', 'wp-staging'); ?>
        </p>
    </div>
<?php endif; ?>
<?php
require_once(WPSTG_VIEWS_DIR . '_main/general-error-modal.php');
