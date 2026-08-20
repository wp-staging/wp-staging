<?php

namespace WPStaging\Framework\TemplateEngine;

use DateTime;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\DateTimeAdapter;
use WPStaging\Framework\Assets\Assets;

class TemplateEngine implements TemplateEngineInterface
{




    const HOOK_RENDER_PRO_TEMPLATES = 'wpstg.template.render_pro_templates';

 
    const ACTION_AFTER_EXISTING_CLONES = 'wpstg.views.single_overview.after_existing_clones_actions';

 
    const ACTION_MULTI_SITE_CLONE_OPTION = 'wpstg.views.ajax_clone.multi_site_clone_option';

 
    const ACTION_BACKUP_TAB = 'wpstg.views.backup.tab_backup';

 
    protected $views;

 
    private $assets;

    public function __construct()
    {
        $this->assets = WPStaging::make(Assets::class);
    }







    public function render(string $path, array $params = []): string
    {
        if (!isset($this->views)) {
            $this->views = WPSTG_VIEWS_DIR;
        }

        $fullPath = WPSTG_VIEWS_DIR . $path;
        if (!file_exists($fullPath)) {
            throw new TemplateEngineException('Template not found: ' . $fullPath);
        }

        extract($params, EXTR_SKIP);
        ob_start();

 
        require $fullPath;
        $result = ob_get_clean();

        return (string)$result;
    }




    public function getAssets()
    {
        return $this->assets;
    }





    protected function getDateTimeFormat(): string
    {
        return (new DateTimeAdapter())->getDateTimeFormat();
    }






    protected function transformToWpFormat($dateTime = null): string
    {
        if (!$dateTime) {
            return '';
        }

        return (new DateTimeAdapter())->transformToWpFormat($dateTime);
    }
}
