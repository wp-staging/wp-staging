<?php
/**
 * @var stdClass $options
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */
?>
<p>
<strong><?php _e("Select Folders to Copy", "wp-staging") ?></strong>
    <br>
<?php _e("Click on a folder name to expand it.", "wp-staging") ?>
</p>
<div id="wpstg-directories-listing" data-existing-excludes="<?php echo (($options->mainJob === 'updating' || $options->mainJob === 'resetting') && isset($options->currentClone['excludedDirectories'])) ? esc_html(implode(',', $options->currentClone['excludedDirectories'])) : '' ?>">
    <div class="wpstg-mb-8px">
        <button type="button" class="wpstg-unselect-dirs button"><?php _e('Unselect All', 'wp-staging'); ?></button>
        <button type="button" class="wpstg-select-dirs-default button"> <?php _e('Select Default', 'wp-staging'); ?></button>
    </div>
    <?php echo $scan->directoryListing() ?>
</div>
<!-- Exclusion Rules Table -->
<div class="wpstg-excluded-filters-container" id="wpstg-exclude-filters-container">
    <table>
        <tbody>
            <?php
            $hasRules = false;
            if ($options->mainJob === 'updating' || $options->mainJob === 'resetting') :
                foreach ($options->currentClone['excludeSizeRules'] as $rule) :
                    $hasRules = true;
                    echo $excludeUtils->renderSizeExclude($rule);
                endforeach;
                foreach ($options->currentClone['excludeGlobRules'] as $rule) :
                    $hasRules = true;
                    echo $excludeUtils->renderGlobExclude($rule);
                endforeach;
            endif; ?>
        </tbody>
    </table>
    <p <?php echo !$hasRules ? 'style="display: none;"' : '' ?> class="wpstg-has-exclude-rules"><b><?php _e('Note', 'wp-staging'); ?>:</b> <?php _e('These rules will not affect wp-admin and wp-includes directories!', 'wp-staging')?></p>
    <div class="wpstg-exclude-filters-foot">
        <div class="wpstg-dropdown wpstg-exclude-filter-dropdown">
            <button class="wpstg-dropdown-toggler wpstg-button--secondary wpstg-button--blue">
                <?php _e("Add Exclude Rule + ", "wp-staging"); ?>
            </button>
            <div class="wpstg-dropdown-menu wpstg-menu-dropup">
                <button class="wpstg-dropdown-action wpstg-file-size-rule"><?php _e('File Size', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-file-ext-rule"><?php _e('File Extension', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-file-name-rule"><?php _e('File Name', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-dir-name-rule"><?php _e('Folder Name', 'wp-staging'); ?></button>
            </div>
        </div>
        <button <?php echo !$hasRules ? 'style="display: none;"' : '' ?> class="wpstg-ml-8px wpstg-button--secondary wpstg-clear-all-rules wpstg-has-exclude-rules wpstg-button--red">
            <?php _e("Clear All Rules", "wp-staging"); ?>
        </button>
    </div>
</div>
<?php
if ($options->current !== null && $options->mainJob === 'updating') {
    echo '<p>' . __("Applying an exclude rule will not effect existing files on the staging site if you don't clean up the wp-content folder before updating. Existing files will not be deleted afterwards automatically!", 'wp-staging') . '</p>';
}
?>
<!-- End Exclusion Rules Table -->
        
<!-- Templates for exclusion filters. These will never be rendered until added to exclusion rule table -->
<?php unset($rule); ?>
<template id="wpstg-file-ext-exclude-filter-template">
    <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/templates/exclude-filters/file-ext-exclude-filter.php') ?>
</template>
<template id="wpstg-file-size-exclude-filter-template">
    <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/templates/exclude-filters/file-size-exclude-filter.php') ?>
</template>
<template id="wpstg-file-name-exclude-filter-template">
    <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/templates/exclude-filters/file-name-exclude-filter.php') ?>
</template>
<template id="wpstg-dir-name-exclude-filter-template">
    <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/templates/exclude-filters/dir-name-exclude-filter.php') ?>
</template>
<!-- End - Templates for exclusion filters -->

<?php if (defined('WPSTG_ALLOW_EXTRA_DIRECTORIES') && WPSTG_ALLOW_EXTRA_DIRECTORIES) { ?>
<h4 style="margin:10px 0 10px 0">
    <?php echo __("Extra directories to copy", "wp-staging") ?>
</h4>

<textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" style="width:100%;height:100px;"></textarea>
<p>
    <span>
        <?php
        echo __(
            "Enter one folder path per line.<br>" .
                "Folders must be relative to the path: " . $options->root,
            "wp-staging"
        )
        ?>
    </span>
</p>
<?php } ?>

<p>
    <span>
        <?php
        if (isset($options->clone)) {
            echo __("All files will be copied to: ", "wp-staging") . "<code>" .  $options->root . $options->clone . "</code>";
        }
        ?>
    </span>
</p>
