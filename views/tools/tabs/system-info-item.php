<?php
/** @var string $itemLabel */
/** @var mixed  $itemValue */
?>
<div class="wpstg-system-info-item">
    <div class="wpstg-system-info-label"><?php echo esc_html($itemLabel); ?></div>
    <div class="wpstg-system-info-value">
        <?php $title = is_array($itemValue) ? '' : $itemValue; ?>
        <span class="wpstg-system-info-badge" alt="<?php echo esc_attr($title); ?>">
            <?php if (is_array($itemValue)) : ?>
                <details>
                    <summary><?php esc_html_e('View data', 'wp-staging'); ?></summary>
                    <pre class="wpstg-system-info-serialized-data"><?php echo esc_html(print_r($itemValue, true)); ?></pre>
                </details>
            <?php else : ?>
                <?php echo esc_html($itemValue); ?>
            <?php endif; ?>
        </span>
    </div>
</div>
