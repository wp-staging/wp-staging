<?php

/**
 * The table of runs the Job History listing and its preview both show, so the two cannot drift apart.
 *
 * @var array $wpstgColumns each entry has label, icon, and optionally sort, isSorted, order and nextOrder
 * @var array $wpstgRuns each entry carries the fields JobHistoryPresenter gives a row
 * @var string $wpstgTableLabel
 * @var string $wpstgSystemActorIcon optional, the icon of a run nobody started by hand
 * @var string $wpstgRunActionsView optional, a view rendering the trailing cell of $wpstgRun
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;

?>
<div class="wpstg-job-history-scroller" tabindex="0" role="region" aria-label="<?php echo esc_attr($wpstgTableLabel); ?>">
<table class="wpstg-job-history-table">
    <thead>
        <tr>
            <?php foreach ($wpstgColumns as $wpstgColumn) : ?>
                <?php $wpstgIsSorted = !empty($wpstgColumn['isSorted']); ?>
                <th>
                    <?php if (empty($wpstgColumn['sort'])) : ?>
                        <span class="wpstg-job-history-th">
                            <?php WPStaging::make(Assets::class)->renderSvg($wpstgColumn['icon']); ?>
                            <?php echo esc_html($wpstgColumn['label']); ?>
                        </span>
                    <?php else : ?>
                        <button type="button"
                                class="wpstg-job-history-th wpstg-job-history-sort<?php echo $wpstgIsSorted ? ' is-sorted' : ''; ?>"
                                data-sort="<?php echo esc_attr($wpstgColumn['sort']); ?>"
                                data-order="<?php echo esc_attr($wpstgColumn['nextOrder']); ?>"
                                aria-sort="<?php echo $wpstgIsSorted ? esc_attr($wpstgColumn['order'] === 'asc' ? 'ascending' : 'descending') : 'none'; ?>">
                            <?php WPStaging::make(Assets::class)->renderSvg($wpstgColumn['icon']); ?>
                            <?php echo esc_html($wpstgColumn['label']); ?>
                            <svg class="wpstg-job-history-sort-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <?php if (!$wpstgIsSorted) : ?>
                                    <path d="m8 9 4-4 4 4"/><path d="m16 15-4 4-4-4"/>
                                <?php elseif ($wpstgColumn['order'] === 'asc') : ?>
                                    <path d="m6 15 6-6 6 6"/>
                                <?php else : ?>
                                    <path d="m6 9 6 6 6-6"/>
                                <?php endif; ?>
                            </svg>
                        </button>
                    <?php endif; ?>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($wpstgRuns as $wpstgRun) : ?>
            <tr class="wpstg-job-history-row wpstg-job-history-row--<?php echo esc_attr($wpstgRun['status']); ?>">
                <td>
                    <div class="wpstg-job-history-when" title="<?php echo esc_attr($wpstgRun['startedAtFull'] ?? ''); ?>">
                        <span><?php echo esc_html($wpstgRun['startedOn']); ?></span>
                        <span class="wpstg-job-history-when-time"><?php echo esc_html($wpstgRun['startedAtTime']); ?></span>
                    </div>
                </td>
                <td>
                    <span class="wpstg-job-history-operation">
                        <span class="wpstg-job-history-op-chip wpstg-job-history-op-chip--<?php echo esc_attr($wpstgRun['operationGroup']); ?>">
                            <?php WPStaging::make(Assets::class)->renderSvg($wpstgRun['operationIcon']); ?>
                        </span>
                        <?php echo esc_html($wpstgRun['operationLabel']); ?>
                    </span>
                </td>
                <td>
                    <span class="wpstg-job-history-target" title="<?php echo esc_attr($wpstgRun['target']); ?>">
                        <?php echo $wpstgRun['target'] === '' ? '&mdash;' : esc_html($wpstgRun['target']); ?>
                    </span>
                </td>
                <td>
                    <span class="wpstg-job-history-actor">
                        <?php if ($wpstgRun['actorInitial'] !== '') : ?>
                            <span class="wpstg-job-history-avatar"><?php echo esc_html($wpstgRun['actorInitial']); ?></span>
                        <?php elseif (!empty($wpstgSystemActorIcon)) : ?>
                            <span class="wpstg-job-history-avatar wpstg-job-history-avatar--system">
                                <?php WPStaging::make(Assets::class)->renderSvg($wpstgSystemActorIcon); ?>
                            </span>
                        <?php else : ?>
                            <span class="wpstg-job-history-avatar wpstg-job-history-avatar--system">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="3"/><path d="M12 2v3"/><path d="M12 19v3"/><path d="M4.9 4.9l2.1 2.1"/><path d="M17 17l2.1 2.1"/><path d="M2 12h3"/><path d="M19 12h3"/><path d="M4.9 19.1L7 17"/><path d="M17 7l2.1-2.1"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                        <?php echo esc_html($wpstgRun['actorLabel']); ?>
                    </span>
                </td>
                <td>
                    <span class="wpstg-job-history-status wpstg-job-history-status--<?php echo esc_attr($wpstgRun['status']); ?>">
                        <?php echo esc_html($wpstgRun['statusLabel']); ?>
                    </span>
                    <?php if (($wpstgRun['message'] ?? '') !== '') : ?>
                        <span class="wpstg--tooltip wpstg-job-history-message" tabindex="0" role="group" aria-label="<?php esc_attr_e('Why it failed', 'wp-staging'); ?>">
                            <span class="wpstg-job-history-message-text"><?php echo esc_html($wpstgRun['message']); ?></span>
                            <span class="wpstg--tooltiptext wpstg-job-history-message-tooltip"><?php echo esc_html($wpstgRun['message']); ?></span>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="wpstg-job-history-duration">
                        <?php echo $wpstgRun['duration'] === '' ? '&mdash;' : esc_html($wpstgRun['duration']); ?>
                    </span>
                </td>
                <?php if (!empty($wpstgRunActionsView)) : ?>
                    <?php require $wpstgRunActionsView; ?>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
