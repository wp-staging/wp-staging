<?php

/**
 * Checkbox component.
 * @var string $name
 * @var string $value
 * @var string|null $classes
 * @var string|null $id
 * @var string|null $dataId
 * @var string|null $dataDirType
 * @var string|null $dataPrefix
 * @var string|null $dataPath
 * @var string|null $dataDeletePath
 * @var string|null $isDataScanned
 * @var string|null $isDataNavigatable
 * @var string|null $onChange
 *
 * @package WPStaging\Framework\Component\UI
 * @see \WPStaging\Framework\Component\UI\CheckboxWrapper::render()
 */

?>
<span class="wpstg--checkbox-wrapper">
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
        <?php if (!empty($dataDirType)) : ?>
            data-dir-type='<?php echo esc_attr($dataDirType); ?>'
        <?php endif; ?>
        <?php if (!empty($dataPrefix)) : ?>
            data-prefix='<?php echo esc_attr($dataPrefix); ?>'
        <?php endif; ?>
        <?php if (!empty($dataPath)) : ?>
            data-path='<?php echo esc_attr($dataPath); ?>'
        <?php endif; ?>
        <?php if (!empty($dataDeletePath)) : ?>
            data-deletepath='<?php echo esc_attr($dataDeletePath); ?>'
        <?php endif; ?>
        <?php if (!empty($isDataScanned)) : ?>
            data-scanned='<?php echo esc_attr($isDataScanned); ?>'
        <?php endif; ?>
        <?php if (!empty($isDataNavigatable)) : ?>
            data-navigatable='<?php echo esc_attr($isDataNavigatable); ?>'
        <?php endif; ?>
        <?php if (!empty($onChange)) : ?>
            onchange='<?php echo esc_attr($onChange); ?>'
        <?php endif; ?>
        <?php echo $isDisabled ? 'disabled' : '';?>
        <?php echo $isChecked ? 'checked' : '';?>
    />
    <svg viewBox="0 0 20 16" width="20" height="20">
        <rect class="wpstg--checkbox-border" x="1" y="1" width="16" height="16" rx="3" ry="3"></rect>
        <rect class="wpstg--checkbox-background" x="1" y="1" width="16" height="16" rx="3" ry="3"></rect>
        <polyline class="wpstg--check-mark" points="4.5 10 7.5 13 13.5 5"></polyline>
    </svg>
</span>