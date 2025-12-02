<?php

/**
 * Toggle component.
 * @var string|null $id
 * @var string $name
 * @var string $value
 * @var bool $isChecked
 * @var bool $isDisabled
 * @var string|null $classes
 * @var string|null $dataId
 * @var string|null $onChange
 *
 * @package WPStaging\Component
 * @see \WPStaging\Component\Toggle::render()
 */

?>
<span class="wpstg--toggle-wrapper">
    <input type="checkbox"
           name='<?php echo empty($name) ? '' : esc_attr($name); ?>'
           value='<?php echo empty($value) ? '' : esc_attr($value); ?>'
        <?php if (!empty($classes)) : ?>
            class='<?php echo esc_attr($classes); ?>'
        <?php endif; ?>
        <?php if (!empty($id)) : ?>
            id='<?php echo esc_attr($id); ?>'
        <?php endif; ?>
        <?php if (!empty($dataId)) : ?>
            data-id='<?php echo esc_attr($dataId); ?>'
        <?php endif; ?>
        <?php if (!empty($onChange)) : ?>
            onchange='<?php echo esc_attr($onChange); ?>'
        <?php endif; ?>
        <?php echo $isDisabled ? 'disabled' : '';?>
        <?php echo $isChecked ? 'checked' : '';?>
    />
        <span class="wpstg--toggle-slider"></span>
</span>
