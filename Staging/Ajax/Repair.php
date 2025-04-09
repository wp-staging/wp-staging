<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Mails\MailSender;
use WPStaging\Framework\Mails\Report\Report;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Sites;

/**
 * Class Repair
 * This class is responsible for fixing the corrupted staging sites option and report that corrupted option to us
 */
class Repair extends AbstractTemplateComponent
{
    /**
     * @var string
     */
    const REPAIR_EMAIL_SUBJECT = 'WP Staging - Staging Sites Option Corrupted';

    /**
     * The file name which will contain the corrupted staging site option
     * @var string
     */
    const CORRUPTED_STAGING_SITE_OPTION_FILE_NAME = 'corrupted-staging-site-option.log';

    /**
     * @var string
     * It is proceeded with current timestamp when saving in database
     */
    const OPTION_PREFIX_FOR_STAGING_SITES_BACKUP = 'wpstg_staging_sites_backup_';

    /** @var MailSender */
    private $mailSender;

    /** @var Report */
    private $report;

    /** @var Directory */
    private $directory;

    /** @var Sites */
    private $sites;

    public function __construct(TemplateEngine $templateEngine, MailSender $mailSender, Report $report, Directory $directory, Sites $sites)
    {
        parent::__construct($templateEngine);
        $this->mailSender = $mailSender;
        $this->report     = $report;
        $this->directory  = $directory;
        $this->sites      = $sites;
    }

    /**
     * @return void
     */
    public function ajaxFixOption()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $corruptedOption = get_option(Sites::STAGING_SITES_OPTION, null);
        $fixed           = $this->backupOldOption($corruptedOption);
        $this->sites->updateStagingSites([]);

        $mailSent = $this->initiateEmailNotification($corruptedOption, $fixed);

        if ($mailSent) {
            wp_send_json_success([
                'message' => esc_html__('Staging sites option has been fixed.', 'wp-staging'),
            ]);
        }

        wp_send_json_success([
            'message' => esc_html__('Staging sites option has been fixed but issue not reported.', 'wp-staging'),
        ]);
    }

    /**
     * @return void
     */
    public function ajaxReportOption()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $corruptedOption = get_option(Sites::STAGING_SITES_OPTION, null);
        $mailSent        = $this->initiateEmailNotification($corruptedOption);

        if ($mailSent) {
            wp_send_json_success([
                'message' => esc_html__('Staging sites option issue reported.', 'wp-staging'),
            ]);
        }

        wp_send_json_error([
            'message' => esc_html__('Issue not reported.', 'wp-staging'),
        ]);
    }

    /**
     * @param array|string|null $corruptedOption
     * @return bool
     */
    private function backupOldOption($corruptedOption): bool
    {
        return add_option(self::OPTION_PREFIX_FOR_STAGING_SITES_BACKUP . time(), $corruptedOption, '', 'no');
    }

    /**
     * @param string|array|null $corruptedOption
     * @return bool
     */
    private function initiateEmailNotification($corruptedOption, bool $fixed = false): bool
    {
        // No need to translate, this email will be send to our support email address
        $emailBody = 'The user staging sites option has been corrupted. ';
        if ($fixed) {
            $emailBody .= 'The option has been fixed and the corrupted data has been backed up. ';
        }

        $emailBody .= 'Site logs and corrupted option data are attached';

        $corruptedOptionFilePath = $this->directory->getLogDirectory() . self::CORRUPTED_STAGING_SITE_OPTION_FILE_NAME;
        file_put_contents($corruptedOptionFilePath, $corruptedOption);

        $this->mailSender->setAttachments($this->getAttachments($corruptedOptionFilePath));
        $this->mailSender->setRecipient(Report::WPSTG_SUPPORT_EMAIL);
        return $this->mailSender->sendRequestForEmailNotification(self::REPAIR_EMAIL_SUBJECT, $emailBody);
    }

    /**
     * @param string $corruptedOptionFilePath
     * @return array
     */
    private function getAttachments(string $corruptedOptionFilePath): array
    {
        $attachments   = $this->report->getBundledLogs();
        $attachments[] = $corruptedOptionFilePath;

        return $attachments;
    }
}
