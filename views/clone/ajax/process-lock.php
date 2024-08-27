
<h3><?php echo esc_html($message); ?></h3>

<button type="button" class="wpstg-prev-step-link wpstg-link-btn button-primary wpstg-button">
    <?php esc_html_e("Back", "wp-staging") ?>
</button>

<button type="button" id="wpstg-restart-cloning" class="wpstg-link-btn button-primary wpstg-button">
    <?php echo esc_html__("Stop other process", "wp-staging")?>
</button>
