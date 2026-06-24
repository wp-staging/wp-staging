<?php

/**
 * Renders a setup modal review card.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $title
 * @var array                                     $rows
 */
?>
<section class="wpstg-staging-review-card">
    <h2 class="wpstg-staging-review-card__title"><?php echo esc_html($title); ?></h2>
    <dl class="wpstg-staging-review-list">
        <?php foreach ($rows as $row) : ?>
            <?php $isStacked = !empty($row['stacked']); ?>
            <div class="<?php echo $isStacked ? 'wpstg-staging-review-stack' : 'wpstg-staging-review-row'; ?>">
                <dt class="wpstg-staging-review-label"><?php echo esc_html($row['label']); ?></dt>
                <dd class="wpstg-staging-review-value <?php echo $isStacked ? 'wpstg-staging-review-value--stacked' : ''; ?>"<?php $renderer->attributes(isset($row['attributes']) ? $row['attributes'] : []); ?>><?php echo esc_html($row['value']); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>
