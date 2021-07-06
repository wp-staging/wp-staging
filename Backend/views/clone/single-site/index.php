<ul id="wpstg-steps">
    <li class="wpstg-current-step wpstg-step1">
        <span class="wpstg-step-num">1</span>
        <?php echo __("Overview", "wp-staging") ?>
    </li>
    <li class="wpstg-step2">
        <span class="wpstg-step-num">2</span>
        <?php echo __("Scanning", "wp-staging") ?>
    </li>
    <li class="wpstg-step3 wpstg-step3-cloning">
        <span class="wpstg-step-num">3</span>
        <?php echo __("Cloning", "wp-staging") ?>
    </li>
    <li class="wpstg-step3 wpstg-step3-pushing" style="display: none;">
        <span class="wpstg-step-num">3</span>
        <?php echo __("Pushing", "wp-staging") ?>
    </li>
    <li>
        <div id="wpstg-report-issue-wrapper">
            <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
                <i class="wpstg-icon-issue"></i><?php echo __("Report Issue", "wp-staging"); ?>
            </button>
            <?php require_once($this->path . 'views/_main/report-issue.php'); ?>
        </div>
    </li>
</ul>

<div id="wpstg-workflow"></div>

<?php if (!defined('WPSTGPRO_VERSION')) { ?>
    <div id="wpstg-sidebar">
        <div class="wpstg-text-center">
            <span class="wpstg-feedback-span">
                <a class="wpstg-feedback-link wpstg--blue" href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank" rel="external noopener">Rate Plugin ★★★</a>
            </span>
        </div>
        <a href="https://wp-staging.com/?utm_source=tryout&utm_medium=plugin&utm_campaign=tryout&utm_term=tryout" target="_new">
            <img id="wpstg-sidebar--banner" src="<?php echo $this->assets->getAssetsUrl('img/wp-staging274x463-1.png'); ?>">
        </a>
    </div>
<?php } ?>
