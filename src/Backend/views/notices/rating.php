<div class="wpstg_fivestar"
     style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);border-left:none;background-color:#59a7f7;color:white;padding: 10px;margin: 10px;margin-left: 0px;">
    <p><?php _e(' Awesome, you\'ve been using <strong>WP Staging </strong> for more than 1 week.
        May I ask you to give it a <strong>5-star</strong> rating on Wordpress?', 'wp-staging'); ?>
        <?php if (!defined('WPSTGPRO_VERSION')) { ?>
            <br><br>
            <?php echo sprintf(__('P.S. Looking for a way to migrate the staging site database and copy plugins and theme files from staging to live site?<br/>
           Try out <a href="%1$s" target="_blank" style="color:white;font-weight:bold;">WP Staging Pro</a>
                ', 'wp-staging'), 'https://wp-staging.com/?utm_source=wpstg_admin&utm_medium=rating_screen&utm_campaign=admin_notice'); ?>
            <br>
        <?php } ?>
    </p>
    <p>
        Cheers,<br>René Hermenau
    </p>

    <ul>
        <li>
            <a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5#new-post" class="thankyou button"
               target="_new" title="Ok, you deserved it" style="font-weight:bold;">
                <?php _e('Ok, you deserved it', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" class="wpstg_hide_rating" title="I already did"
               style="font-weight:normal;color:white;">
                <?php _e('I already did', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" class="wpstg_hide_rating" title="No, not good enough"
               style="font-weight:normal;color:white;">
                <?php _e('No, not good enough', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" class="wpstg_rate_later" title="Ask me again in a week"
               style="font-weight:normal;color:white;">
                <?php _e('I want to rate later - Ask me again in a week', 'wp-staging') ?>
            </a>
        </li>
    </ul>
</div>
<script type="text/javascript" src="<?php echo $this->url . "js/wpstg-admin-rating.js" ?>"></script>