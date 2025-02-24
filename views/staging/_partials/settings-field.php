<?php

/**
 * @var string $name
 * @var string $label
 * @var string $type
 * @var string $placeholder
 * @var string $description
 * @var bool   $disabled
 * @var bool   $autocapitalize
 * @var bool   $autocomplete
 */

?>

<div class="wpstg-form-group wpstg-text-field">
    <label for="<?php echo esc_attr($name) ?>"><?php echo esc_html($label); ?></label>
    <input
        id="<?php echo esc_attr($name) ?>"
        name="<?php echo esc_attr($name) ?>"
        type="<?php echo esc_attr($type) ?>"
        placeholder="<?php echo esc_attr($placeholder) ?>"
        class="wpstg-textbox"
        <?php echo $disabled ? 'disabled' : '' ?>
        <?php echo $autocapitalize ? '' : 'autocapitalize="off"' ?>
        <?php echo $autocomplete ? '' : 'autocomplete="off"' ?>
    />
    <?php echo $description; // phpcs:ignore  ?>
</div>
