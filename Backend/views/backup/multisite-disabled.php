<?php

/**
 * @var $mainsiteWpstgURL
 * @see \WPStaging\Backup\Ajax\Listing::render
 */

?>

<div id="wpstg-multisite-disabled">
    <ul>
        <li class="wpstg-clone">
            <p><strong><?php esc_html_e('Coming soon!', 'wp-staging'); ?></strong></p>
            <p><?php echo sprintf(esc_html__('Please go to the %s to create a backup of the entire multisite network including all network sub-sites. With one of the next releases, you will be able to backup network sub-sites separately.', 'wp-staging'), '<a href="' . esc_url($mainsiteWpstgURL) . '">main multisite</a>'); ?></p>
        </li>
    </ul>
</div>
