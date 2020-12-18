<?php
/**
 * This file is currently being called only for the Free version:
 * src/Backend/views/clone/ajax/scan.php:113
 *
 * @file src/Backend/Pro/views/clone/ajax/custom-directory.php For the Pro counterpart.
 */
?>
<fieldset disabled style="opacity:0.8;border-top: 1px solid white;margin-top: 20px;">
   <p>
       <strong style="font-size: 14px;"> <?php _e( 'Copy Staging Site to Custom Directory', 'wp-staging' ); ?></strong>
       <br>
           <?php _e( 'Path must be writeable by PHP and an absolute path like <code>/www/public_html/dev</code>.', 'wp-staging' ); ?>
    </p>
    <div class="wpstg-form-group wpstg-text-field">
        <label><?php _e('Target Directory: ', 'wp-staging') ?> </label>
        <input readonly type="text" name="wpstg_clone_dir" id="wpstg_clone_dir" value="" title="wpstg_clone_dir" placeholder="<?php echo \WPStaging\Core\WPStaging::getWPpath(); ?>" autocapitalize="off">
        <span class="wpstg-code-segment">
            <code><?php echo __('Default: ', 'wp-staging') . \WPStaging\Core\WPStaging::getWPpath(); ?></code>
        </span>
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label><?php _e('Target Hostname: ') ?> </label>
        <input readonly type="text" name="wpstg_clone_hostname" id="wpstg_clone_hostname" value="" title="wpstg_clone_hostname" placeholder="<?php get_site_url(); ?>" autocapitalize="off">
        <span class="wpstg-code-segment">
            <code><?php echo __('Default: ', 'wp-staging') . get_site_url(); ?></code>
        </span>
    </div>
</fieldset>
<fieldset disabled class="wpstg-fieldset">
    <p>
        <strong class="wpstg-fs-14"> <?php _e( 'Symlink Upload Folder', 'wp-staging' ); ?></strong>
        <br/>
        <p><?php _e( 'Activating will symlink the upload folder with the production site. All images and content on the production site uploads folder will be linked to the staging site folder. This will speed up the cloning and pushing process tremendously as no images and other data is copied between both sites.', 'wp-staging' ); ?></p>
        <p><?php _e( 'Note: This feature will only work if the staging site is on the same hosting as the production site.', 'wp-staging' ); ?></p>
    </p>
    <div class="wpstg-form-group">
        <label class="wpstg-checkbox" for="wpstg_symlink_upload">
            <?php _e( 'Symlink Upload Folder:', 'wp-staging' ); ?>
            <input disabled type="checkbox" name="wpstg_symlink_upload" id="wpstg_symlink_upload" value="true" title="wpstg_symlink_upload">
        </label>
    </div>
</fieldset>

