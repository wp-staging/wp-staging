<?php

use WPStaging\Core\WPStaging;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backend\Modules\Jobs\Scan;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Sanitize;

/**
 * This file is currently being called for the both FREE and PRO version:
 * @see src/Backend/views/clone/ajax/scan.php:76
 *
 * @var Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 * @var stdClass $wpDefaultDirectories
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

// For new clone settings are always enabled
$proSettingsDisabled = false;
// By default symlink option is unchecked
$uploadsSymlinked = false;

/**
 * Used for overwriting the default destination directory and destination hostname via hook
 */
$directory = WPStaging::getWPpath();
$customDir = $directory;

if (is_multisite() && !SUBDOMAIN_INSTALL) {
    $hostname = network_site_url();
} else {
    $hostname = get_site_url();
}
$customHostname = $hostname;

// Apply Filters in only PRO version
if ($isPro) {
    $hostname       = apply_filters('wpstg_cloning_target_hostname', $hostname);
    $customHostname = apply_filters('wpstg_cloning_target_hostname', '');
    $directory      = apply_filters('wpstg_cloning_target_dir', $directory);
    $customDir      = apply_filters('wpstg_cloning_target_dir', '');
} else {
    // Disable pro settings when not PRO version
    $customDir           = '';
    $customHostname      = '';
    $proSettingsDisabled = true;
}

if ($isPro && !empty($options->current) && $options->current !== null) {
    $cloneDir            = isset($options->existingClones[$options->current]['cloneDir']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['cloneDir']) : '';
    $hostname            = isset($options->existingClones[$options->current]['url']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['url']) : '';
    $customHostname      = $hostname;
    $directory           = isset($options->existingClones[$options->current]['path']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['path']) : '';
    $customDir           = $directory;
    $uploadsSymlinked    = isset($options->existingClones[$options->current]['uploadsSymlinked']) && $options->existingClones[$options->current]['uploadsSymlinked'];
    $proSettingsDisabled = true;
}
?>

<p class="wpstg--advance-settings--checkbox">
  <label for="wpstg-change-dest"><?php esc_html_e('Change Destination'); ?></label>
  <input type="checkbox" id="wpstg-change-dest" name="wpstg-change-dest" value="true" class="wpstg-toggle-advance-settings-section wpstg-checkbox" data-id="wpstg-clone-directory" <?php echo $isPro === true ? '' : 'disabled' ?> >
  <span class="wpstg--tooltip">
    <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
    <span class="wpstg--tooltiptext">
      <strong> <?php esc_html_e('You can copy the staging site to a custom directory and can use a different hostname.', 'wp-staging'); ?></strong>
      <br /> <br />
      <?php echo sprintf(
          Escape::escapeHtml(__('<strong>Destination Directory:</strong> An absolute path like <code>/www/public_html/dev</code>. File permissions should be 755 and it must be writeable by php user <code>%s</code>', 'wp-staging')),
          esc_html((new SystemInfo())->getPHPUser())
      ); ?>
      <br /> <br />
      <?php echo Escape::escapeHtml(__('<strong>Target Hostname:</strong> The hostname of the destination site, for instance <code>https://subdomain.example.com</code> or <code>https://example.com/staging</code>', 'wp-staging')) ?>
      <br /> <br />
      <?php esc_html_e('Make sure the hostname points to the destination directory from above.', 'wp-staging'); ?>
    </span>
  </span>
</p>
<div id="wpstg-clone-directory" <?php echo $isPro === true ? 'style="display: none;"' : '' ?> >
  <div class="wpstg-form-group wpstg-text-field">
    <label><?php esc_html_e('Destination Directory: ', 'wp-staging') ?> </label>
    <input type="text" class="wpstg-textbox" name="wpstg_clone_dir" id="wpstg_clone_dir" value="<?php echo esc_attr($customDir); ?>" title="wpstg_clone_dir" placeholder="<?php echo esc_attr($directory); ?>" autocapitalize="off" <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?> />
    <?php if (!$proSettingsDisabled) : ?>
    <span class="wpstg-code-segment">
      <code>
        <a id="wpstg-use-target-dir" data-base-path="<?php echo esc_attr($directory) ?>" data-path="<?php echo esc_attr($directory) ?>" class="wpstg-pointer">
          <?php esc_html_e('Set Default: ', 'wp-staging') ?>
        </a>
        <span class="wpstg-use-target-dir--value"><?php echo esc_attr($directory); ?></span>
      </code>
    </span>
    <?php endif; ?>
  </div>
  <div class="wpstg-form-group wpstg-text-field">
    <label><?php esc_html_e('Target Hostname: ') ?> </label>
    <input type="text" class="wpstg-textbox" name="wpstg_clone_hostname" id="wpstg_clone_hostname" value="<?php echo esc_attr($customHostname); ?>" title="wpstg_clone_hostname" placeholder="<?php echo esc_attr($hostname); ?>" autocapitalize="off" <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?> />
    <?php if (!$proSettingsDisabled) : ?>
    <span class="wpstg-code-segment">
      <code>
        <a id="wpstg-use-target-hostname" data-base-uri="<?php echo esc_attr($hostname) ?>" data-uri="<?php echo esc_attr($hostname) ?>" class="wpstg-pointer">
          <?php esc_html_e('Set Default: ', 'wp-staging') ?>
        </a>
        <span class="wpstg-use-target-hostname--value"><?php echo esc_url(get_site_url()); ?></span>
      </code>
    </span>
    <?php endif; ?>
  </div>
  <hr/>
</div>

<p class="wpstg--advance-settings--checkbox">
    <label for="wpstg_symlink_upload"><?php esc_html_e('Symlink Uploads Folder'); ?></label>
    <input type="checkbox" class="wpstg-checkbox" id="wpstg_symlink_upload" name="wpstg_symlink_upload" value="true"
      <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?>
      <?php echo $uploadsSymlinked === true ? 'checked' : '' ?> />
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
          <?php echo sprintf(esc_html__('Activate to symlink the folder %s%s%s to the production site. %s All files including images on the production site\'s uploads folder will be linked to the staging site uploads folder. This will speed up the cloning and pushing process tremendously as no files from the uploads folder are copied between both sites. %s Note: this can lead to mixed and shared content issues if both site loads (custom) stylesheet files from the same uploads folder. %s Using this option means changing images on the staging site will change images on the production site as well. Use this with care! %s', 'wp-staging'), '<code>', esc_html($wpDefaultDirectories->getRelativeUploadPath()), '</code>', '<br><br>', '<br><br>', '<br><br><strong>', '</strong>');?>
          <br/>
          <br/>
          <strong><?php esc_html_e('This feature only works if the staging site is on the same hosting as the production site.', 'wp-staging'); ?></strong>
          <?php echo $proSettingsDisabled === true ? '<br/>' . esc_html__('(Create a new staging site if you want to change this setting.)', 'wp-staging') : '' ?>
        </span>
    </span>
</p>
