<?php

namespace WPStaging\Staging\Service;

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Staging\Dto\StagingSiteDto;

/**
 * @package WPStaging\Staging\Service
 */
abstract class AbstractStagingSetup
{
    /**
     * @var StagingSiteDto
     */
    protected $stagingSiteDto;

    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var bool
     */
    protected $openDisabledSettingsSectionByDefault = true;

    /**
     * @var string
     */
    private $infoIcon;

    /**
     * @var string
     */
    private $stagingJob;

    /**
     * @var WpDefaultDirectories
     */
    private $wpDefaultDirectories;

    public function __construct(TemplateEngine $templateEngine, Assets $assets, WpDefaultDirectories $wpDefaultDirectories)
    {
        $this->templateEngine       = $templateEngine;
        $this->infoIcon             = $assets->getAssetsUrl('svg/info-outline.svg');
        $this->wpDefaultDirectories = $wpDefaultDirectories;
    }

    public function initNewStagingSite()
    {
        $this->stagingJob     = StagingSetup::JOB_NEW_STAGING_SITE;
        $this->stagingSiteDto = new StagingSiteDto();
    }

    public function isNewStagingSite(): bool
    {
        return $this->stagingJob === StagingSetup::JOB_NEW_STAGING_SITE;
    }

    public function isUpdateJob(): bool
    {
        return $this->stagingJob === StagingSetup::JOB_UPDATE;
    }

    public function isResetJob(): bool
    {
        return $this->stagingJob === StagingSetup::JOB_RESET;
    }

    public function isUpdateOrResetJob(): bool
    {
        return $this->isUpdateJob() || $this->isResetJob();
    }

    public function setStagingSiteDto(StagingSiteDto $stagingSiteDto)
    {
        $this->stagingSiteDto = $stagingSiteDto;
    }

    public function getStagingSiteDto(): StagingSiteDto
    {
        return $this->stagingSiteDto;
    }

    public function getRoot(): string
    {
        return wp_normalize_path(ABSPATH);
    }

    public function renderSettings(string $name, string $label, string $description, bool $checked = false, bool $disabled = false, string $additionalClasses = '', string $dataId = '')
    {
        $view = $this->templateEngine->render(
            'staging/_partials/settings.php',
            [
                'name'        => $name,
                'label'       => $label,
                'description' => $description,
                'checked'     => $checked,
                'disabled'    => $disabled,
                'classes'     => $additionalClasses,
                'dataId'      => $dataId,
                'infoIcon'    => $this->infoIcon,
            ]
        );

        echo $view; // phpcs:ignore
    }

    public function getSymlinkUploadDescription(): string
    {
        return sprintf(esc_html__('Activate to symlink the folder %s to the production site. %s All files including images on the production site\'s uploads folder will be linked to the staging site uploads folder. This will speed up the cloning and pushing process tremendously as no files from the uploads folder are copied between both sites. %s Warning: this can lead to mixed and shared content issues if both sites load (custom) stylesheet files from the same uploads folder. %s Using this option means changing images on the staging site will change images on the production site as well. Use this with care! %s', 'wp-staging'), '<code>' . esc_html($this->wpDefaultDirectories->getRelativeUploadPath()) . '</code>', '<br><br>', '<br><br><span class="wpstg--red">', '<br><br>', '</span>');
    }

    public function getCustomDirectoryDescription(): string
    {
        return $this->templateEngine->render(
            'staging/setup/custom-directory-desc.php',
            [
                'systemInfo' => new SystemInfo(),
            ]
        );
    }

    public function getIsOpenDisabledSettingsSectionByDefault(): bool
    {
        return $this->openDisabledSettingsSectionByDefault;
    }

    abstract public function renderNetworkCloneSettings();

    abstract public function getAdvanceSettingsTitle(): string;

    abstract public function renderAdvanceSettingsHeader();

    abstract public function renderAdvanceSettings(string $name, string $label, string $description, bool $checked = false, string $additionalClasses = '', string $dataId = '');

    abstract public function renderNewAdminSettings();

    abstract public function renderExternalDatabaseSettings();

    abstract public function renderCustomDirectorySettings();

    abstract public function renderDisableWooSchedulerSettings();

    protected function renderSettingsFields(array $fields)
    {
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'text';
            if ($type === 'checkbox') {
                $this->renderCheckbox(
                    $field['name'],
                    $field['label'],
                    $field['value'] ?? 'true',
                    $field['checked'] ?? false,
                    $field['disabled'] ?? false
                );

                continue;
            }

            $this->renderField(
                $field['name'],
                $field['label'],
                $type,
                $field['placeholder'] ?? '',
                $field['description'] ?? '',
                $field['disabled'] ?? false,
                $field['autocapitalize'] ?? true,
                $field['autocomplete'] ?? true
            );
        }
    }

    protected function renderField(string $name, string $label, string $type = 'text', string $placeholder = '', string $description = '', bool $disabled = false, bool $autocapitalize = true, bool $autocomplete = true)
    {
        $view = $this->templateEngine->render(
            'staging/_partials/settings-field.php',
            [
                'name'           => $name,
                'label'          => $label,
                'type'           => $type,
                'placeholder'    => $placeholder,
                'description'    => $description,
                'disabled'       => $disabled,
                'autocapitalize' => $autocapitalize,
                'autocomplete'   => $autocomplete,
            ]
        );

        if ($type === 'password') {
            echo '<form>' . $view . '</form>'; // phpcs:ignore
            return;
        }

        echo $view; // phpcs:ignore
    }

    protected function renderCheckbox(string $name, string $label, string $value, bool $checked = false, bool $disabled = false)
    {
        $view = $this->templateEngine->render(
            'staging/_partials/settings-checkbox.php',
            [
                'name'     => $name,
                'label'    => $label,
                'value'    => $value,
                'checked'  => $checked,
                'disabled' => $disabled,
            ]
        );

        echo $view; // phpcs:ignore
    }
}
