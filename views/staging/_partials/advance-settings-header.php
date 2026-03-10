<?php

/**
 * Only used in FREE version.
 * @see \WPStaging\Staging\Service\StagingSetup:renderAdvanceSettingsHeader
 */

use WPStaging\Framework\Language\Language;

?>

<p class="wpstg-pro-features-notice wpstg-inline-flex wpstg-items-center wpstg-gap-3 wpstg-rounded-md wpstg-px-4 wpstg-py-2 wpstg-my-2 wpstg-text-sm wpstg-text-white wpstg-bg-blue-600 dark:wpstg-bg-blue-800 dark:wpstg-border dark:wpstg-border-solid dark:wpstg-border-blue-600"><?php esc_html_e('Options below are pro features!', 'wp-staging'); ?>
    <a href="<?php echo esc_url(Language::localizeHomepageUrl('https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=new-admin-user&utm_term=new-admin-user')); ?>" target="_blank" class="wpstg-inline-block wpstg-m-2 wpstg-px-3 wpstg-py-1 wpstg-text-sm wpstg-text-white wpstg-bg-red-500 wpstg-rounded wpstg-border wpstg-border-solid wpstg-border-red-400 wpstg-no-underline hover:wpstg-bg-red-700 hover:wpstg-text-white"><?php esc_html_e("Try out WP Staging Pro", "wp-staging"); ?></a>
</p>
