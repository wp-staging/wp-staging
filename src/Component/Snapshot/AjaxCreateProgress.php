<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Manager\Database\TableManager;
use WPStaging\Repository\SnapshotRepository;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class AjaxCreateProgress extends AbstractTemplateComponent
{

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--create--progress', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $result = $this->templateEngine->render('Component/Backend/Snapshot/create-progress.php');
        wp_send_json($result);
    }
}
