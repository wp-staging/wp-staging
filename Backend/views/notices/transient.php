<div class="updated notice is-dismissible" style="border-left: 4px solid #ffba00;">
    <p>
        <?php
        echo esc_html(
            ('1' === $deactivatedNoticeID) ?
                __("WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging.", "wp-staging"):
                __("WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging Pro.", "wp-staging")
        )
        ?>
    </p>
</div>