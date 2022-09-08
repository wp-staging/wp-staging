<?php
$reasons = [
            1 => '<li><label><input type="radio" name="wpstg_disable_reason" value="temporary"/>' . esc_html__('Only temporary', 'wp-staging') . '</label></li>',
        //2 => '<li><label><input type="radio" name="wpstg_disable_reason" value="stopped showing social buttons"/>' . __('I do not use it any longer ', 'wp-staging') . '</label></li>',
        3 => '<li><label><input type="radio" name="wpstg_disable_reason" value="missing feature"/>' . esc_html__('Miss a feature', 'wp-staging') . '</label></li>
		<li><input type="text" name="wpstg_disable_text[]" value="" placeholder="Please describe the feature"/></li>',
        4 => '<li><label><input type="radio" name="wpstg_disable_reason" value="technical issue"/>' . esc_html__('Technical Issue', 'wp-staging') . '</label></li>
		<li><textarea name="wpstg_disable_text[]" placeholder="' . esc_html__('Can we help? Please describe your problem', 'wp-staging') . '"></textarea></li>',
        5 => '<li><label><input type="radio" name="wpstg_disable_reason" value="other plugin"/>' . esc_html__('Switched to another plugin/staging solution', 'wp-staging') .  '</label></li>
		<li><input type="text" name="wpstg_disable_text[]" value="" placeholder="Name of the plugin"/></li>',
        6 => '<li><label><input type="radio" name="wpstg_disable_reason" value="other"/>' . esc_html__('Other reason', 'wp-staging') . '</label></li>
		<li><textarea name="wpstg_disable_text[]" placeholder="' . esc_html__('Please specify, if possible', 'wp-staging') . '"></textarea></li>',
    ];
shuffle($reasons);
?>


<div id="wpstg-feedback-overlay" style="display: none;">
    <div id="wpstg-feedback-content">
    <form action="" method="post">
        <h3><strong><?php esc_html_e('Please let us know why you are deactivating:', 'wp-staging'); ?></strong></h3>
        <ul>
                <?php
                foreach ($reasons as $reason) {
                    echo $reason; //phpcs:ignore
                }
                ?>
        </ul>
        <?php if ($email) : ?>
            <input type="hidden" name="wpstg_disable_from" value="<?php echo esc_attr($email); ?>"/>
        <?php endif; ?>
        <input id="wpstg-feedback-submit" class="button button-primary" type="submit" name="wpstg_disable_submit" value="<?php esc_html_e('Submit & Deactivate', 'wp-staging'); ?>"/>
        <a class="button"><?php esc_html_e('Only Deactivate', 'wp-staging'); ?></a>
        <a class="wpstg-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'wp-staging'); ?></a>
    </form>
    </div>
</div>