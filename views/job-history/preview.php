<?php

/**
 * Job History preview below the Developer plan: the upgrade offer above sample rows, never this site's own.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Pro\License\Licensing;

$wpstgUpgradeUrl = class_exists(Licensing::class)
    ? WPStaging::make(Licensing::class)->getUpgradeToDevUrl('job_history')
    : Language::getUpgradeUrl('job_history');

$wpstgSampleTime = function ($hour, $minute) {
    return date_i18n(get_option('time_format'), ($hour * 60 + $minute) * MINUTE_IN_SECONDS, true);
};

$wpstgColumns = [
    ['label' => __('When', 'wp-staging'),       'icon' => 'clock'],
    ['label' => __('Operation', 'wp-staging'),  'icon' => 'settings'],
    ['label' => __('Target', 'wp-staging'),     'icon' => 'root-folder'],
    ['label' => __('Started by', 'wp-staging'), 'icon' => 'user-plus'],
    ['label' => __('Result', 'wp-staging'),     'icon' => 'check'],
    ['label' => __('Duration', 'wp-staging'),   'icon' => 'timer'],
];

$wpstgRuns = [
    [
        'startedOn'      => __('Today', 'wp-staging'),
        'startedAtTime'  => $wpstgSampleTime(16, 42),
        'operationLabel' => __('Staging Push', 'wp-staging'),
        'operationIcon'  => 'push',
        'operationGroup' => 'sync',
        'target'         => 'Checkout Redesign',
        'actorInitial'   => 'J',
        'actorLabel'     => 'Jane Cooper',
        'status'         => 'success',
        'statusLabel'    => __('Success', 'wp-staging'),
        'duration'       => '4m 12s',
    ],
    [
        'startedOn'      => __('Today', 'wp-staging'),
        'startedAtTime'  => $wpstgSampleTime(3, 0),
        'operationLabel' => __('Backup', 'wp-staging'),
        'operationIcon'  => 'hard-drive',
        'operationGroup' => 'backup',
        'target'         => 'Nightly Backup',
        'actorInitial'   => '',
        'actorLabel'     => __('Schedule', 'wp-staging'),
        'status'         => 'success',
        'statusLabel'    => __('Success', 'wp-staging'),
        'duration'       => '3m 05s',
    ],
    [
        'startedOn'      => __('Yesterday', 'wp-staging'),
        'startedAtTime'  => $wpstgSampleTime(15, 10),
        'operationLabel' => __('Staging Create', 'wp-staging'),
        'operationIcon'  => 'folder-new',
        'operationGroup' => 'staging',
        'target'         => 'Checkout Redesign',
        'actorInitial'   => 'M',
        'actorLabel'     => 'Marcus Chen',
        'status'         => 'success',
        'statusLabel'    => __('Success', 'wp-staging'),
        'duration'       => '5m 34s',
    ],
    [
        'startedOn'      => __('Yesterday', 'wp-staging'),
        'startedAtTime'  => $wpstgSampleTime(11, 34),
        'operationLabel' => __('Remote Sync', 'wp-staging'),
        'operationIcon'  => 'remote-sync',
        'operationGroup' => 'sync',
        'target'         => 'legacy-shop.com',
        'actorInitial'   => 'P',
        'actorLabel'     => 'Priya Shah',
        'status'         => 'failed',
        'statusLabel'    => __('Failed', 'wp-staging'),
        'duration'       => '38s',
    ],
];

$wpstgTableLabel      = __('Sample operations', 'wp-staging');
$wpstgSystemActorIcon = 'clock';

?>
<div class="wpstg-job-history wpstg-job-history-preview">

    <div class="wpstg-callout wpstg-callout-info wpstg-mb-6">
        <div class="wpstg-icon-box wpstg-icon-box-blue">
            <svg class="wpstg-h-5 wpstg-w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div class="wpstg-flex-1">
            <h3 class="wpstg-heading-lg">
                <?php esc_html_e('Unlock Job History', 'wp-staging'); ?>
            </h3>
            <p class="wpstg-text-body wpstg-text-sm wpstg-m-0">
                <?php esc_html_e('When several people administer a site, Job History answers who ran a backup, who pushed a staging site to live, and whether it worked.', 'wp-staging'); ?>
            </p>
            <p class="wpstg-text-body wpstg-text-sm wpstg-m-0 wpstg-mb-4">
                <?php esc_html_e('Job History is available with a WP Staging Developer or Agency plan.', 'wp-staging'); ?>
            </p>
            <a href="<?php echo esc_url($wpstgUpgradeUrl); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-no-underline" data-wpstg-upgrade-cta>
                <?php esc_html_e('Unlock Job History', 'wp-staging'); ?>
                <svg class="wpstg-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>

    <p class="wpstg-job-history-preview-label">
        <?php esc_html_e('Sample data', 'wp-staging'); ?>
    </p>

    <div class="wpstg-job-history-card">
        <?php require WPSTG_VIEWS_DIR . 'job-history/_partials/runs-table.php'; ?>

        <div class="wpstg-job-history-footer">
            <span><?php esc_html_e('Upgrade to see who started each run and how it ended.', 'wp-staging'); ?></span>
        </div>
    </div>

</div>
