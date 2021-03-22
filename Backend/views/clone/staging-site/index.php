<span class="wpstg-notice-alert wpstg-mt-20px">
    <?php echo __("This staging site can be pushed and modified with WP STAGING Pro plugin installed on your production site! Open WP Staging Pro on your production site and start the pushing process from there!", "wp-staging")?>
    <br/>
    <?php echo sprintf(__("<a href='%s' target='_new'>Open WP STAGING Pro on Live Site</a>"), wpstg_get_production_hostname() . '/wp-admin/admin.php?page=wpstg_clone'); ?>
    <br/>
    <br/>
    <?php echo sprintf(__("If you like to clone this staging site check out <a href='%s' target='_new'>this article</a>."), 'https://wp-staging.com/docs/cloning-a-staging-site-testing-push-method/'); ?>
</span>