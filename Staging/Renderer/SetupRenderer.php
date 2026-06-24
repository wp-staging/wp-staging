<?php

namespace WPStaging\Staging\Renderer;

use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * Renders shared setup modal UI fragments.
 */
class SetupRenderer
{
    /**
     * @var string
     */
    private $selectedEngineName = '';

    public function setSelectedEngineName(string $selectedEngineName = ''): self
    {
        $this->selectedEngineName = !empty($selectedEngineName) ? $selectedEngineName : esc_html__('Classic', 'wp-staging');

        return $this;
    }

    public function getSelectedEngineName(): string
    {
        return !empty($this->selectedEngineName) ? $this->selectedEngineName : esc_html__('Classic', 'wp-staging');
    }

    public function icon(string $name, string $class = 'wpstg-h-5 wpstg-w-5', float $strokeWidth = 1.75)
    {
        $this->renderPartial('icon', compact('name', 'class', 'strokeWidth'));
    }

    public function attributes(array $attributes)
    {
        $this->renderPartial('attributes', compact('attributes'));
    }

    public function closeButton(string $class)
    {
        $this->renderPartial('close-button', compact('class'));
    }

    public function stepBadge(string $label)
    {
        $this->renderPartial('step-badge', compact('label'));
    }

    public function modalHeader(string $title, string $description = '', string $titleId = '', string $stepLabel = '', string $extraClass = '', string $afterDescription = '', string $icon = '')
    {
        $this->renderPartial('modal-header', compact('title', 'description', 'titleId', 'stepLabel', 'extraClass', 'afterDescription', 'icon'));
    }

    public function footerButton(string $label, string $class, string $variant = 'secondary', string $icon = '', string $iconPosition = 'start', array $attributes = [])
    {
        $this->renderPartial('footer-button', compact('label', 'class', 'variant', 'icon', 'iconPosition', 'attributes'));
    }

    public function modalFooter(string $status, callable $buttons, string $extraClass = '', string $statusWarning = '')
    {
        $this->renderPartial('modal-footer', compact('status', 'buttons', 'extraClass', 'statusWarning'));
    }

    public function reviewCard(string $title, array $rows)
    {
        $this->renderPartial('review-card', compact('title', 'rows'));
    }

    public function accordionSection(array $args, callable $content)
    {
        $this->renderPartial('accordion-section', compact('args', 'content'));
    }

    public function setupOptionCard(string $name, string $label, string $description, bool $checked = false, array $checkboxOptions = [], string $tooltip = '', bool $proLocked = false)
    {
        $this->renderPartial('setup-option-card', compact('name', 'label', 'description', 'checked', 'checkboxOptions', 'tooltip', 'proLocked'));
    }

    public function proControlRow(string $name, bool $checked, string $label, string $description, string $statusLabel = '', string $badgeLabel = '')
    {
        $this->renderPartial('pro-control-row', compact('name', 'checked', 'label', 'description', 'statusLabel', 'badgeLabel'));
    }

    public function setupCopyCard(string $checkboxId, string $checkboxClass, string $title, callable $summary, string $buttonClass, string $buttonLabel, string $buttonIcon, $details = null, array $checkboxOptions = [], $afterContent = null)
    {
        $this->renderPartial('setup-copy-card', compact('checkboxId', 'checkboxClass', 'title', 'summary', 'buttonClass', 'buttonLabel', 'buttonIcon', 'details', 'checkboxOptions', 'afterContent'));
    }

    public function readyCard(
        string $title = '',
        string $description = '',
        string $databaseDescription = '',
        string $filesDescription = '',
        bool $showRuntimeBehavior = true,
        string $runtimeDescription = '',
        string $cardClass = '',
        string $runtimeTitle = '',
        string $engineSuffix = '',
        string $runtimeIcon = 'shield',
        array $customizeLinks = [],
        bool $runtimeLocked = false
    ) {
        $this->renderPartial('ready-card', compact(
            'title',
            'description',
            'databaseDescription',
            'filesDescription',
            'showRuntimeBehavior',
            'runtimeDescription',
            'cardClass',
            'runtimeTitle',
            'engineSuffix',
            'runtimeIcon',
            'customizeLinks',
            'runtimeLocked'
        ));
    }

    public function engineSection(string $setupMode, string $enginePanelId)
    {
        $this->renderPartial('engine-section', compact('setupMode', 'enginePanelId'));
    }

    public function runtimeSection(bool $isCreate, bool $isUpdate, bool $isProLicenseActive, bool $showWooSchedulerSettings, StagingSiteDto $stagingSiteDto, array $runtimeSummaryTooltips)
    {
        $this->renderPartial('runtime-section', compact('isCreate', 'isUpdate', 'isProLicenseActive', 'showWooSchedulerSettings', 'stagingSiteDto', 'runtimeSummaryTooltips'));
    }

    public function destinationSection(bool $isProLicenseActive, bool $isProBuild, bool $isCreate, AbstractStagingSetup $stagingSetup, string $defaultPathBase, string $defaultSiteName, string $productionSiteUrl)
    {
        $this->renderPartial('destination-section', compact('isProLicenseActive', 'isProBuild', 'isCreate', 'stagingSetup', 'defaultPathBase', 'defaultSiteName', 'productionSiteUrl'));
    }

    public function runtimeSummaryValue(string $key, array $tooltips, bool $isProLicenseActive)
    {
        $this->renderPartial('runtime-summary-value', compact('key', 'tooltips', 'isProLicenseActive'));
    }

    public function hasWooSchedulerSettings(AbstractStagingSetup $stagingSetup): bool
    {
        return strpos($this->captureCallable([$stagingSetup, 'renderEnableWooSchedulerSettings']), 'wpstg_woo_scheduler_enabled') !== false;
    }

    public function configurationBody(array $context)
    {
        $this->renderPartial('configuration-body', $context);
    }

    public function createSetupFooter(string $previewSiteUrl)
    {
        $this->renderPartial('create-setup-footer', compact('previewSiteUrl'));
    }

    private function captureCallable(callable $callback): string
    {
        ob_start();
        $callback();

        return (string)ob_get_clean();
    }

    private function renderPartial(string $partial, array $data = [])
    {
        $renderer = $this;
        extract($data, EXTR_SKIP);

        require WPSTG_VIEWS_DIR . 'staging/_partials/setup-modal/' . $partial . '.php';
    }
}
