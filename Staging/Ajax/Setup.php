<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\LegacyOptionsCache;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\TableScanner;
use WPStaging\Staging\Sites;

/**
 * Renders the unified staging setup UI for create, update, and reset jobs.
 */
class Setup extends AbstractTemplateComponent
{
    /**
     * @var AbstractStagingSetup
     */
    private $stagingSetup;

    /**
     * @var DirectoryScanner
     */
    private $directoryScanner;

    /**
     * @var TableScanner
     */
    private $tableScanner;

    /**
     * @var ProcessLock
     */
    private $processLock;

    public function __construct(TemplateEngine $templateEngine, AbstractStagingSetup $stagingSetup, DirectoryScanner $directoryScanner, TableScanner $tableScanner, ProcessLock $processLock)
    {
        parent::__construct($templateEngine);
        $this->stagingSetup     = $stagingSetup;
        $this->processLock      = $processLock;
        $this->directoryScanner = $directoryScanner;
        $this->tableScanner     = $tableScanner;
    }

    /**
     * @return void
     */
    public function ajaxSetup()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        $cloneId       = $this->getValidatedCloneId();
        $isReset       = $this->getIsReset();
        $isCreateModal = $this->getIsCreateModal();
        $isUpdateModal = $this->getIsUpdateModal();
        if (empty($cloneId)) {
            $this->stagingSetup->initNewStagingSite();
            $this->prepareNewStagingSiteDto();
        } elseif ($isReset) {
            $this->stagingSetup->initResetJob($this->getStagingSiteDtoByCloneId($cloneId));
        } else {
            $this->stagingSetup->initUpdateJob($this->getStagingSiteDtoByCloneId($cloneId));
        }

        $this->directoryScanner->setStagingSetup($this->stagingSetup);
        $this->tableScanner->setStagingSetup($this->stagingSetup);
        $this->maybePrepareLegacyOptionsCache($cloneId, $isReset);

        $templateData = [
            'stagingSetup'     => $this->stagingSetup,
            'stagingSiteDto'   => $this->stagingSetup->getStagingSiteDto(),
            'directoryScanner' => $this->directoryScanner,
            'tableScanner'     => $this->tableScanner,
        ];

        $modalSetupMode = '';
        if ($isReset) {
            $modalSetupMode = 'reset';
        } elseif ($isUpdateModal && !empty($cloneId)) {
            $modalSetupMode = 'update';
            $this->directoryScanner->setShowFileDestination(false);
        } elseif ($isCreateModal && empty($cloneId)) {
            $modalSetupMode = 'create';
        }

        if ($modalSetupMode !== '') {
            $templateData['setupMode'] = $modalSetupMode;
        }

        $result = $this->templateEngine->render('staging/setup.php', $templateData);

        wp_send_json_success($result);
    }

    private function maybePrepareLegacyOptionsCache(string $cloneId, bool $isReset)
    {
        WPStaging::make(LegacyOptionsCache::class)->prepare($this->getLegacyMainJob($cloneId, $isReset), $cloneId);
    }

    private function prepareNewStagingSiteDto()
    {
        $cloneId        = (string)time();
        $stagingSiteDto = $this->stagingSetup->getStagingSiteDto();
        $siteName       = WPStaging::make(Sites::class)->generateStagingSiteName($cloneId);

        $stagingSiteDto->setCloneId($cloneId);
        $stagingSiteDto->setCloneName($siteName);
        $stagingSiteDto->setDirectoryName($siteName);
    }

    private function getLegacyMainJob(string $cloneId, bool $isReset): string
    {
        if (empty($cloneId)) {
            return Job::STAGING;
        }

        return $isReset ? Job::RESET : Job::UPDATE;
    }

    private function getValidatedCloneId(): string
    {
        if (empty($_POST['cloneId'])) {
            return '';
        }

        return Sanitize::sanitizeString($_POST['cloneId']);
    }

    private function getIsReset(): bool
    {
        if (empty($_POST['reset'])) {
            return false;
        }

        return Sanitize::sanitizeString($_POST['reset']) === 'true';
    }

    private function getIsCreateModal(): bool
    {
        if (empty($_POST['createModal'])) {
            return false;
        }

        return Sanitize::sanitizeString($_POST['createModal']) === 'true';
    }

    private function getIsUpdateModal(): bool
    {
        if (empty($_POST['updateModal'])) {
            return false;
        }

        return Sanitize::sanitizeString($_POST['updateModal']) === 'true';
    }

    /**
     * @param string $cloneId
     * @return StagingSiteDto
     * @throws \Exception
     */
    private function getStagingSiteDtoByCloneId(string $cloneId): StagingSiteDto
    {
        /**
         * Lazy loading and it is not needed everywhere.
         * @var Sites $stagingSitesService
         */
        $stagingSitesService = WPStaging::make(Sites::class);

        return $stagingSitesService->getStagingSiteDtoByCloneId($cloneId);
    }
}
