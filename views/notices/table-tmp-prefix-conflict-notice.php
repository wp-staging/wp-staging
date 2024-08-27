<?php

/**
 * @var $this
 *
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
 <div class='notice-warning notice'>
     <p><strong><?php esc_html_e('WP STAGING - Conflict with temporary prefix!', 'wp-staging'); ?></strong>
         <br>
     <?php
        echo sprintf(__('This site uses the table prefix (<code>%s</code>). This is a temporary table prefix reserved by WP STAGING and you can not clone this website nor restore it from a backup.
            This has likely been caused by a manual adjustment in the wp-config.php and should be fixed as soon as possible.
            <a href="%s" target="_blank" rel="external nofollow">Read this</a> to see how to rename the table prefix.', 'wp-staging'), esc_html($this->db->prefix), esc_url('https://wp-staging.com/3-ways-to-change-the-wordpress-database-prefix-method-simplified/'));
        ?>
     </p>
 </div>
