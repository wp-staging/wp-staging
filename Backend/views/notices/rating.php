<?php
/**
 * @var $this \WPStaging\Framework\Notices\Notices
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
<div class="wpstg_fivestar" style="display:none;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); border-left:none; background-color:#0a8ee2; color:white; padding: 10px; margin:20px 20px 20px 0px;">
    <div class="wpstg-welcome-box" style="display: flex; width: 100%; align-items: center;">
        <div class="wpstg-welcome-text" style="padding: 0px; padding-left: 20px; padding-right: 8px;">
            <p><?php echo sprintf(esc_html__('You are using %s for more than 1 week.
                May we ask you to give it a %s rating on wordpress.org?', 'wp-staging'), "<strong>WP STAGING</strong>", "<strong>5-star</strong>"); ?>
                <?php if (!defined('WPSTGPRO_VERSION')) { ?>
                    <br><br>
                    <?php esc_html_e('Btw, would you like to migrate this staging site to the live site or try the extremely fast backup feature of WP STAGING? 
                Try out', 'wp-staging') ?> <a href="https://wp-staging.com/?utm_source=wpstg_admin&utm_medium=rating_screen&utm_campaign=admin_notice" target="_blank" style="color:white;font-weight:bold;">WP STAGING | PRO</a>
                    <br>
                <?php } ?>
            </p>
            <ul>
                <li>
                    <a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5#new-post" target="_blank" style="background-color:#d10f83;border-color:transparent;margin-bottom:10px;color:white;font-weight:bold;-webkit-box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);-moz-box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);" id="wpstg_clicked_deserved_it" class="thankyou button"
                    title="Sure, I like your plugin" style="font-weight:bold;">
                        <?php esc_html_e('- Yes, I like WP STAGING! Rate & Close this Message', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="wpstg_hide_rating" title="I already rated"
                    style="font-weight:normal;color:white;text-decoration: none;">
                        <?php esc_html_e('- I already rated - Close ', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="wpstg_hide_rating" title="No, not good enough"
                    style="font-weight:normal;color:white;">
                        <?php esc_html_e('', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="wpstg_rate_later" title="Ask me again in a week"
                    style="font-weight:normal;color:white;text-decoration: none;">
                        <?php esc_html_e('- Ask me again in a week - Close', 'wp-staging') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>

</div>

<script>
    var wpstgYouTubeConfig = {
        'accepted': false,
        'message': "<?php esc_html_e("This video is hosted on YouTube. Please click on the OK button to play this video. We don't load any external data without your explicit consent.") ?>",
        'regards': "<?php esc_html_e("Your WP STAGING Team") ?>"
    };
</script>
<script src="<?php echo esc_url($this->assets->getAssetsUrlWithVersion("js/dist/wpstg-admin-rating.js", '2.7.6')) ?>"></script>
