<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;

$processLock = WPStaging::make(ProcessLock::class);
$jobData     = WPStaging::make(JobTransientCache::class)->getJob();
$isLocked    = false;

try {
    $processLock->checkProcessLocked();
    $isLocked = isset($jobData['status']) && $jobData['status'] === JobTransientCache::STATUS_RUNNING;
} catch (ProcessLockedException $e) {
    $isLocked = true;
}

if ($isLocked) {
    $isDataAvailable = !empty($jobData);
    $isCancelable    = $jobData['status'] === JobTransientCache::STATUS_RUNNING;
    ?>
    <div id="wpstg--locked-process" class="wpstg--locked-process">
        <div class="wpstg--locked-process--body">
            <div class="wpstg--locked-process--loader"></div>
            <div class="wpstg--locked-process--content">
                <?php if ($isDataAvailable) : ?>
                <div class="wpstg--locked-process--content--job">
                    <span class="wpstg--locked-process--job-title"><?php echo esc_html($jobData['title']); ?></span>
                    <span class="wpstg--locked-process--timer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7l-1.343 1.343m0 0A8 8 0 1 0 6.343 19.657A8 8 0 0 0 17.657 8.343M12 10v4M9 3h6"/></svg>
                        <span class="wpstg--locked-process--elapsed-time">00:00</span>
                    </span>
                </div>
                <div class="wpstg--locked-process--content--task">
                    <span><?php esc_html_e('Task: ', 'wp-staging'); ?><span class="wpstg--locked-process--task-title"><?php esc_html_e('Processing...', 'wp-staging'); ?></span></span>
                    <span><span class="wpstg--locked-process--percentage">0</span>%</span>
                </div>
                <?php endif; ?>
                <?php if (!$isDataAvailable) : ?>
                    <span class="wpstg--locked-process--task-title"><?php esc_html_e('A WP Staging Job might be in progress. This message should disappear in few minutes. If you continue to see this message, contact the support!', 'wp-staging'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        // A cancel job should not be cancelled, also let avoid showing logs modal for cancel jobs
        if ($isDataAvailable && $jobData['type'] !== JobTransientCache::JOB_TYPE_CANCEL) : ?>
        <div class="wpstg--locked-process--footer">
            <button class="wpstg--locked-process--btn wpstg--locked-process--show-logs wpstg-button wpstg-border-thin-button"><?php esc_html_e('Show Logs', 'wp-staging'); ?></button>
            <?php
            // Lets show cancel button only when it is cancellable
            if ($isCancelable) : ?>
            <button class="wpstg--locked-process--btn wpstg--locked-process--cancel-job wpstg-button wpstg-border-thin-button"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($isDataAvailable) : ?>
    <script>
        WPStaging.initBackgroundLogger(<?php echo esc_js($jobData['start']); ?>, '<?php echo esc_js($jobData['type']); ?>', '<?php echo esc_js($jobData['jobId']); ?>');
    </script>
    <?php endif; ?>
    <?php
    // as explained above, we only show the cancel modal if the job is cancellable
    if ($isCancelable) :
        require_once WPSTG_VIEWS_DIR . 'job/modal/confirm-cancel.php';
    endif;
}
