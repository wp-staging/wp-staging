<?php
/**
 * @var string $email
 *
 * @see /Backend/Feedback/Feedback.php
 * @see loadFeedbackForm()
 */

$reasons = [
    1 => [
        'id'    => 'wpstg_disable_reason_temporary',
        'value' => 'temporary',
        'label' => esc_html__('Only temporary', 'wp-staging'),
        'input' => false,
        'placeholder' => ''
    ],
    2 => [
        'id'    => 'wpstg_disable_reason_missing',
        'value' => 'missing_feature',
        'label' => esc_html__('Missing a feature', 'wp-staging'),
        'input' => 'input',
        'placeholder' => esc_html__('Please describe the feature', 'wp-staging')
    ],
    3 => [
        'id'    => 'wpstg_disable_reason_technical',
        'value' => 'technical_issue',
        'label' => esc_html__('Technical Issue', 'wp-staging'),
        'input' => 'textarea',
        'placeholder' => esc_html__('Can we help? Please describe your problem', 'wp-staging')
    ],
    4 => [
        'id'    => 'wpstg_disable_reason_plugin',
        'value' => 'other_plugin',
        'label' => esc_html__('Switched to another plugin/staging solution', 'wp-staging'),
        'input' => 'input',
        'placeholder' => esc_html__('Name of the plugin', 'wp-staging')
    ],
    5 => [
        'id'    => 'wpstg_disable_reason_other',
        'value' => 'other_reason',
        'label' => esc_html__('Other reason', 'wp-staging'),
        'input' => 'textarea',
        'placeholder' => esc_html__('Please specify, if possible', 'wp-staging')
    ],
];

// Any reason for this shuffling?
shuffle($reasons);
?>

<div id="wpstg-feedback-overlay" style="display: none;">
    <div id="wpstg-feedback-content">
        <form action="" method="post">
            <h3><strong><?php esc_html_e('Please let us know why you are deactivating:', 'wp-staging'); ?></strong></h3>
            <ul>
                <?php foreach ($reasons as $reason) : ?>
                    <li>
                        <label id="<?php echo esc_attr($reason['id']) ?>">
                            <input type="checkbox" name="wpstg_disable_reason[]" value="<?php echo esc_attr($reason['value']) ?>" />
                            <?php echo esc_html($reason['label']) ?>
                        </label>
                    </li>
                    <?php if ($reason['input'] === 'input') : ?>
                        <li>
                            <input type="text" name="wpstg_disable_text[<?php echo esc_attr($reason['value']) ?>]" value="" placeholder="<?php echo esc_attr($reason['placeholder']) ?>"/>
                        </li>
                    <?php elseif ($reason['input'] === 'textarea') : ?>
                        <li>
                            <textarea name="wpstg_disable_text[<?php echo esc_attr($reason['value']) ?>]" placeholder="<?php echo esc_attr($reason['placeholder']) ?>"></textarea>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php if ($email) : ?>
                <input type="hidden" name="wpstg_disable_from" value="<?php echo esc_html($email); ?>"/>
            <?php endif; ?>
            <button id="wpstg-feedback-submit" class="button button-primary" type="submit" name="wpstg_disable_submit">
                <?php esc_html_e('Submit & Deactivate', 'wp-staging'); ?>
            </button>

            <a class="button"><?php esc_html_e('Only Deactivate', 'wp-staging'); ?></a>
            <a class="wpstg-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'wp-staging'); ?></a>
        </form>
    </div>
</div>