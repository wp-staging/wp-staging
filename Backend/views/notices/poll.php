<div class="wpstg_poll update-nag wpstg-box-shadow">
    <p>
        <?php echo sprintf(esc_html__('Great, You are using %s for a while.', 'wp-staging'), "<strong>WP Staging</strong>"); ?>
        <?php esc_html_e('Hope you are happy with it.', 'wp-staging'); ?>

        <br><br>

        <?php esc_html_e('Are you interested in copying changes from WPStaging staging site back to your live site?', 'wp-staging'); ?>

        <br><br>

        <?php echo sprintf(esc_html__('Click on the %s Button and fill out the poll!', 'wp-staging'), "<a href='https://docs.google.com/forms/d/e/1FAIpQLScZ-dO5WffV3xObn16LwG05tr1HrADD_8L4wbTxPHqoPssVcg/viewform?c=0&w=1&usp=mail_form_link' target='_blank'><i>" . esc_html__('Yes, i am interested', 'wp-staging') . "</i></a>"); ?>

        <br>

        <?php esc_html_e('It only takes one (1) minute of your time - I promise!', 'wp-staging'); ?>

        <br><br>

        <?php esc_html_e('Cheers,', 'wp-staging'); ?>

        <br>

        <?php esc_html_e('René', 'wp-staging'); ?>
    <ul>
        <li class="wpstg-float-left">
            <a href="https://docs.google.com/forms/d/e/1FAIpQLScZ-dO5WffV3xObn16LwG05tr1HrADD_8L4wbTxPHqoPssVcg/viewform?c=0&w=1&usp=mail_form_link" class="thankyou button button-primary" target="_new" title="Yes, i am interested" style="color: #ffffff;font-weight: normal;margin-right:10px;float:left;">
                <?php esc_html_e('Yes, i am interested', 'wp-staging'); ?>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" data-url="<?php echo esc_url(admin_url("admin-ajax.php"))?>" class="wpstg_hide_poll" title="Close It" style="vertical-align:middle;">
                <?php esc_html_e('Do Not Ask Again', 'wp-staging'); ?>
            </a>
        </li>
    </ul>
</div>

<script type="text/javascript" src="<?php echo esc_url($this->assets->getAssetUrl("js/wpstg-admin-poll.js")) ?>"></script>
