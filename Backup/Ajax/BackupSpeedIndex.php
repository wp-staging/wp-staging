<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Math;

class BackupSpeedIndex
{
    /** @var string */
    const OPTION_BACKUP_SPEED_FIRST_INDEX = 'wpstg_first_backup_speed_index';

    /** @var string */
    const OPTION_BACKUP_SPEED_INDEX = 'wpstg_backup_speed_index';

    /** @var string */
    const OPTION_SHOW_BACKUP_SPEED_MODAL = 'wpstg_backup_speed_modal_shown';

    /** @var bool */
    protected $firstBackupSpeedIndex = false;

    /** @var string */
    protected $finalBackupSpeedIndex;

    /** @var float */
    protected $currentBackupSpeedIndex;

    /** @var bool false */
    protected $isBackupSpeedModalDisplayed = false;

    /** @var bool */
    protected $isBackupSlowerThanUsual;

    /** @var string */
    protected $currentBackupSize;

    /**
     * @var int
     */
    protected $currentBackupTime = 1;

    /** @var Sanitize */
    private $sanitize;

    /**
     * @var Auth
     */
    private $auth;

    /** @var Math */
    protected $utilsMath;

    /**
     * @param Auth $auth
     * @param Sanitize $sanitize
     * @param Math $utilsMath
     */
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

    /**
     * Ajax handler to possibly show a modal based on backup speed
     *
     * @return void
     */
    public function ajaxMaybeShowModal()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        if (WPStaging::isPro()) {
            return;
        }

        $this->init();
        $this->sendJsonResponse();
    }

    /**
     * Checks the backup speed, triggering actions based on the results.
     *
     * @return void
     */
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

    /**
     * Send a JSON response based on backup speed analysis.
     *
     * @return void
     */
    public function sendJsonResponse()
    {
        wp_send_json(['isBackupSlowerThanUsual' => $this->isBackupSlowerThanUsual, 'isBackupSpeedModalDisplayed' => $this->isBackupSpeedModalDisplayed]);
    }

    /**
     * Create a temporary backup speed index by adding it as an option.
     * @return void
     */
    private function saveTempBackupSpeedIndex()
    {
        add_option(self::OPTION_BACKUP_SPEED_FIRST_INDEX, $this->currentBackupSpeedIndex);
    }


    /**
     * Create the backup speed index by calculating an average and adding it as an option.
     * @return void
     */
    private function calculateFinalBackupSpeedIndex()
    {
        $averageSpeedIndex = ($this->currentBackupSpeedIndex + $this->firstBackupSpeedIndex) / 2;
        add_option(self::OPTION_BACKUP_SPEED_INDEX, $averageSpeedIndex);
        $this->deleteTempBackupSpeedIndex();
    }

    /**
     * Calculate the current backup speed based on file size and backup time.
     * The higher the Index number, the faster the backup creation.
     * @return void
     */
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

    /**
     * Delete the temporary backup speed index option.
     * @return void
     */
    private function deleteTempBackupSpeedIndex()
    {
        delete_option(self::OPTION_BACKUP_SPEED_FIRST_INDEX);
    }

    /**
     * @param string $currentBackupSize
     * @return void
     */
    public function setCurrentBackupSize(string $currentBackupSize)
    {
        $this->currentBackupSize = $currentBackupSize;
    }

    /**
     * @param int $currentBackupTime
     * @return void
     */
    public function setCurrentBackupTime(int $currentBackupTime)
    {
        $this->currentBackupTime = $currentBackupTime;
    }
}
