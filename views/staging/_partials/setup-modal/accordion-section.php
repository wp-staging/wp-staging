<?php

/**
 * Renders a setup modal accordion section.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var array                                     $args
 * @var callable                                  $content
 */

$args = array_merge([
    'badge'           => '',
    'badgeAttributes' => [],
    'badgeClass'      => 'wpstg-create-pill wpstg-create-pill--slate',
    'badgeIcon'       => '',
    'cardClass'       => 'wpstg-create-accordion-card wpstg-staging-accordion-card',
    'chevronClass'    => 'wpstg-create-accordion-chevron wpstg-staging-accordion-chevron',
    'headerClass'     => 'wpstg-tab-header wpstg-create-accordion-header wpstg-staging-accordion-header',
    'icon'            => '',
    'iconClass'       => 'wpstg-create-accordion-icon wpstg-staging-accordion-icon',
    'isDisabled'      => false,
    'panelClass'      => 'wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel',
], $args);

if (!empty($args['isDisabled'])) {
    $args['cardClass']   .= ' wpstg-create-accordion-card--disabled';
    $args['headerClass'] .= ' wpstg-create-accordion-header--disabled';
}

$headerAttributes = [
    'aria-controls'  => $args['panelId'],
    'aria-expanded'  => 'false',
    'data-collapsed' => 'true',
    'data-id'        => '#' . $args['panelId'],
    'href'           => '#',
    'role'           => 'button',
];

if (!empty($args['isDisabled'])) {
    $headerAttributes['aria-disabled']       = 'true';
    $headerAttributes['data-wpstg-disabled'] = 'true';
    $headerAttributes['tabindex']            = '-1';
}
?>
<div class="<?php echo esc_attr($args['cardClass']); ?>">
    <a class="<?php echo esc_attr($args['headerClass']); ?>"<?php $renderer->attributes($headerAttributes); ?>>
        <span class="<?php echo esc_attr($args['iconClass']); ?>" aria-hidden="true">
            <?php $renderer->icon($args['icon']); ?>
        </span>
        <span class="wpstg-min-w-0 wpstg-flex-1">
            <span class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                <strong><?php echo esc_html($args['title']); ?></strong>
                <?php if (!empty($args['badge'])) : ?>
                    <span class="<?php echo esc_attr($args['badgeClass']); ?>"<?php $renderer->attributes($args['badgeAttributes']); ?>>
                        <?php if (!empty($args['badgeIcon'])) : ?>
                            <?php $renderer->icon($args['badgeIcon'], 'wpstg-h-3 wpstg-w-3 wpstg-mr-1'); ?>
                        <?php endif; ?>
                        <?php echo esc_html($args['badge']); ?>
                    </span>
                <?php endif; ?>
            </span>
            <span class="wpstg-create-accordion-description"><?php echo esc_html($args['description']); ?></span>
        </span>
        <span class="<?php echo esc_attr($args['chevronClass']); ?>" aria-hidden="true">
            <?php $renderer->icon('chevron', 'wpstg-h-4 wpstg-w-4'); ?>
        </span>
    </a>
    <div class="<?php echo esc_attr($args['panelClass']); ?>" id="<?php echo esc_attr($args['panelId']); ?>" style="display: none;" aria-hidden="true">
        <?php $content(); ?>
    </div>
</div>
