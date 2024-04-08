<?php

$assetsUrl    = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
$displayStyle = \WPStaging\Core\WPStaging::isPro() ? 'display:none;' : 'display:block;';
$modalType    = $modalType ?? 'backup';
?>

<div id="wpstg-<?php echo esc_attr($modalType); ?>-success-modal" class="wpstg-<?php echo esc_attr($modalType); ?>-success-modal">
    <div id="wpstg-<?php echo esc_attr($modalType); ?>-success-modal-inner">
        <h2>{title}</h2>
        <p class="wpstg-<?php echo esc_attr($modalType); ?>-success-modal-text">{text}</p>
        <div class="wpstg-<?php echo esc_attr($modalType); ?>-success-logs-wrapper">
            <button class="wpstg-<?php echo esc_attr($modalType); ?>-success-logs-button">{btnTxtLog}</button>
            <div class="wpstg-<?php echo esc_attr($modalType); ?>-success-logs"></div>
        </div>
        <div class="wpstg-rate-us" style="<?php echo esc_attr($displayStyle); ?>">
            <div class="wpstg-rate-us-inner">
                <div class="wpstg-rate-us-emoji">
                    <img src="<?php echo esc_url($assetsUrl); ?>svg/thumbs-up.svg" alt="Rate Us"/>
                </div>
                <div>
                    <p><?php esc_html_e('Did you like how easy it was? Please rate us well so others can find and enjoy WP Staging, too. Thanks a lot!', 'wp-staging') ?></p>
                    <p class="wpstg-rating-sub-desc"><?php esc_html_e('Btw. This asking for a rating is not part of WP Staging Pro to meet the demands of professional users and provide a cleaner user interface.', 'wp-staging') ?></p>
                    <div class="wpstg-rate-us-action">
                        <a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank" class="wpstg-blue-primary wpstg-button"> <?php esc_html_e('Sure, let me rate it', 'wp-staging') ?></a>
                        <div class="wpstg--tooltip" id="wpstg-how-to-login-link">
                            <a href="javascript:void(0)"> <?php esc_html_e('How to log in?', 'wp-staging') ?></a>
                            <div class="wpstg--tooltiptext">
                                <?php
                                echo sprintf(
                                    esc_html__('You need a wordpress.org account for rating WP Staging. That is a different account than you have on this site. If you haven\'t made a wordpress.org account, just %s and then click %s. It\'s quick - just a minute. Thanks a lot!', 'wp-staging'),
                                    '<a href="https://login.wordpress.org/register?locale=en_US" target="_blank">' . esc_html('register first') . '</a>',
                                    '<a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank">' . esc_html('Sure, let me rate it') . '</a>'
                                );
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
