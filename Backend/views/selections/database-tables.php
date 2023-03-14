<?php
/**
 * @var stdClass $options
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */
?>
<?php
    do_action("wpstg_scanning_db");
    $dbPrefix = WPStaging\Core\WPStaging::getTablePrefix();
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
</div>
<select multiple="multiple" id="wpstg_select_tables_cloning">
    <?php
    foreach ($options->tables as $table) :
        $attributes = !in_array($table->name, $options->excludedTables) && (strpos($table->name, $dbPrefix) === 0) ? "selected='selected'" : "";
        if (($options->mainJob === 'updating' || $options->mainJob === 'resetting') && isset($options->currentClone['includedTables'])) {
            $attributes = !in_array($table->name, $options->excludedTables) && in_array($table->name, $options->currentClone['includedTables']) ? "selected='selected'" : "";
        }

        $attributes .= in_array($table->name, $options->clonedTables) ? "disabled" : '';
        ?>
        <option class="wpstg-db-table" value="<?php echo esc_attr($table->name) ?>" name="<?php echo esc_attr($table->name) ?>" <?php echo esc_html($attributes) ?>>
            <?php echo esc_html($table->name) ?> - <?php echo esc_html(size_format($table->size, 2)) ?>
        </option>
    <?php endforeach ?>
</select>
<div class="wpstg-mt-10px">
    <a href="#" class="wpstg-button-unselect button"> <?php esc_html_e('Unselect All', 'wp-staging'); ?> </a>
    <a href="#" class="wpstg-button-select button"> <?php echo esc_html($dbPrefix); ?> </a>
</div>
<p>
    <?php esc_html_e("You can select multiple tables. Press left mouse button & move or press STRG+Left mouse button. (Apple: ⌘+Left Mouse Button)", "wp-staging"); ?>
</p>
