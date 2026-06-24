<?php

/**
 * Renders the staging engine accordion section.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $setupMode
 * @var string                                    $enginePanelId
 */

$description = $setupMode === 'create'
    ? esc_html__('Choose the cloning engine used to copy your site.', 'wp-staging')
    : sprintf(esc_html__('Choose the cloning engine used for this %s.', 'wp-staging'), $setupMode);

$renderer->accordionSection([
    'badge'           => $renderer->getSelectedEngineName(),
    'badgeAttributes' => ['data-wpstg-staging-engine-summary' => true],
    'badgeClass'      => 'wpstg-create-pill wpstg-create-pill--slate wpstg-create-summary-engine-badge',
    'description'     => $description,
    'icon'            => 'beaker',
    'panelClass'      => 'wpstg-create-accordion-panel wpstg-create-engine-panel wpstg-staging-accordion-panel wpstg-collapse-panel',
    'panelId'         => $enginePanelId,
    'title'           => __('Copy method', 'wp-staging'),
], function () use ($setupMode) {
    $selectorClass = sprintf('wpstg-%s-engine-selector', $setupMode);
    require WPSTG_VIEWS_DIR . 'staging/_partials/staging-engine-selector-modal.php';
});
