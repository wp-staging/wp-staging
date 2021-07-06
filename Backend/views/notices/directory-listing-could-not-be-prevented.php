<?php
/**
 * @see \WPStaging\Backend\Notices\Notices::showDirectoryListingWarningNotice
 * @var array $directoryListingErrors An array of directory listing errors to display.
 */
?>

<div class='notice-warning notice is-dismissible'>
    <p><strong><?php _e('WP STAGING - Failed to prevent directory listing', 'wp-staging'); ?></strong>
        <br>
    <?php _e('Following the best development practices, WP STAGING tries to prevent directory listing on it\'s own directories
that might contain sensitive data. This warning tells you that we could not prevent directory listing on one
of the directories.'); ?>
    <?php echo !empty($directoryListingErrors) ? implode('<br>', esc_html($directoryListingErrors)) : ''; ?></p>
</div>
