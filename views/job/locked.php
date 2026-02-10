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
    <div id="wpstg--locked-process" class="wpstg--locked-process wpstg-flex wpstg-items-center wpstg-justify-between wpstg-gap-4 wpstg-rounded-lg wpstg-border wpstg-border-slate-200/70 dark:wpstg-border-white/10 wpstg-bg-white dark:wpstg-bg-dark-boxes wpstg-shadow-sm wpstg-px-5 wpstg-py-4">
        <div class="wpstg--locked-process--body wpstg-flex wpstg-flex-1 wpstg-items-center wpstg-gap-4 wpstg-min-w-0">
            <div class="wpstg--locked-process--loader wpstg-shrink-0"></div>
            <div class="wpstg--locked-process--content wpstg-flex-1 wpstg-min-w-0">
                <?php if ($isDataAvailable) : ?>
                <div class="wpstg--locked-process--content--job wpstg-flex wpstg-items-start wpstg-justify-between wpstg-gap-4">
                    <div class="wpstg-flex wpstg-flex-col wpstg-gap-0.5 wpstg-min-w-0">
                        <span class="wpstg--locked-process--task-title wpstg-text-base wpstg-font-semibold wpstg-leading-tight wpstg-text-slate-900 dark:wpstg-text-slate-100"><?php esc_html_e('Processing...', 'wp-staging'); ?></span>
                        <span class="wpstg--locked-process--job-title wpstg-text-sm wpstg-text-slate-500 dark:wpstg-text-slate-400 wpstg-leading-tight"><?php echo esc_html($jobData['title']); ?></span>
                    </div>
                    <div class="wpstg-flex wpstg-flex-col wpstg-items-end wpstg-gap-0.5 wpstg-text-sm wpstg-text-slate-600 dark:wpstg-text-slate-300">
                        <span class="wpstg--locked-process--timer wpstg-inline-flex wpstg-items-center wpstg-gap-1">
                            <svg class="wpstg-text-slate-400" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7l-1.343 1.343m0 0A8 8 0 1 0 6.343 19.657A8 8 0 0 0 17.657 8.343M12 10v4M9 3h6"/></svg>
                            <span class="wpstg--locked-process--elapsed-time wpstg-font-medium">00:00</span>
                        </span>
                        <span class="wpstg-font-medium"><span class="wpstg--locked-process--percentage">0</span>%</span>
                    </div>
                </div>
                <div class="wpstg--locked-process--content--task wpstg-mt-2 wpstg-block">
                    <div class="wpstg-h-1.5 wpstg-w-full wpstg-rounded-full wpstg-bg-slate-200 dark:wpstg-bg-white/10" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e('Job progress', 'wp-staging'); ?>">
                        <div class="wpstg--locked-process--progress-bar wpstg-h-full wpstg-rounded-full wpstg-bg-blue-600 dark:wpstg-bg-blue-500" style="width:0%"></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!$isDataAvailable) : ?>
                    <span class="wpstg--locked-process--task-title wpstg-text-sm wpstg-leading-snug wpstg-text-slate-600"><?php esc_html_e('A WP Staging Job might be in progress. This message should disappear in few minutes. If you continue to see this message, contact the support!', 'wp-staging'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        // A cancel job should not be cancelled, also let avoid showing logs modal for cancel jobs
        if ($isDataAvailable && $jobData['type'] !== JobTransientCache::JOB_TYPE_CANCEL) : ?>
        <div class="wpstg--locked-process--footer wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-shrink-0">
            <button class="wpstg--locked-process--btn wpstg--locked-process--show-logs wpstg-btn wpstg-btn-md wpstg-btn-secondary"><?php esc_html_e('View live logs', 'wp-staging'); ?></button>
            <?php
            // Lets show cancel button only when it is cancellable
            if ($isCancelable) : ?>
            <button class="wpstg--locked-process--btn wpstg--locked-process--cancel-job wpstg-btn wpstg-btn-md wpstg-btn-danger"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($isDataAvailable) : ?>
    <script>
        WPStaging.initBackgroundLogger(<?php echo esc_js($jobData['startedAt']); ?>, '<?php echo esc_js($jobData['type']); ?>', '<?php echo esc_js($jobData['jobId']); ?>');
    </script>
    <?php endif; ?>
    <?php
    // as explained above, we only show the cancel modal if the job is cancellable
    if ($isCancelable) :
        require_once WPSTG_VIEWS_DIR . 'job/modal/confirm-cancel.php';
    endif;
}
