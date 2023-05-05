<?php
/**
 * @see \WPStaging\Framework\Notices\Notices::showDirectoryListingWarningNotice
 * @var array $directoryListingErrors An array of directory listing errors to display.
 */
?>

<div class='notice-warning notice is-dismissible'>
    <p><strong><?php esc_html_e('WP STAGING - Failed to prevent directory listing', 'wp-staging'); ?></strong>
        <br>
    <?php esc_html_e('Following the best development practices, WP STAGING tries to prevent directory listing on it\'s own directories
that might contain sensitive data. This warning tells you that we could not prevent directory listing on one
of the directories.', 'wp-staging'); ?>
    <?php echo !empty($directoryListingErrors) ? wp_kses(implode('<br>', $directoryListingErrors), ['br']) : ''; ?></p>
</div>
