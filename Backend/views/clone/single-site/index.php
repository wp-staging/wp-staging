<ul id="wpstg-steps">
    <li class="wpstg-current-step wpstg-step1">
        <span class="wpstg-step-num">1</span>
        <?php echo __( "Overview", "wp-staging" ) ?>
    </li>
    <li class="wpstg-step2">
        <span class="wpstg-step-num">2</span>
        <?php echo __( "Scanning", "wp-staging" ) ?>
    </li>
    <li class="wpstg-step3 wpstg-step3-cloning">
        <span class="wpstg-step-num">3</span>
        <?php echo __( "Cloning", "wp-staging" ) ?>
    </li>
    <li class="wpstg-step3 wpstg-step3-pushing" style="display: none;">
        <span class="wpstg-step-num">3</span>
        <?php echo __( "Pushing", "wp-staging" ) ?>
    </li>
    <li>
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo __( "Report Issue", "wp-staging" ); ?>
        </button>
    </li>
</ul>

<div id="wpstg-workflow"></div>

<?php if (!defined('WPSTGPRO_VERSION')) { ?>
    <div id="wpstg-sidebar">
        <div class="wpstg-text-center">
            <span class="wpstg-feedback-span">
                <a class="wpstg-feedback-link" href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank" rel="external noopener">Give Feedback ★★★</a>
            </span>
        </div>
        <a href="https://wp-staging.com/?utm_source=tryout&utm_medium=plugin&utm_campaign=tryout&utm_term=tryout" target="_new">
            <img src="<?php echo WPSTG_PLUGIN_URL . 'Backend/public/img/wpstaging-banner200x400-tryout.gif'; ?>">
        </a>
    </div>
<?php } ?>
