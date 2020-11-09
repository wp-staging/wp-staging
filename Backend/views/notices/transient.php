<div class="updated notice is-dismissible" style="border-left: 4px solid #ffba00;">
    <p>
        <?php
        echo esc_html(
            ('1' === $deactivatedNoticeID) ?
                __("WP Staging and WP STAGING Pro cannot both be active. We've automatically deactivated WP STAGING.", "wp-staging"):
                __("WP Staging and WP STAGING Pro cannot both be active. We've automatically deactivated WP STAGING Pro.", "wp-staging")
        )
        ?>
    </p>
</div>