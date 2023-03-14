<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="" id="wpstg-welcome">
    <div class="wpstg-welcome-container wpstg--grey">
        <h2 class="wpstg-h2 wpstg--grey">
            <span class="wpstg-heading-pro wpstg--blue"><?php esc_html_e('WP STAGING | PRO', 'wp-staging'); ?></span>
            <?php esc_html_e(' - Enterprise Level Backup, Cloning & Migration Tool', 'wp-staging'); ?>
        </h2>
        <h3 class="wpstg--grey">Is this the best backup & migration plugin?</h3>
        <li><strong>Enterprise Reliability</strong> - <?php echo sprintf(
            Escape::escapeHtml(__('Your data is crucial so we run <a href="%s" target="_blank" style="text-decoration: underline;">thousands</a> of automated tests before every release.', 'wp-staging')),
            'https://www.youtube.com/watch?v=Tf9C9Pgu7Bs&t=5s'
        ); ?></li>
        <li><strong>German Engineering</strong> - <?php esc_html_e('Our headquarter is located in Germany with a small team of highly skilled developers.', 'wp-staging'); ?></li>
        <li><strong>Cloning</strong> - <?php esc_html_e('Clone your entire website with one click.', 'wp-staging'); ?></li>
        <li><strong>Push Changes</strong> - <?php esc_html_e('Push a staging site to the production site. (Pro)', 'wp-staging'); ?></li>
        <li><strong>Backup & Restore</strong> - <?php esc_html_e('Backup and Restore WordPress. Easy, fast, and secure. (Pro)', 'wp-staging'); ?></li>
        <li><strong>Move WordPress</strong> - <?php esc_html_e('Migrate & move your website from one domain to another, even to a separate server. (Pro)', 'wp-staging'); ?></li>
        <li><strong>Support Multisites</strong> - <?php esc_html_e('Clone and push Multisites. (Pro)', 'wp-staging'); ?></li>
        <li><strong>Authentication</strong> - <?php esc_html_e('Cloned sites are available to authenticated users only.', 'wp-staging'); ?></li>
        <li><strong>High Performance</strong> - <?php esc_html_e('WP STAGING is one of the fastest backup and migration plugins. Compare yourself.', 'wp-staging'); ?></li>
        <li><strong>Secure</strong> - <?php esc_html_e('WP STAGING is no cloud service. Your data belongs to you only.', 'wp-staging'); ?></li>
        <a href="http://wp-staging.com/?utm_source=wpstg&utm_medium=addon_page&utm_term=click-wpstaging-pro&utm_campaign=wpstaging" target="_blank" class="wpstg-button--big wpstg-button--blue">Buy WP Staging Pro</a>
        <a href="<?php echo esc_url(admin_url()); ?>admin.php?page=wpstg_clone" target="_self" class="wpstg-primary-color wpstg-ml-30px">Skip & Start Cloning</a>
        <div class="wpstg-footer"> <?php esc_html_e('Comes with our money back guarantee * You need to give us chance to resolve your issue first.', 'wp-staging'); ?></div>
    </div>
</div>
