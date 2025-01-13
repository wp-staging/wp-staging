<?php

use WPStaging\Backend\Modules\Jobs\Scan;
use WPStaging\Framework\Facades\Info;
use WPStaging\Framework\Facades\UI\Checkbox;

/**
 * This file is currently being called for the both FREE and PRO version:
 * @see src/views/clone/ajax/scan.php:89
 *
 * @var Scan $scan
 * @var stdClass $options
 * @var bool $isPro
 * @var object $wpDefaultDirectories
 *
 * @see Scan::start For details on $options.
 */

// By default symlink option is unchecked
$uploadsSymlinked = false;

if ($isPro && !empty($options->current)) {
    $uploadsSymlinked = isset($options->existingClones[$options->current]['uploadsSymlinked']) && $options->existingClones[$options->current]['uploadsSymlinked'];
}

$disableUploadsSymlink = false;
if (Info::canUse('symlink') === false || !$isPro) {
    $disableUploadsSymlink = true;
}
?>

<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_symlink_upload"><?php esc_html_e('Symlink Uploads Folder', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_symlink_upload', 'wpstg_symlink_upload', 'true', $uploadsSymlinked, ['isDisabled' => $disableUploadsSymlink]); ?>
    <span class="wpstg--tooltip wpstg-tooltip-icon">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info"/>
        <span class="wpstg--tooltiptext">
            <?php if ($disableUploadsSymlink && $isPro) : ?>
                <span class="wpstg--red">
                    <?php echo sprintf(esc_html__('Symlink Uploads Folder is disabled by default because %s is either unavailable(does\'nt exists) or restricted by your hosting provider.', 'wp-staging'), '<code>symlink</code>'); ?>
                </span>
            <br/>
            <br/>
            <?php else : ?>
                <?php echo sprintf(esc_html__('Activate to symlink the folder %s%s%s to the production site. %s All files including images on the production site\'s uploads folder will be linked to the staging site uploads folder. This will speed up the cloning and pushing process tremendously as no files from the uploads folder are copied between both sites. %s Warning: this can lead to mixed and shared content issues if both sites load (custom) stylesheet files from the same uploads folder. %s Using this option means changing images on the staging site will change images on the production site as well. Use this with care! %s', 'wp-staging'), '<code>', esc_html($wpDefaultDirectories->getRelativeUploadPath()), '</code>', '<br><br>', '<br><br><span class="wpstg--red">', '<br><br>', '</span>'); ?>
                <br/>
                <br/>
                <span class="wpstg--red"><?php esc_html_e('This feature only works if the staging site is on the same domain as the production site.', 'wp-staging'); ?></span>
            <?php endif; ?>
        </span>
    </span>
</div>
