<?php

use WPStaging\Framework\Database\TableDto;
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
?>
<p>
    <strong><?php esc_html_e("Select Tables to Copy", "wp-staging"); ?></strong>
    <br>
    <?php echo sprintf(esc_html__("Tables with the live site prefix %s have been selected.", "wp-staging"), "<code>" . esc_html($dbPrefix) . "</code>"); ?>
</p>
<p style="display: none;">
    <?php esc_html_e("Selected tables will be copied/replaced with the tables from the production site.", "wp-staging"); ?>
</p>
<div class="wpstg-my-10px">
    <a href="#" class="wpstg-button-unselect button"><?php esc_html_e('Unselect All', 'wp-staging'); ?></a>
    <a href="#" class="wpstg-button-select button"> <?php echo esc_html($dbPrefix) ?> </a>
    <a href="#" class="wpstg-button-unselect-wpstg button"> <?php esc_html_e('Unselect wpstg', 'wp-staging'); ?> </a>
</div>
<select multiple="multiple" id="wpstg_select_tables_cloning">
    <?php
    foreach ($tables as $table) :
        $attributes = !in_array($table->getName(), $excludedTables) && (strpos($table->getName(), $dbPrefix) === 0) ? "selected='selected'" : "";
        if ($stagingSetup->isUpdateOrResetJob() && !empty($stagingSiteDto->getIncludedTables())) {
            $attributes = !in_array($table->getName(), $excludedTables) && in_array($table->getName(), $stagingSiteDto->getIncludedTables()) ? "selected='selected'" : "";
        }

        ?>
        <option class="wpstg-db-table" value="<?php echo esc_attr($table->getName()) ?>" name="<?php echo esc_attr($table->getName()) ?>" <?php echo esc_html($attributes) ?>>
            <?php echo esc_html($table->getName()) ?> - <?php echo esc_html(size_format($table->getSize(), 2)) ?>
        </option>
    <?php endforeach ?>
</select>
<div class="wpstg-mt-10px">
    <a href="#" class="wpstg-button-unselect button"> <?php esc_html_e('Unselect All', 'wp-staging'); ?> </a>
    <a href="#" class="wpstg-button-select button"> <?php echo esc_html($dbPrefix); ?> </a>
    <a href="#" class="wpstg-button-unselect-wpstg button"> <?php esc_html_e('Unselect wpstg', 'wp-staging'); ?> </a>
</div>
<p>
    <?php esc_html_e("You can select multiple tables. Press left mouse button & move or press STRG+Left mouse button. (Apple: ⌘+Left Mouse Button)", "wp-staging"); ?>
</p>
