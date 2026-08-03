<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\WPStaging;
use WPStaging\Pro\Staging\Multisite\SubsitesDomainPathAndUrlsUpdater;

class UpdateSiteUrlAndHome extends DBCloningService
{
    /**
     * Replace "siteurl" and "home"
     * If it is a complete network clone also update "domain" and "path" in "blogs" and "site" table
     * @return bool
     */
    protected function internalExecute(): bool
    {
        if (!$this->isNetworkClone()) {
            return $this->updateOptionsTable('options', $this->dto->getStagingSiteUrl());
        }

        if (!class_exists('\WPStaging\Pro\Staging\Multisite\SubsitesDomainPathAndUrlsUpdater')) {
            throw new FatalException("SubsitesDomainPathAndUrlsUpdater class not found.");
        }

        /** @var SubsitesDomainPathAndUrlsUpdater $subsitesDomainPathAndUrlsUpdater */
        $subsitesDomainPathAndUrlsUpdater = WPStaging::make(SubsitesDomainPathAndUrlsUpdater::class);
        $subsitesDomainPathAndUrlsUpdater->setup($this->dto, [$this, 'updateOptionsTable'], [$this, 'skipTable']);
        return $subsitesDomainPathAndUrlsUpdater->updateSubsitesDomainPathAndUrls();
    }

    /**
     * @param string $tableName
     * @param string $siteUrl
     * @return bool
     *
     * @throws FatalException
     */
    public function updateOptionsTable(string $tableName, string $siteUrl): bool
    {
        if ($this->skipTable($tableName)) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating siteurl and homeurl in {$this->dto->getPrefix()}{$tableName} to " . $siteUrl);
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'",
                $siteUrl
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update siteurl and homeurl in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        $this->validateRequiredOptions($tableName, $siteUrl);

        return true;
    }

    /**
     * @param string $tableName
     * @param string $siteUrl
     * @return void
     * @throws FatalException
     */
    private function validateRequiredOptions(string $tableName, string $siteUrl)
    {
        $optionsTable = $this->dto->getPrefix() . $tableName;
        $foundOptions = $this->dto->getStagingDb()->get_results(
            "SELECT option_name, option_value FROM `{$optionsTable}` WHERE option_name = 'siteurl' OR option_name = 'home'",
            ARRAY_A
        );

        $foundOptionNames = [];
        $incorrectOptions = [];
        foreach ((array)$foundOptions as $foundOption) {
            $optionName = isset($foundOption['option_name']) ? $foundOption['option_name'] : '';
            if ($optionName === '') {
                continue;
            }

            $foundOptionNames[] = $optionName;
            if (!isset($foundOption['option_value']) || $foundOption['option_value'] !== $siteUrl) {
                $incorrectOptions[] = $optionName;
            }
        }

        $missingOptions = array_diff(['siteurl', 'home'], $foundOptionNames);

        if (!empty($missingOptions)) {
            throw new FatalException(
                sprintf(
                    'Staging options table %s is missing required rows after database copying: %s.',
                    $optionsTable,
                    implode(', ', $missingOptions)
                )
            );
        }

        if (!empty($incorrectOptions)) {
            throw new FatalException(
                sprintf(
                    'Staging options table %s contains incorrect values after updating: %s.',
                    $optionsTable,
                    implode(', ', array_unique($incorrectOptions))
                )
            );
        }
    }
}
