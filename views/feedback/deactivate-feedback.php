<?php

/**
 * @var string $email
 *
 * @see /Basic/Feedback/Feedback.php
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

<div id="wpstg-feedback-overlay" class="wpstg-feedback-form-overlay" style="display:none;">
    <div id="wpstg-feedback-content" class="wpstg-feedback-modal-popup wpstg-deactivate-modal">
        <div class="wpstg-modal-content">
            <div class="wpstg-modal-header">
                <div class="wpstg-modal-header-heading-container">
                    <h1 class="wpstg-modal-header-heading">
                        <?php esc_html_e('Thanks for using WP Staging. Please let us know why you are deactivating.', 'wp-staging'); ?>
                    </h1>
                </div>
                <button type="button" class="wpstg-close-feedback-form">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
                    </svg>
                </button>
            </div>
            <form action="" method="post" class="wpstg-deactivate-feedback-reasons">
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
                                <label>
                                    <input type="text" name="wpstg_disable_text[<?php echo esc_attr($reason['value']) ?>]" value="" placeholder="<?php echo esc_attr($reason['placeholder']) ?>"/>
                                </label>
                            </li>
                        <?php elseif ($reason['input'] === 'textarea') : ?>
                            <li>
                                <label>
                                    <textarea name="wpstg_disable_text[<?php echo esc_attr($reason['value']) ?>]" placeholder="<?php echo esc_attr($reason['placeholder']) ?>"></textarea>
                                </label>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php if ($email) : ?>
                    <input type="hidden" name="wpstg_disable_from" value="<?php echo esc_html($email); ?>"/>
                <?php endif; ?>
            </form>
            <div>
                <div class="wpstg-feedback-form-actions">
                    <button id="wpstg-feedback-submit" type="button" class="wpstg-submit-feedback-button">
                        <?php esc_html_e('Submit & Deactivate', 'wp-staging'); ?>
                    </button>
                    <button id="wpstg-skip-and-deactivate" type="button" class="wpstg-skip-and-deactivate"><?php esc_html_e('Skip & Deactivate', 'wp-staging'); ?></button>
                </div>
                <footer class="wpstg-feedback-form-footer">
                    <div>
                        <?php
                        echo sprintf(
                            esc_html__("Our %s", "wp-staging"),
                            "<a href='https://wp-staging.com/privacy-policy/' target='_blank'>" . esc_html__("privacy policy", "wp-staging") . "</a>"
                        );
                        ?>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</div>
