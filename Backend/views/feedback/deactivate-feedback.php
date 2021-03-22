<?php
$reasons = [
            1 => '<li><label><input type="radio" name="wpstg_disable_reason" value="temporary"/>' . __('Only temporary', 'wp-staging') . '</label></li>',
        //2 => '<li><label><input type="radio" name="wpstg_disable_reason" value="stopped showing social buttons"/>' . __('I do not use it any longer ', 'wp-staging') . '</label></li>',
        3 => '<li><label><input type="radio" name="wpstg_disable_reason" value="missing feature"/>' . __('Miss a feature', 'wp-staging') . '</label></li>
		<li><input type="text" name="wpstg_disable_text[]" value="" placeholder="Please describe the feature"/></li>',
        4 => '<li><label><input type="radio" name="wpstg_disable_reason" value="technical issue"/>' . __('Technical Issue', 'wp-staging') . '</label></li>
		<li><textarea name="wpstg_disable_text[]" placeholder="' . __('Can we help? Please describe your problem', 'wp-staging') . '"></textarea></li>',
        5 => '<li><label><input type="radio" name="wpstg_disable_reason" value="other plugin"/>' . __('Switched to another plugin/staging solution', 'wp-staging') .  '</label></li>
		<li><input type="text" name="wpstg_disable_text[]" value="" placeholder="Name of the plugin"/></li>',
        6 => '<li><label><input type="radio" name="wpstg_disable_reason" value="other"/>' . __('Other reason', 'wp-staging') . '</label></li>
		<li><textarea name="wpstg_disable_text[]" placeholder="' . __('Please specify, if possible', 'wp-staging') . '"></textarea></li>',
    ];
shuffle($reasons);
?>


<div id="wpstg-feedback-overlay" style="display: none;">
    <div id="wpstg-feedback-content">
    <form action="" method="post">
        <h3><strong><?php _e('Please let us know why you are deactivating:', 'wp-staging'); ?></strong></h3>
        <ul>
                <?php
                foreach ($reasons as $reason) {
                    echo $reason;
                }
                ?>
        </ul>
        <?php if ($email) : ?>
            <input type="hidden" name="wpstg_disable_from" value="<?php echo $email; ?>"/>
        <?php endif; ?>
        <input id="wpstg-feedback-submit" class="button button-primary" type="submit" name="wpstg_disable_submit" value="<?php _e('Submit & Deactivate', 'wp-staging'); ?>"/>
        <a class="button"><?php _e('Only Deactivate', 'wp-staging'); ?></a>
        <a class="wpstg-feedback-not-deactivate" href="#"><?php _e('Don\'t deactivate', 'wp-staging'); ?></a>
    </form>
    </div>
</div>