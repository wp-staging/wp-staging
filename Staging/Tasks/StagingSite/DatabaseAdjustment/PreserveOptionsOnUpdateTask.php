<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\OptionPreservationHandler;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Preserve staging-site-only options across a staging site update. The update flow renames the
 * existing wpstg0_options to wpstgbak_options and imports the production options into a fresh
 * wpstg0_options — so any options that only existed on the staging site (cloud storage credentials,
 * backup schedules, license key, etc.) would be lost without this task reinstating them from the
 * backup copy.
 *
 * Legacy did the same thing via PreserveDataFirstStep/PreserveDataSecondStep using a
 * wpstg_tmp_data option round-trip; here we read directly from the already-renamed backup table.
 */
class PreserveOptionsOnUpdateTask extends DatabaseAdjustmentTask
{
    /** @var string */
    const FILTER_PRESERVED_OPTIONS = 'wpstg_preserved_options';

    /** @var OptionPreservationHandler */
    protected $optionPreservationHandler;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Database $database, OptionPreservationHandler $optionPreservationHandler)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls, $database);
        $this->optionPreservationHandler = $optionPreservationHandler;
    }

    public static function getTaskName()
    {
        return 'staging_preserve_options_on_update';
    }

    public static function getTaskTitle()
    {
        return 'Preserving staging-site-only options';
    }

    public function execute(): TaskResponseDto
    {
        $this->setup();

        if ($this->isOptionsTableExcluded()) {
            return $this->generateResponse();
        }

        $destOptionsTable = $this->getOptionsTableName();
        $bakOptionsTable  = DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP . 'options';

        if (!$this->isTableExists($bakOptionsTable)) {
            // Nothing to preserve — previous staging options table never existed or already cleaned up.
            return $this->generateResponse();
        }

        $optionsToPreserve = Hooks::applyFilters(self::FILTER_PRESERVED_OPTIONS, $this->getPreservedOptions());
        if (empty($optionsToPreserve)) {
            return $this->generateResponse();
        }

        $optionsToPreserve = $this->escapeLiteralOptionsForLike($optionsToPreserve);

        $this->optionPreservationHandler->setProductionDb($this->wpdb);
        $likeStatement  = $this->optionPreservationHandler->getLikeStatement($optionsToPreserve);
        $preservedRows  = $this->optionPreservationHandler->getOptionsDataToPreserve($likeStatement, $bakOptionsTable);

        if (empty($preservedRows)) {
            return $this->generateResponse();
        }

        // Delete matching option names from the fresh staging options table to avoid unique-key
        // collisions on option_name when we insert the preserved rows.
        $this->optionPreservationHandler->deleteFromTable($likeStatement, $destOptionsTable);

        $sql = $this->optionPreservationHandler->createInsertQuery($preservedRows, $destOptionsTable);
        $this->executeBulk($sql);

        $this->logger->info(sprintf('Preserved %d staging-only option(s) across update.', count($preservedRows)));

        return $this->generateResponse();
    }

    /**
     * Option names whose staging values must survive a staging-site update. Subclasses may extend
     * this list with version-specific entries (e.g. Pro adds cloud-storage credentials).
     *
     * @return string[]
     */
    protected function getPreservedOptions(): array
    {
        return [
            'wpstg_optimizer_excluded',
            'wpstg_version_upgraded_from',
            'wpstg_version',
            'wpstg_installDate',
            'wpstg_free_install_date',
            'upload_path',
            'wpstg_free_upgrade_date',
            'wpstg_googledrive',
            'wpstg_google-drive',
            'wpstg_amazons3',
            'wpstg_amazon-s3',
            'wpstg_sftp',
            'wpstg_digitalocean',
            'wpstg_digitalocean-spaces',
            'wpstg_wasabi',
            'wpstg_wasabi-s3',
            'wpstg_generic-s3',
            'wpstg_dropbox',
            'wpstg_one-drive',
            'wpstg_pcloud',
        ];
    }

    /**
     * Escape option names so SQL LIKE treats "_" and "%" as literal characters.
     *
     * @param string[] $optionsToPreserve
     * @return string[]
     */
    protected function escapeLiteralOptionsForLike(array $optionsToPreserve): array
    {
        return array_map(function ($optionName) {
            return $this->wpdb->esc_like((string)$optionName);
        }, $optionsToPreserve);
    }

    /**
     * @param string $sqlbatch
     * @return void
     */
    protected function executeBulk(string $sqlbatch)
    {
        $queries = array_filter(explode(";\n", $sqlbatch));

        foreach ($queries as $query) {
            if ($this->wpdb->query($query) === false) {
                $this->logger->warning("Preserve Options: Could not execute query: {$query}");
            }
        }
    }
}
