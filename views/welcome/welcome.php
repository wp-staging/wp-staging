<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="" id="wpstg-welcome">
    <div class="wpstg-welcome-container wpstg--grey">
        <h2 class="wpstg-h2 wpstg--grey">
            <span class="wpstg-heading-pro wpstg--blue"><?php esc_html_e('WP STAGING | PRO', 'wp-staging'); ?></span>
            <?php esc_html_e(' - Enterprise Level Backup, Cloning & Migration Tool', 'wp-staging'); ?>
        </h2>
        <h3 class="wpstg--grey"><?php esc_html_e('Is this the best backup & migration plugin?', 'wp-staging'); ?></h3>
        <li><strong><?php esc_html_e('Enterprise Reliability', 'wp-staging'); ?></strong> - <?php echo sprintf(
            Escape::escapeHtml(__('Your data is important so we run <a href="%s" target="_blank" style="text-decoration: underline;">thousands</a> of automated tests before every release.', 'wp-staging')),
            'https://wp-staging.com/quality-assurance-for-wp-staging/'
        ); ?></li>
        <li><strong><?php esc_html_e('German Engineering', 'wp-staging'); ?></strong> - <?php esc_html_e('Our headquarter is located in Germany with a small team of highly skilled developers.', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Cloning', 'wp-staging'); ?></strong> - <?php esc_html_e('Clone your entire website with one click.', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Push Changes', 'wp-staging'); ?></strong> - <?php esc_html_e('Push a staging site to the production site. (Pro)', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Backup & Restore', 'wp-staging'); ?></strong> - <?php esc_html_e('Backup and Restore WordPress. Simple, fast, and secure. Even if your website is no longer accessible.', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Move WordPress', 'wp-staging'); ?></strong> - <?php esc_html_e('Migrate & move your website from one domain to another, even to a separate server. (Pro)', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Support Multisites', 'wp-staging'); ?></strong> - <?php esc_html_e('Clone and push Multisites. (Pro)', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Authentication', 'wp-staging'); ?></strong> - <?php esc_html_e('Cloned sites are available to authenticated users only.', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('High Performance', 'wp-staging'); ?></strong> - <?php esc_html_e('WP STAGING is one of the fastest backup and migration plugins. Compare yourself.', 'wp-staging'); ?></li>
        <li><strong><?php esc_html_e('Secure', 'wp-staging'); ?></strong> - <?php esc_html_e('WP STAGING is no cloud service. Your data belongs to you only.', 'wp-staging'); ?></li>
        <a href="https://wp-staging.com/?utm_source=wpstg&utm_medium=addon_page&utm_term=click-wpstaging-pro&utm_campaign=wpstaging" target="_blank" class="wpstg-button--big wpstg-button--blue"><?php esc_html_e('Buy WP Staging Pro', 'wp-staging'); ?></a>
        <a href="<?php echo esc_url(admin_url()); ?>admin.php?page=wpstg_clone" target="_self" class="wpstg-primary-color wpstg-ml-30px"><?php esc_html_e('Skip & Start WP Staging', 'wp-staging'); ?></a>
        <div class="wpstg-footer"> <?php esc_html_e('Comes with our money back guarantee * You need to give us chance to resolve your issue first.', 'wp-staging'); ?></div>
    </div>
</div>
