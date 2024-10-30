<?php

/**
 * This file is currently being called for both FREE and PRO version:
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var bool                                 $isPro
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

use WPStaging\Framework\Facades\UI\Checkbox;

$isDisabled = false;
if (!$isPro) {
    $isDisabled = true;
}

?>

<?php if (!$isPro) { // Show this only on FREE version ?>
    <p class="wpstg-dark-alert"><?php esc_html_e('Options below are pro features! ', 'wp-staging'); ?>
        <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=new-admin-user&utm_term=new-admin-user" target="_blank" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet"><?php esc_html_e("Try out WP Staging Pro", "wp-staging"); ?></a>
    </p>
<?php } ?>

<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg-new-admin-user"><?php esc_html_e('New Admin Account', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg-new-admin-user', 'wpstg-new-admin-user', '', $checked = false, ['classes' => 'wpstg-toggle-advance-settings-section', 'isDisabled' => !$isPro], ['id' => 'wpstg-new-admin-user-section']); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_attr($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php echo esc_html__('Create a new admin user account for this staging site!', 'wp-staging'); ?>
            <div class="wpstg--red wpstg-mt-10px">
                <?php echo esc_html__('If the account already exists, the password will be updated.', 'wp-staging'); ?>
            </div>
        </span>
    </span>
</div>
<div id="wpstg-new-admin-user-section" <?php echo $isPro ? 'style="display: none;"' : '' ?>>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg-new-admin-email"><?php esc_html_e('Email: ', 'wp-staging'); ?> </label>
        <input type="email" class="wpstg-textbox" name="wpstg-new-admin-email" id="wpstg-new-admin-email" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> />
    </div>
    <form>
        <div class="wpstg-form-group wpstg-text-field">
            <label for="wpstg-new-admin-password"><?php esc_html_e('Password: ', 'wp-staging'); ?></label>
            <input type="password" class="wpstg-textbox" name="wpstg-new-admin-password" id="wpstg-new-admin-password" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> autocomplete="off" />
        </div>
    </form>
    <hr />
</div>
