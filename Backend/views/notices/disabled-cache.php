<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::showNotices
 */
?>
<div class="notice wpstg-cache-notice" style="border-left: 4px solid #ffba00;">
    <p>
        <strong style="margin-bottom: 10px;"><?php _e( 'Cache Disabled', 'wp-staging' ); ?></strong> <br/>
        <?php _e( 'WP STAGING disabled the cache on this staging site by setting the constant WP_CACHE to false in the wp-config.php.', 'wp-staging' ); ?>
    </p>
    <p>
        <a href="javascript:void(0);" class="wpstg_hide_cache_notice" title="Close this message"
            style="font-weight:bold;">
            <?php _e('Close this message', 'wp-staging') ?>
        </a>
    </p>
</div>
<?php
/*
 * Cache-burst mechanism to ensure the browser cache will not get in the way
 * of the script working properly when there's updates.
 */
$file = trailingslashit($this->path) . "public/js/wpstg-admin-cache-notice.js";

if (file_exists($file)) {
    $version = (string)@filemtime($file);
} else {
    $version = '{{version}}';
}
?>
<script src="<?php echo esc_url(trailingslashit($this->url) . "js/wpstg-admin-cache-notice.js?v=$version") ?>"></script>
