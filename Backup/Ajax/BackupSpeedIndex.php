<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Math;

class BackupSpeedIndex
{
 
    const OPTION_BACKUP_SPEED_FIRST_INDEX = 'wpstg_first_backup_speed_index';

 
    const OPTION_BACKUP_SPEED_INDEX = 'wpstg_backup_speed_index';

 
    const OPTION_SHOW_BACKUP_SPEED_MODAL = 'wpstg_backup_speed_modal_shown';

 
    const WPSTG_DISABLE_BACKUP_SPEED_MODAL = true;

 
    protected $firstBackupSpeedIndex = false;

 
    protected $finalBackupSpeedIndex;

 
    protected $currentBackupSpeedIndex;

 
    protected $isBackupSpeedModalDisplayed = false;

 
    protected $isBackupSlowerThanUsual;

 
    protected $currentBackupSize;

 
    protected $currentBackupTime = 1;

 
    protected $utilsMath;

 
    private $sanitize;

 
    private $auth;






    public function __construct(Auth $auth, Sanitize $sanitize, Math $utilsMath)
    {
        $this->auth                        = $auth;
        $this->finalBackupSpeedIndex       = get_option(self::OPTION_BACKUP_SPEED_INDEX);
        $this->firstBackupSpeedIndex       = get_option(self::OPTION_BACKUP_SPEED_FIRST_INDEX);
        $this->isBackupSpeedModalDisplayed = get_option(self::OPTION_SHOW_BACKUP_SPEED_MODAL);
        $this->sanitize                    = $sanitize;
        $this->utilsMath                   = $utilsMath;
        $this->isBackupSlowerThanUsual     = false;
    }






    public function ajaxMaybeShowModal()
    {
 
        if (self::WPSTG_DISABLE_BACKUP_SPEED_MODAL) {
            return;
        }

        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        if (WPStaging::isPro()) {
            return;
        }

        $this->init();
        $this->sendJsonResponse();
    }






    public function init()
    {
        if ($this->isBackupSpeedModalDisplayed) {
            return;
        }

        $this->calculateCurrentBackupSpeedIndex();

        if (!$this->finalBackupSpeedIndex && !$this->firstBackupSpeedIndex) {
            $this->saveTempBackupSpeedIndex();
            return;
        }

        if (!$this->finalBackupSpeedIndex) {
            $this->calculateFinalBackupSpeedIndex();
            return;
        }

        if ($this->finalBackupSpeedIndex > $this->currentBackupSpeedIndex) {
            $this->isBackupSlowerThanUsual = true;
            update_option(self::OPTION_SHOW_BACKUP_SPEED_MODAL, 'true');
        }
    }






    public function sendJsonResponse()
    {
        wp_send_json(['isBackupSlowerThanUsual' => $this->isBackupSlowerThanUsual, 'isBackupSpeedModalDisplayed' => $this->isBackupSpeedModalDisplayed]);
    }





    private function saveTempBackupSpeedIndex()
    {
        add_option(self::OPTION_BACKUP_SPEED_FIRST_INDEX, $this->currentBackupSpeedIndex);
    }






    private function calculateFinalBackupSpeedIndex()
    {
        $averageSpeedIndex = ($this->currentBackupSpeedIndex + $this->firstBackupSpeedIndex) / 2;
        add_option(self::OPTION_BACKUP_SPEED_INDEX, $averageSpeedIndex);
        $this->deleteTempBackupSpeedIndex();
    }






    private function calculateCurrentBackupSpeedIndex()
    {
        if (isset($_POST['size'])) {
            $this->setCurrentBackupSize($this->sanitize->sanitizeString($_POST['size']));
        }

        if (!empty($_POST['time'])) {
            $this->setCurrentBackupTime($this->sanitize->sanitizeInt($_POST['time']));
        }

        $fileSize                      = $this->utilsMath->convertUnitToMB($this->currentBackupSize);
        $this->currentBackupSpeedIndex = ($fileSize / $this->currentBackupTime);
    }





    private function deleteTempBackupSpeedIndex()
    {
        delete_option(self::OPTION_BACKUP_SPEED_FIRST_INDEX);
    }





    public function setCurrentBackupSize(string $currentBackupSize)
    {
        $this->currentBackupSize = $currentBackupSize;
    }





    public function setCurrentBackupTime(int $currentBackupTime)
    {
        $this->currentBackupTime = $currentBackupTime;
    }
}
