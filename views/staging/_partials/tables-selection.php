<?php

use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\StagingSetup;

/**
 * @var string         $dbPrefix
 * @var StagingSetup   $stagingSetup
 * @var StagingSiteDto $stagingSiteDto
 * @var TableDto[]     $tables
 * @var string[]       $excludedTables
 *
 * @see WPStaging\Staging\Service\TableScanner::renderTablesSelection
 */

?>
<?php
do_action("wpstg_scanning_db");

/**
 * WordPress core tables (without prefix). Excluding any of these from a staging
 * site can break it, so the modal surfaces a warning when one is deselected.
 */
$wpCoreTableSuffixes = [
    'commentmeta',
    'comments',
    'links',
    'options',
    'postmeta',
    'posts',
    'term_relationships',
    'term_taxonomy',
    'termmeta',
    'terms',
    'usermeta',
    'users',
];

// The redesigned reset modal reuses the shared update-style selection chrome
// (.wpstg-update-selection in update.scss restyles these rows), so reset, update
// and create all render the same panel; per-modal CSS handles the rest.
$wrapperClass = 'wpstg-selection-panel';
$headerClass  = 'wpstg-selection-header';
$listClass    = 'wpstg-table-selection-list wpstg-selection-list';
?>
<div class="<?php echo esc_attr($wrapperClass); ?>">
    <div class="<?php echo esc_attr($headerClass); ?>">
        <div class="wpstg-min-w-0">
            <strong class="wpstg-selection-title"><?php esc_html_e("Select Tables to Copy", "wp-staging"); ?></strong>
        </div>

        <div class="wpstg-selection-actions">
            <span class="wpstg-selection-actions-label"><?php esc_html_e('Quick select:', 'wp-staging'); ?></span>
            <span class="wpstg--tooltip wpstg--tooltip-normal wpstg-selection-action-tip">
                <button type="button" class="wpstg-button-unselect wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded"><?php esc_html_e('Deselect all tables', 'wp-staging'); ?></button>
                <span class="wpstg--tooltiptext"><?php esc_html_e('Clear the selection so you can pick tables manually.', 'wp-staging'); ?></span>
            </span>
            <span class="wpstg--tooltip wpstg--tooltip-normal wpstg-selection-action-tip">
                <button type="button" class="wpstg-button-select wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded"><?php esc_html_e('Select live site tables', 'wp-staging'); ?></button>
                <span class="wpstg--tooltiptext"><?php printf(esc_html__('Select all tables that use this site\'s prefix (%s).', 'wp-staging'), '<code>' . esc_html($dbPrefix) . '</code>'); ?></span>
            </span>
            <span class="wpstg--tooltip wpstg--tooltip-normal wpstg-selection-action-tip">
                <button type="button" class="wpstg-button-unselect-wpstg wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded"><?php esc_html_e('Exclude staging tables', 'wp-staging'); ?></button>
                <span class="wpstg--tooltiptext"><?php esc_html_e('Remove tables that belong to existing WP STAGING staging sites, keeping any custom selection intact.', 'wp-staging'); ?></span>
            </span>
        </div>
    </div>

    <p class="wpstg-hidden">
        <?php esc_html_e("Selected tables will be copied/replaced with the tables from the production site.", "wp-staging"); ?>
    </p>

    <select multiple="multiple" id="wpstg_select_tables_cloning" class="wpstg-hidden" aria-hidden="true">
        <?php
        /** @var TableDto $table */
        foreach ($tables as $table) :
            $tableName  = $table->getName();
            $tableSize  = size_format($table->getSize(), 2);
            $isSelected = !in_array($tableName, $excludedTables) && (strpos($tableName, $dbPrefix) === 0);
            if ($stagingSetup->isUpdateOrResetJob() && !empty($stagingSiteDto->getIncludedTables())) {
                $isSelected = !in_array($tableName, $excludedTables) && in_array($tableName, $stagingSiteDto->getIncludedTables());
            }
            ?>
            <option class="wpstg-db-table" value="<?php echo esc_attr($tableName); ?>" name="<?php echo esc_attr($tableName); ?>" data-size="<?php echo esc_attr($tableSize); ?>" <?php selected($isSelected); ?>>
                <?php echo esc_html($tableName); ?> - <?php echo esc_html($tableSize); ?>
            </option>
        <?php endforeach ?>
    </select>

    <div class="<?php echo esc_attr($listClass); ?>">
        <?php
        foreach ($tables as $table) :
            $tableName  = $table->getName();
            $tableSize  = size_format($table->getSize(), 2);
            $isSelected = !in_array($tableName, $excludedTables) && (strpos($tableName, $dbPrefix) === 0);
            if ($stagingSetup->isUpdateOrResetJob() && !empty($stagingSiteDto->getIncludedTables())) {
                $isSelected = !in_array($tableName, $excludedTables) && in_array($tableName, $stagingSiteDto->getIncludedTables());
            }

            $checkboxId = 'wpstg-table-' . md5($tableName);

            $tableSuffix     = strpos($tableName, $dbPrefix) === 0 ? substr($tableName, strlen($dbPrefix)) : $tableName;
            $isCoreTable     = in_array($tableSuffix, $wpCoreTableSuffixes, true);
            $checkboxClasses = $isCoreTable ? 'wpstg-db-table-checkbox wpstg-core-table' : 'wpstg-db-table-checkbox';

            // Critical-table chip: wp_options drives settings, wp_users drives logins/accounts.
            $tableChip = '';
            if ($stagingSetup->isUpdateOrResetJob()) {
                if ($tableSuffix === 'options') {
                    $tableChip = esc_html__('settings', 'wp-staging');
                } elseif ($tableSuffix === 'users') {
                    $tableChip = esc_html__('users', 'wp-staging');
                }
            }
            ?>
            <label class="wpstg-table-selection-row wpstg-selection-row" for="<?php echo esc_attr($checkboxId); ?>" data-size-bytes="<?php echo esc_attr((string)(int)$table->getSize()); ?>">
                <?php Checkbox::render($checkboxId, 'wpstg_table_selection[]', $tableName, $isSelected, ['classes' => $checkboxClasses, 'usePrimitive' => true]); ?>
                <span class="wpstg-selection-row-content">
                    <span class="wpstg-selection-row-title"><?php echo esc_html($tableName); ?></span>
                    <?php if ($tableChip !== '') : ?>
                        <span class="wpstg-update-table-chip"><?php echo $tableChip; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <?php endif; ?>
                    <span class="wpstg-selection-row-meta"><?php echo esc_html($tableSize); ?></span>
                </span>
            </label>
        <?php endforeach ?>
    </div>
</div>
