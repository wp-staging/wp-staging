<?php

namespace WPStaging\Staging\Ajax\Delete;

use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Staging\Sites;
use WPStaging\Staging\PrefixOwnership;
use WPStaging\Staging\Traits\WithStagingDatabase;




class DeleteConfirm extends AbstractTemplateComponent
{
    use WithStagingDatabase;

 
    private $sites;

 
    private $sanitize;

    public function __construct(Sites $sites, Sanitize $sanitize, TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->sites     = $sites;
        $this->sanitize  = $sanitize;
    }




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
            wp_send_json_error(['message' => esc_html($e->getMessage())], 409);
        }

        $ownsTables = true;
        $tables     = [];
        try {
            $this->initStagingDatabase($stagingSiteDto);
            $connected = true;
        } catch (\Throwable $e) {
            $connected = false;
        }

        if ($connected) {
            try {
                $ownsTables = (new PrefixOwnership($this->sites))->canDeleteTables($stagingSiteDto->getUsedPrefix(), $cloneId, $this->stagingDb->getWpdb());
                $tables = $ownsTables ? $this->getStagingTablesStatus($stagingSiteDto->getUsedPrefix()) : [];
            } catch (\RuntimeException $e) {
                wp_send_json_error(['message' => esc_html($e->getMessage())], 409);
            } catch (\Throwable $e) {
                $tables    = [];
                $connected = false;
            }
        }

        $result = $this->templateEngine->render(
            'staging/confirm-delete.php',
            [
                'ownsDatabaseTables'  => $ownsTables,
                'stagingSite'         => $stagingSiteDto,
                'tables'              => $tables === null ? [] : $tables,
                'isDatabaseConnected' => $connected,
                'stagingSiteSize'     => '', 
            ]
        );

        wp_send_json_success([
            'stagingSiteName' => $stagingSiteDto->getSiteName(),
            'html'            => $result,
        ]);
    }
}
