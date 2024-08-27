<?php
/**
 * @var $this \WPStaging\Basic\Notices\Notices
 * @see \WPStaging\Basic\Notices\Notices::renderNotices
 */
?>
<div class="wpstg_fivestar" style="display:none;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); border-left:none; background: linear-gradient(129deg, #0054be 0%, #1a73e8 0%, #3f55cd 100%); color:white; padding: 10px; margin:20px 20px 20px 0px;border-radius: 6px;">
    <div class="wpstg-welcome-box" style="display: flex; width: 100%; align-items: center;">
        <div class="wpstg-welcome-text" style="padding: 0px; padding-left: 20px; padding-right: 8px;">
            <p><?php echo sprintf(esc_html__('You are using %s for more than 1 week.
                May we ask you to give it a %s rating on wordpress.org?', 'wp-staging'), "<strong>WP STAGING</strong>", "<strong>5-star</strong>"); ?>
                <?php if (!defined('WPSTGPRO_VERSION')) { ?>
                    <br><br>
                    <?php esc_html_e('If you want to migrate your staging site to the live site or need a tremendously fast backup feature, try out ', 'wp-staging') ?> <a href="https://wp-staging.com/?utm_source=wpstg_admin&utm_medium=rating_screen&utm_campaign=admin_notice" target="_blank" style="color:white;font-weight:bold;">WP STAGING | PRO</a>
                    <br>
                <?php } ?>
            </p>
            <ul>
                <li>
                    <a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5#new-post" target="_blank" style="background-color:#e01e5a;border-color:transparent;margin-bottom:10px;color:white;font-weight:bold;-webkit-box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);-moz-box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);box-shadow: 1px 1px 8px -7px rgba(0,0,0,0.75);" id="wpstg_clicked_deserved_it" class="thankyou button"
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
                    <a href="javascript:void(0);" class="wpstg_rate_later" title="Ask me again in a week"
                    style="font-weight:normal;color:white;text-decoration: none;">
                        <?php esc_html_e('- Ask me again in a week - Close', 'wp-staging') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<script src="<?php echo esc_url($this->assets->getAssetsUrlWithVersion("js/dist/wpstg-admin-rating.min.js", '3.0.1')) ?>"></script>
