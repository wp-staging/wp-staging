<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Framework\Component\AbstractTemplateComponent;

/**
 * Ajax loading of log window and progress bar
 */
class AjaxRestoreProgress extends AbstractTemplateComponent
{

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--restore--progress', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $result = $this->templateEngine->render('Component/Backend/Snapshot/restore-progress.php');
        wp_send_json($result);
    }
}
