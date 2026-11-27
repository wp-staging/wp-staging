<?php

/**
 * Template for the staging process screen that reports creating, updating or resetting a staging site.
 *
 * Rendered hidden on the staging page and cloned into the process modal — or into
 * the first run's inline slot — by js/src/staging/modules/creation-progress-view.js.
 *
 * @see \WPStaging\Backend\Administrator::getClonePage()
 * @see src/views/logs/logs-template.php for the diagnostics table reused below.
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Onboarding\QueuedBackup;

$sourceUrl = untrailingslashit(preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', (string)home_url()));

$sysinfoUrl = admin_url('admin-post.php?action=wpstg_download_sysinfo');
?>
<div id="wpstg-staging-creation" class="wpstg-staging-creation" data-view="progress" data-status="starting">
    <header class="wpstg-staging-creation__brand">
        <span class="wpstg-staging-creation__mark">
            <img src="<?php echo esc_url($this->assets->getAssetsUrl('img/wpstg-icon.svg')); ?>" width="25" height="25" alt="" aria-hidden="true">
            WP STAGING
        </span>
        <?php if ($sourceUrl !== '') : ?>
            <span class="wpstg-staging-creation__source" title="<?php echo esc_attr($sourceUrl); ?>"><?php echo esc_html($sourceUrl); ?></span>
        <?php endif; ?>
    </header>

    <div class="wpstg-staging-creation__body">
        <div class="wpstg-staging-creation__heading">
            <span class="wpstg-staging-creation__check" data-wpstg-success-check hidden aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20 6 9 17l-5-5"></path></svg>
            </span>
            <h2 class="wpstg-staging-creation__title" data-wpstg-title><?php esc_html_e('Creating your staging site', 'wp-staging'); ?></h2>
            <button type="button" class="wpstg-staging-creation__close" data-wpstg-close aria-label="<?php esc_attr_e('Close', 'wp-staging'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>
        <p class="wpstg-staging-creation__intro" data-wpstg-intro><?php esc_html_e('Please keep this window open while we create your staging site.', 'wp-staging'); ?></p>

        <div data-wpstg-run-panel>
            <ol class="wpstg-staging-creation__stages" data-wpstg-stages aria-label="<?php esc_attr_e('Creation stages', 'wp-staging'); ?>">
                <?php
                $stageLabels = [
                    __('Preparing', 'wp-staging'),
                    __('Database', 'wp-staging'),
                    __('Copying files', 'wp-staging'),
                    __('Finalizing', 'wp-staging'),
                ];
                foreach ($stageLabels as $stageIndex => $stageLabel) :
                    ?>
                    <li data-wpstg-stage="<?php echo (int)$stageIndex; ?>">
                        <span class="wpstg-staging-creation__stage-dot" aria-hidden="true"></span>
                        <span class="wpstg-staging-creation__stage-name"><?php echo esc_html($stageLabel); ?></span>
                        <span class="wpstg-staging-creation__stage-state" data-wpstg-stage-state><?php esc_html_e('Pending', 'wp-staging'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>

            <div class="wpstg-staging-creation__card">
                <div class="wpstg-staging-creation__card-head">
                    <span class="wpstg-staging-creation__stage-icon" aria-hidden="true">
                        <svg data-wpstg-stage-icon="0" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="m9 14 2 2 4-4"></path></svg>
                        <svg data-wpstg-stage-icon="1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v14a9 3 0 0 0 18 0V5"></path><path d="M3 12a9 3 0 0 0 18 0"></path></svg>
                        <svg data-wpstg-stage-icon="2" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"></path></svg>
                        <svg data-wpstg-stage-icon="3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><path d="M4 22v-7"></path></svg>
                    </span>
                    <div class="wpstg-staging-creation__phase">
                        <strong data-wpstg-stage-label role="status" aria-live="polite"><?php esc_html_e('Preparing your staging site', 'wp-staging'); ?></strong>
                        <p class="wpstg-staging-creation__detail" data-wpstg-detail></p>
                    </div>
                </div>

                <div class="wpstg-staging-creation__measure">
                    <strong class="wpstg-staging-creation__percent" data-wpstg-percent hidden></strong>
                    <div class="wpstg-staging-creation__eta" data-wpstg-eta hidden>
                        <span><?php esc_html_e('Time left in this step', 'wp-staging'); ?></span>
                        <strong>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            <span data-wpstg-eta-value></span>
                        </strong>
                    </div>
                </div>

                <div class="wpstg-staging-creation__track" data-wpstg-activity role="progressbar" aria-label="<?php esc_attr_e('Staging creation in progress.', 'wp-staging'); ?>">
                    <span class="wpstg-staging-creation__segment" data-wpstg-activity-segment></span>
                </div>

                <div class="wpstg-staging-creation__metrics">
                    <span data-wpstg-metrics></span>
                    <span data-wpstg-speed hidden></span>
                </div>
            </div>

            <div class="wpstg-staging-creation__connection" data-wpstg-connection hidden>
                <strong><?php esc_html_e('Connection interrupted', 'wp-staging'); ?></strong>
                <p data-wpstg-connection-text><?php esc_html_e('We’re waiting for the server to confirm progress. Reconnect to check the existing job.', 'wp-staging'); ?></p>
                <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--secondary" data-wpstg-reconnect><?php esc_html_e('Reconnect', 'wp-staging'); ?></button>
            </div>

            <?php
            if (!empty($offerBackupNext)) :
                $isBackupQueued = in_array($queuedBackupStatus, [QueuedBackup::STATUS_QUEUED, QueuedBackup::STATUS_RUNNING], true);
                ?>
                <div class="wpstg-staging-creation__backup-offer" data-wpstg-backup-offer data-state="<?php echo $isBackupQueued ? 'queued' : 'idle'; ?>" data-first-run="<?php echo empty($isFirstRunOffer) ? '0' : '1'; ?>" hidden>
                    <span class="wpstg-staging-creation__backup-mark" aria-hidden="true" data-wpstg-backup-mark>+</span>
                    <div class="wpstg-staging-creation__backup-copy">
                        <strong data-wpstg-backup-offer-title><?php esc_html_e('Add a backup of your live site', 'wp-staging'); ?></strong>
                        <p data-wpstg-backup-offer-text><?php esc_html_e('Optional. Starts after your staging site is ready.', 'wp-staging'); ?></p>
                    </div>
                    <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--secondary wpstg-staging-creation__btn--small" data-wpstg-backup-action>
                        <?php esc_html_e('Add backup', 'wp-staging'); ?>
                    </button>
                </div>
            <?php endif; ?>

            <div class="wpstg-staging-creation__confirm" data-wpstg-cancel-confirm hidden>
                <strong data-wpstg-stop-question><?php esc_html_e('Stop creating this staging site?', 'wp-staging'); ?></strong>
                <p data-wpstg-stop-consequence><?php esc_html_e('Partly copied files and tables are cleaned up by the server.', 'wp-staging'); ?></p>
                <div class="wpstg-staging-creation__confirm-actions">
                    <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--secondary" data-wpstg-keep-going><?php esc_html_e('Keep creating', 'wp-staging'); ?></button>
                    <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--danger" data-wpstg-confirm-stop><?php esc_html_e('Stop creation', 'wp-staging'); ?></button>
                </div>
            </div>
        </div>

        <div data-wpstg-success-panel hidden>
            <div class="wpstg-staging-creation__site">
                <span class="wpstg-staging-creation__site-dot" aria-hidden="true"></span>
                <div class="wpstg-staging-creation__site-id">
                    <strong data-wpstg-site-name></strong>
                    <span data-wpstg-site-url></span>
                </div>
                <span class="wpstg-staging-creation__badge"><?php esc_html_e('Ready', 'wp-staging'); ?></span>
            </div>
            <p class="wpstg-staging-creation__finished-in" data-wpstg-finished-in hidden></p>
            <div class="wpstg-staging-creation__actions">
                <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--primary wpstg-open-staging-site" data-wpstg-open-staging>
                    <?php esc_html_e('Open staging site', 'wp-staging'); ?>
                    <span aria-hidden="true">↗</span>
                </button>
                <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--secondary" data-wpstg-manage-staging><?php esc_html_e('Close', 'wp-staging'); ?></button>
            </div>

            <?php
            $autoLoginCtaClass = 'wpstg-staging-creation__btn wpstg-staging-creation__btn--pro-locked';
            include WPSTG_VIEWS_DIR . 'staging/_partials/auto-login-pro-cta.php';
            unset($autoLoginCtaClass);
            ?>

            <div class="wpstg-staging-creation__review" data-wpstg-review-slot></div>

            <div class="wpstg-staging-creation__backup-status" data-wpstg-backup-status hidden>
                <strong data-wpstg-backup-status-title></strong>
                <p data-wpstg-backup-status-text></p>
                <div data-wpstg-backup-panel></div>
            </div>
        </div>

        <div data-wpstg-stopped-panel hidden>
            <p class="wpstg-staging-creation__stopped-message" data-wpstg-stopped-message></p>
            <div class="wpstg-staging-creation__actions">
                <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--primary" data-wpstg-start-again><?php esc_html_e('Back to staging sites', 'wp-staging'); ?></button>
            </div>
        </div>

        <section id="wpstg-staging-creation-logs" class="wpstg-staging-creation__logs" aria-label="<?php esc_attr_e('Detailed process log', 'wp-staging'); ?>" hidden>
            <div class="wpstg-staging-creation__logs-title">
                <strong><?php esc_html_e('Process log', 'wp-staging'); ?></strong>
                <span data-wpstg-elapsed></span>
            </div>

            <div class="wpstg--logs--container wpstg-staging-creation__logs-table">
                <?php
                $logType = 'staging-creation';
                require WPSTG_VIEWS_DIR . 'logs/logs-template.php';
                unset($logType);
                ?>
            </div>

            <div class="wpstg-staging-creation__logs-actions">
                <button type="button" class="wpstg-staging-creation__log-action" data-wpstg-copy-log><?php esc_html_e('Copy', 'wp-staging'); ?></button>
                <button type="button" class="wpstg-staging-creation__log-action" data-wpstg-download-log><?php esc_html_e('Download', 'wp-staging'); ?></button>
            </div>

            <div class="wpstg-staging-creation__logs-foot">
                <span data-wpstg-follow-label><?php esc_html_e('Following latest activity', 'wp-staging'); ?></span>
                <button type="button" class="wpstg-staging-creation__log-action" data-wpstg-jump-latest hidden><?php esc_html_e('Jump to latest', 'wp-staging'); ?></button>
            </div>

            <details class="wpstg-staging-creation__system-info">
                <summary><?php esc_html_e('System information', 'wp-staging'); ?></summary>
                <dl>
                    <dt><?php esc_html_e('Live site', 'wp-staging'); ?></dt>
                    <dd><?php echo esc_html($sourceUrl); ?></dd>
                    <dt><?php esc_html_e('WP STAGING', 'wp-staging'); ?></dt>
                    <dd><?php echo esc_html(\WPStaging\Core\WPStaging::getVersion()); ?></dd>
                    <dt><?php esc_html_e('WordPress', 'wp-staging'); ?></dt>
                    <dd><?php echo esc_html(get_bloginfo('version')); ?></dd>
                    <dt><?php esc_html_e('PHP', 'wp-staging'); ?></dt>
                    <dd><?php echo esc_html(PHP_VERSION); ?></dd>
                </dl>
                <p>
                    <?php
                    echo sprintf(
                        Escape::escapeHtml(__('The complete environment and plugin inventory is in the <a href="%s" target="_blank" rel="noopener">system information export</a>.', 'wp-staging')),
                        esc_url($sysinfoUrl)
                    );
                    ?>
                </p>
            </details>

            <p class="wpstg-staging-creation__log-feedback" data-wpstg-log-feedback role="status" aria-live="polite"></p>
        </section>

        <div class="wpstg-staging-creation__footer" data-wpstg-footer>
            <button type="button" class="wpstg-staging-creation__text-btn wpstg-staging-creation__logs-toggle" data-wpstg-logs-toggle aria-expanded="false" aria-controls="wpstg-staging-creation-logs">
                <span class="wpstg-staging-creation__chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m6 9 6 6 6-6"></path></svg>
                </span>
                <span data-wpstg-logs-toggle-label><?php esc_html_e('Show more details', 'wp-staging'); ?></span>
            </button>
            <button type="button" class="wpstg-staging-creation__btn wpstg-staging-creation__btn--secondary" data-wpstg-cancel><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
        </div>
    </div>
</div>
