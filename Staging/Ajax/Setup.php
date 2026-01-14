<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\TableScanner;
use WPStaging\Staging\Sites;

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

        $cloneId = $this->getValidatedCloneId();
        $isReset = $this->getIsReset();
        if (empty($cloneId)) {
            $this->stagingSetup->initNewStagingSite();
        } elseif ($isReset) {
            $this->stagingSetup->initResetJob($this->getStagingSiteDtoByCloneId($cloneId));
        } else {
            $this->stagingSetup->initUpdateJob($this->getStagingSiteDtoByCloneId($cloneId));
        }

        $this->directoryScanner->setStagingSetup($this->stagingSetup);
        $this->tableScanner->setStagingSetup($this->stagingSetup);

        if ($isReset) {
            $result = $this->templateEngine->render(
                'staging/reset-setup.php',
                [
                    'stagingSetup'     => $this->stagingSetup,
                    'stagingSiteDto'   => $this->stagingSetup->getStagingSiteDto(),
                    'directoryScanner' => $this->directoryScanner,
                    'tableScanner'     => $this->tableScanner,
                ]
            );
        } else {
            $result = $this->templateEngine->render(
                'staging/setup.php',
                [
                    'stagingSetup'     => $this->stagingSetup,
                    'stagingSiteDto'   => $this->stagingSetup->getStagingSiteDto(),
                    'directoryScanner' => $this->directoryScanner,
                    'tableScanner'     => $this->tableScanner,
                ]
            );
        }

        wp_send_json_success($result);
    }

    private function getValidatedCloneId(): string
    {
        if (empty($_POST['cloneId'])) {
            return '';
        }

        $cloneId = Sanitize::sanitizeString($_POST['cloneId']);
        if ($cloneId === null || $cloneId === false) {
            return '';
        }

        return trim($cloneId);
    }

    private function getIsReset(): bool
    {
        if (empty($_POST['reset'])) {
            return false;
        }

        $reset = Sanitize::sanitizeString($_POST['reset']);
        if ($reset === null || $reset === false) {
            return false;
        }

        return $reset === 'true';
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
