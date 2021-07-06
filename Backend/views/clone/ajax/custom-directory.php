<?php

use WPStaging\Core\WPStaging;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backend\Modules\Jobs\Scan;

/**
 * This file is currently being called for the both FREE and PRO version:
 * src/Backend/views/clone/ajax/scan.php:63
 *
 * @var Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

// For new clone settings are always enabled
$proSettingsDisabled = false;
// By default symlink option is unchecked
$uploadsSymlinked = false;

/**
 * Used for overwriting the default target directory and target hostname via hook
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
    $hostname = apply_filters('wpstg_cloning_target_hostname', $hostname);
    $customHostname = apply_filters('wpstg_cloning_target_hostname', '');
    $directory = apply_filters('wpstg_cloning_target_dir', $directory);
    $customDir = apply_filters('wpstg_cloning_target_dir', '');
} else {
    // Disable pro settings when not PRO version
    $customDir           = '';
    $customHostname      = '';
    $proSettingsDisabled = true;
}

if ($isPro && !empty($options->current) && $options->current !== null) {
    $cloneDir         = isset($options->existingClones[$options->current]['cloneDir']) ? $options->existingClones[$options->current]['cloneDir'] : '';
    $hostname         = isset($options->existingClones[$options->current]['url']) ? $options->existingClones[$options->current]['url'] : '';
    $customHostname   = $hostname;
    $directory        = isset($options->existingClones[$options->current]['path']) ? $options->existingClones[$options->current]['path'] : '';
    $customDir        = $directory;
    $uploadsSymlinked = isset($options->existingClones[$options->current]['uploadsSymlinked']) && $options->existingClones[$options->current]['uploadsSymlinked'];
    $proSettingsDisabled = true;
} ?>

<p class="wpstg--advance-settings--checkbox">
  <label for="wpstg-change-dest"><?php _e('Change Destination'); ?></label>
  <input type="checkbox" id="wpstg-change-dest" name="wpstg-change-dest" value="true" class="wpstg-toggle-advance-settings-section" data-id="wpstg-clone-directory" <?php echo $isPro === true ? '' : 'disabled' ?> >
  <span class="wpstg--tooltip">
    <img class="wpstg--dashicons" src="<?php echo $scan->getInfoIcon(); ?>" alt="info" />
    <span class="wpstg--tooltiptext">
      <strong> <?php _e('You can copy the staging site to a custom directory and can use a different hostname.', 'wp-staging'); ?></strong>
      <br /> <br />
      <?php echo sprintf(__('<strong>Target Directory:</strong> An absolute path like <code>/www/public_html/dev</code>. File permissions should be 755 and it must be writeable by php user <code>%s</code>', 'wp-staging'), (new SystemInfo())->getPHPUser()); ?>
      <br /> <br />
      <?php _e('<strong>Taget Hostname:</strong> The hostname of the target site, for instance <code>https://subdomain.example.com</code> or <code>https://example.com/staging</code>', 'wp-staging'); ?>
      <br /> <br />
      <?php _e('Make sure the hostname points to the target directory from above.', 'wp-staging'); ?>
    </span>
  </span>
</p>
<div id="wpstg-clone-directory" <?php echo $isPro === true ? 'style="display: none;"' : '' ?> >
  <div class="wpstg-form-group wpstg-text-field">
    <label><?php _e('Target Directory: ', 'wp-staging') ?> </label>
    <input type="text" class="wpstg-textbox" name="wpstg_clone_dir" id="wpstg_clone_dir" value="<?php echo $customDir; ?>" title="wpstg_clone_dir" placeholder="<?php echo $directory; ?>" autocapitalize="off" <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?> />
    <?php if (!$proSettingsDisabled) : ?>
    <span class="wpstg-code-segment">
      <code>
        <a id="wpstg-use-target-dir" data-base-path="<?php echo $directory ?>" data-path="<?php echo $directory ?>" class="wpstg-pointer">
          <?php _e('Set Default: ', 'wp-staging') ?>
        </a>
        <span class="wpstg-use-target-dir--value"><?php echo $directory; ?></span>
      </code>
    </span>
    <?php endif; ?>
  </div>
  <div class="wpstg-form-group wpstg-text-field">
    <label><?php _e('Target Hostname: ') ?> </label>
    <input type="text" class="wpstg-textbox" name="wpstg_clone_hostname" id="wpstg_clone_hostname" value="<?php echo $customHostname; ?>" title="wpstg_clone_hostname" placeholder="<?php echo $hostname; ?>" autocapitalize="off" <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?> />
    <?php if (!$proSettingsDisabled) : ?>
    <span class="wpstg-code-segment">
      <code>
        <a id="wpstg-use-target-hostname" data-base-uri="<?php echo $hostname ?>" data-uri="<?php echo $hostname ?>" class="wpstg-pointer">
          <?php _e('Set Default: ', 'wp-staging') ?>
        </a>
        <span class="wpstg-use-target-hostname--value"><?php echo get_site_url(); ?></span>
      </code>
    </span>
    <?php endif; ?>
  </div>
  <hr/>
</div>

<p class="wpstg--advance-settings--checkbox">
    <label for="wpstg_symlink_upload"><?php _e('Symlink Uploads Folder'); ?></label>
    <input type="checkbox" id="wpstg_symlink_upload" name="wpstg_symlink_upload" value="true"
      <?php echo $proSettingsDisabled === true ? 'disabled' : '' ?>
      <?php echo $uploadsSymlinked === true ? 'checked' : '' ?> />
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo $scan->getInfoIcon(); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
          <?php _e('Activate to symlink the folder <code>wp-content/uploads</code> to the production site. All images on the production site\'s uploads folder will be linked to the staging site uploads folder. This will speed up the cloning and pushing process tremendously as no images and other data is copied between both sites.', 'wp-staging'); ?>
          <br/>
          <br/>
          <?php _e('<strong>This feature only works if the staging site is on the same hosting as the production site.</strong>', 'wp-staging'); ?>
          <?php echo $proSettingsDisabled === true ? '<br/>' . __('(Create a new staging site if you want to change this setting.)', 'wp-staging') : '' ?>
        </span>
    </span>
</p>
