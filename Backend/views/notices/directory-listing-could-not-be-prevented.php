<?php
/**
 * @see \WPStaging\Backend\Notices\Notices::showDirectoryListingWarningNotice
 * @var array $directoryListingErrors An array of directory listing errors to display.
 */
?>

<div class='notice-warning notice is-dismissible'>
    <p style='font-weight: bold;'><?php _e('WPSTAGING - Failed to prevent directory listing', 'wp-staging'); ?></p>
    <p><?php _e('Following the best development practices, WPSTAGING tries to prevent directory listing on it\'s own directories
that might contain sensitive data. This warning tells you that we could not prevent directory listing on one
of the directories.'); ?></p>
    <p><?php echo implode('<br>', esc_html($directoryListingErrors)); ?></p>
</div>
