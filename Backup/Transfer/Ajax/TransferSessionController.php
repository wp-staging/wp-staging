<?php

namespace WPStaging\Backup\Transfer\Ajax;

use Throwable;
use WPStaging\Backup\Transfer\TransferSession;
use WPStaging\Backup\Transfer\TransferSessionException;
use WPStaging\Backup\Transfer\TransferSessionService;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;

use function WPStaging\functions\debug_log;





class TransferSessionController
{
 
    private $transferSessionService;

 
    private $capabilities;

 
    private $nonce;

    public function __construct(TransferSessionService $transferSessionService, Capabilities $capabilities, Nonce $nonce)
    {
        $this->transferSessionService = $transferSessionService;
        $this->capabilities           = $capabilities;
        $this->nonce                  = $nonce;
    }

 
    public function ajaxCreate()
    {
        $backupId = $this->getRequestedBackupId();

        $this->authorizeAndSendSession(function () use ($backupId) {
            if ($backupId === '') {
                throw TransferSessionException::invalidRequest();
            }

            $requestedTtl = isset($_POST['ttl']) ? Sanitize::sanitizeInt($_POST['ttl']) : 0;

            return $this->transferSessionService->createForBackupId($backupId, $requestedTtl);
        }, TransferSessionException::artifactCreationFailed(), 'create', $backupId);
    }

 
    public function ajaxRevoke()
    {
        $this->authorizeAndSendSession(function () {
            $sessionId = isset($_POST['sessionId']) ? Sanitize::sanitizeInt($_POST['sessionId']) : 0;
            if ($sessionId <= 0) {
                throw TransferSessionException::invalidRequest();
            }

            return $this->transferSessionService->revoke($sessionId);
        }, TransferSessionException::cannotDeleteArtifact(), 'revoke');
    }






    public function ajaxDownloadStarted()
    {
        $this->authorizeAndSendSession(function () {
            $sessionId = isset($_POST['sessionId']) ? Sanitize::sanitizeInt($_POST['sessionId']) : 0;

            return $this->transferSessionService->markDownloadStarted($sessionId);
        }, TransferSessionException::sessionNotFound(), 'record a started download of');
    }

 
    public function ajaxStatus()
    {
        if (!$this->isRequestAuthorized()) {
            $this->sendUnauthorized();
            return;
        }

        $backupId = $this->getRequestedBackupId();
        $session  = $backupId === '' ? null : $this->transferSessionService->getActiveSession($backupId);

        if ($session === null) {
            wp_send_json(['error' => false, 'hasSession' => false]);
            return;
        }

        wp_send_json(array_merge(['error' => false, 'hasSession' => true], $session->toResponse()));
    }








    private function authorizeAndSendSession(callable $getSession, TransferSessionException $fallbackError, string $actionForLog, string $backupId = '')
    {
        if (!$this->isRequestAuthorized()) {
            $this->sendUnauthorized();
            return;
        }

        try {
            $session = $getSession();
        } catch (TransferSessionException $e) {
            $this->sendError($e->getMessage(), $backupId);
            return;
        } catch (Throwable $e) {
            debug_log(sprintf('WP STAGING: Could not %s a transfer session. %s', $actionForLog, $e->getMessage()));
            $this->sendError($fallbackError->getMessage(), $backupId);
            return;
        }

        $this->sendSession($session);
    }

    private function isRequestAuthorized(): bool
    {
        return current_user_can($this->capabilities->manageWPSTG()) && $this->nonce->requestHasValidNonce(Nonce::WPSTG_NONCE);
    }

 
    private function sendSession(TransferSession $session)
    {
        wp_send_json(array_merge(['error' => false], $session->toResponse()));
    }






    private function sendError(string $message, string $backupId = '')
    {
        $response      = ['error' => true, 'message' => $message];
        $activeSession = $backupId === '' ? null : $this->transferSessionService->getActiveSession($backupId);

        if ($activeSession !== null) {
            $response['activeSession'] = $activeSession->toResponse();
        }

        wp_send_json($response);
    }

 
    private function sendUnauthorized()
    {
        $this->sendError(__('You are not allowed to access this page.', 'wp-staging'));
    }

 
    private function getRequestedBackupId(): string
    {
        $backupId = isset($_POST['backupId']) ? Sanitize::sanitizeString($_POST['backupId']) : '';

        return preg_match('/^[a-f0-9]{32}$/', $backupId) === 1 ? $backupId : '';
    }
}
