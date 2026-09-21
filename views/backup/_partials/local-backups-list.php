<?php

/**
 * Where the backups stored on this server are listed once the page has looked them up.
 */

?>
<div id="wpstg-existing-backups">
    <div id="backup-messages"></div>
    <div class="wpstg-backup-list">
        <span id="local-backup-title"><?php echo esc_html__('Local Backups:', 'wp-staging'); ?></span>
        <ul id="wpstg-backup-list-ul">
            <li><?php esc_html_e('Searching for existing backups...', 'wp-staging'); ?></li>
        </ul>
    </div>
</div>
