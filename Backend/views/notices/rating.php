<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="wpstg_fivestar" style="display:none;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); border-left:none; background-color:#0a8ee2; color:white; padding: 10px; margin:20px 20px 20px 0px;">
    <div class="wpstg-welcome-box" style="display: flex; width: 100%; align-items: center;">
        <div class="wpstg-welcome-video-container" style="width: 375px; height: 210px;display:none;">
            <div style="width: 100%; height: 100%; display: flex; background: #000; position: relative; align-items: center; justify-content: center; z-index: 0;">
                <div class="wpstg-yt-thumbnail-container" style="display: flex; width: 100%; height: 100%; z-index: 100; cursor: pointer; justify-content: center; align-items: center;" id="welcomeNoticeFree">
                    <img style="width: 100%; object-fit: fill; z-index: 0; cursor: pointer;" alt="WP STAGING Welcome Video Thumbnail" src="<?php echo esc_url(plugins_url('/assets/img/thumbnail.jpg', dirname(dirname(__DIR__)))) ?>" />
                    <button style="cursor: pointer; position: absolute; width: 68px; height: 48px; background: transparent; border: 0px solid transparent; -moz-transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1); -webkit-transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1); transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1); z-index: 63;">
                        <svg height="100%" version="1.1" viewBox="0 0 68 48" width="100%"><path class="wpstg-yt-button-svg" style="-moz-transition: fill .1s cubic-bezier(0.4,0.0,1,1),fill-opacity .1s cubic-bezier(0.4,0.0,1,1); -webkit-transition: fill .1s cubic-bezier(0.4,0.0,1,1),fill-opacity .1s cubic-bezier(0.4,0.0,1,1); transition: fill .1s cubic-bezier(0.4,0.0,1,1),fill-opacity .1s cubic-bezier(0.4,0.0,1,1); fill: #212121; fill-opacity: .8; height: 100%; left: 0; position: absolute; top: 0; width: 100%;" d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>
                    </button>
                </div>
                <div style="position: absolute; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;">
                    <img style="width: 90px; height: 90px; z-index: 10;" width="90px" src="<?php echo esc_url(plugins_url('/assets/img/tail-spin.svg', dirname(dirname(__DIR__)))) ?>">
                </div>
            </div>
        </div>
        <div class="wpstg-welcome-text" style="padding: 0px; padding-left: 20px; padding-right: 8px;">
            <p><?php echo sprintf(esc_html__('You are using %s for more than 1 week.
                May we ask you to give it a %s rating on wordpress.org?', 'wp-staging'), "<strong>WP STAGING</strong>", "<strong>5-star</strong>"); ?>
                <?php if (!defined('WPSTGPRO_VERSION')) { ?>
                    <br><br>
                    <?php esc_html_e('P.S. Do you like to migrate this staging site to production site? 
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
