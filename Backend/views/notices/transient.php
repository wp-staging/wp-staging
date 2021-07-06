<div class="updated notice is-dismissible">
    <p>
        <?php
        echo esc_html(
            ($deactivatedNoticeID === '1') ?
                __("WP Staging and WP STAGING Pro cannot both be active. We've automatically deactivated WP STAGING.", "wp-staging") :
                __("WP Staging and WP STAGING Pro cannot both be active. We've automatically deactivated WP STAGING Pro.", "wp-staging")
        )
        ?>
    </p>
</div>