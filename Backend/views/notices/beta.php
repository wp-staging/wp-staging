<!-- Not used any longer. So can be used for other purposes in the future //-->
<div class="wpstg_beta_notice wpstg-box-shadow wpstg-error">
    <p>
        <?php esc_html_e("WP Staging is well tested and we did a lot to catch every possible error but
        we can not handle all possible combinations of server, plugins and themes. <br>
        <strong>BEFORE</strong> you create your first staging site it´s highly recommended
        <strong>to make a full backup of your website</strong> first!", "wp-staging") ?>
    </p>
    <p>
        <?php esc_html_e("A good plugin for an entire WordPress backup is the free one", "wp-staging") ?>
        <a href="https://wordpress.org/plugins/backwpup/" target="_blank">BackWPup</a>
    </p>
    <ul>
        <li>
            <a href="javascript:void(0);" class="wpstg_hide_beta" title="I understand" data-url="<?php echo esc_url(admin_url("admin-ajax.php"))?>" style="font-weight:bold;">
                <?php esc_html_e("I understand! (Do not show this again)", "wp-staging") ?>
            </a>
        </li>
    </ul>
</div>
<script type="text/javascript" src="<?php echo esc_url($this->assets->getAssetUrl("js/dist/wpstg-admin-beta.js")) ?>"></script>
