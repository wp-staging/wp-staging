<?php

namespace WPStaging\Backup\Security\Ajax;

use Throwable;
use WPStaging\Backup\Security\BackupDirectoryNoticeService;
use WPStaging\Backup\Security\BackupDirectoryProtectionService;
use WPStaging\Backup\Security\BackupDirectorySecurityCheck;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

use function WPStaging\functions\debug_log;





class BackupDirectorySecurityController
{
 
    private $securityCheck;

 
    private $noticeService;

 
    private $protectionService;

 
    private $templateEngine;

 
    private $capabilities;

 
    private $nonce;

    public function __construct(
        BackupDirectorySecurityCheck $securityCheck,
        BackupDirectoryNoticeService $noticeService,
        BackupDirectoryProtectionService $protectionService,
        TemplateEngine $templateEngine,
        Capabilities $capabilities,
        Nonce $nonce
    ) {
        $this->securityCheck     = $securityCheck;
        $this->noticeService     = $noticeService;
        $this->protectionService = $protectionService;
        $this->templateEngine    = $templateEngine;
        $this->capabilities      = $capabilities;
        $this->nonce             = $nonce;
    }

 
    public function ajaxRunCheck()
    {
        if (!$this->isRequestAuthorized()) {
            $this->sendUnauthorized();
        }

        try {
            $this->protectionService->protect();
            $this->noticeService->reset();
            $result = $this->securityCheck->run();
        } catch (Throwable $e) {
            debug_log('WP STAGING: The backup folder security check could not be completed. ' . $e->getMessage());
            wp_send_json(['error' => true, 'message' => __('The backup folder security check could not be completed.', 'wp-staging')]);
            return;
        }

        wp_send_json([
            'error'  => false,
            'status' => $result['status'],
            'html'   => $this->renderNotice(),
        ]);
    }

 
    public function ajaxDismissNotice()
    {
        if (!$this->isRequestAuthorized()) {
            $this->sendUnauthorized();
        }

        $this->noticeService->dismiss();

        wp_send_json(['error' => false]);
    }

 
    private function renderNotice(): string
    {
        return $this->templateEngine->render('backup/partials/backup-directory-hardening-notice.php');
    }

    private function isRequestAuthorized(): bool
    {
        return current_user_can($this->capabilities->manageWPSTG()) && $this->nonce->requestHasValidNonce(Nonce::WPSTG_NONCE);
    }

 
    private function sendUnauthorized()
    {
        wp_send_json([
            'error'   => true,
            'message' => __('You are not allowed to access this page.', 'wp-staging'),
        ]);
    }
}
