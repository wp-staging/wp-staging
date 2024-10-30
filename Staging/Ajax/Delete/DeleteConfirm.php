<?php

namespace WPStaging\Staging\Ajax\Delete;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Traits\WithStagingDatabase;

class DeleteConfirm extends AbstractTemplateComponent
{
    use WithStagingDatabase;

    /** @var Sites */
    private $sites;

    /** @var Sanitize */
    private $sanitize;

    public function __construct(Sites $sites, Sanitize $sanitize, TemplateEngine $templateEngine, Database $stagingDb)
    {
        parent::__construct($templateEngine);
        $this->sites     = $sites;
        $this->sanitize  = $sanitize;
        $this->stagingDb = $stagingDb;
    }

    /**
     * @return void
     */
    public function ajaxConfirm()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error('Invalid request.');
        }

        $cloneId = $this->sanitize->sanitizeString(isset($_POST['cloneId']) ? $_POST['cloneId'] : '');
        if (empty($cloneId)) {
            wp_send_json_error('Invalid request. Clone ID missing!');
        }

        $stagingSiteDto = null;
        try {
            $stagingSiteDto = $this->sites->getStagingSiteDtoByCloneId($cloneId);
        } catch (\Throwable $e) {
            wp_send_json_error($e->getMessage());
        }

        $tables    = [];
        $connected = false;
        try {
            $this->initStagingDatabase($stagingSiteDto);
            $tables    = $this->getStagingTablesStatus($stagingSiteDto->getUsedPrefix());
            $connected = true;
        } catch (\Throwable $e) {
            $tables    = [];
            $connected = false;
        }

        $result = $this->templateEngine->render(
            'staging/confirm-delete.php',
            [
                'stagingSite'         => $stagingSiteDto,
                'tables'              => $tables === null ? [] : $tables,
                'isDatabaseConnected' => $connected,
                'stagingSiteSize'     => '', // TODO: not-available but still used in UI, find a way how we can get this efficiently
            ]
        );

        wp_send_json_success([
            'stagingSiteName' => $stagingSiteDto->getSiteName(),
            'html'            => $result
        ]);
    }
}
