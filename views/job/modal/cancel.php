<?php
/**
 * @see views/job/locked.php
 * @var array $jobData contains job data for current job
 */
?>
<div id="wpstg--modal--cancel-job" class="wpstg--modal--cancel-job">
    <h2 class="wpstg--modal--title"><?php esc_html_e('Cancel Current Job!', 'wp-staging') ?></h2>    
    <p>
        <?php esc_html_e('Are you sure? This process is irreversible!', 'wp-staging') ?> <br/>
        <?php esc_html_e('Once the job is cancelled, you will lose all of the progress made so far!', 'wp-staging') ?>
    </p>
</div>
