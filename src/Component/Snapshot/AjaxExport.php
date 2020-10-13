<?php
/** @noinspection PhpUndefinedClassInspection */

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use Exception;
use WPStaging\Command\Database\Export\ExportHandler;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\FileSystem;

class AjaxExport extends AbstractTemplateComponent
{

    /** @var ExportHandler */
    private $handler;

    public function __construct(ExportHandler $handler, Hooks $hooks, TemplateEngine $templateEngine)
    {
        parent::__construct($hooks, $templateEngine);
        $this->handler = $handler;
    }

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--export', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : null;

        $this->compatibilityCheck();
        (new FileSystem)->deleteFiles($this->handler->generatePath());

        try {
            wp_send_json_success($this->pathToUrl($this->handler->handle($id)));
        } catch (Exception $e) {
            wp_send_json([
                'error' => true,
                'message' => sprintf(__('Failed to export the snapshot tables %s', 'wp-staging'), $id),
            ]);
        }
    }

    private function compatibilityCheck()
    {
        // TODO RPoC
        if (class_exists('PDO')) {
            return;
        }
        wp_send_json([
            'error' => true,
            'message' => __('PHP PDO extension not found. ', 'wp-staging'),
        ]);
    }

    /**
     * @param string $dir
     *
     * @return string
     */
    private function pathToUrl($dir)
    {
        $relativePath = str_replace(ABSPATH, null, $dir);
        return site_url() . '/' . $relativePath;
    }
}
