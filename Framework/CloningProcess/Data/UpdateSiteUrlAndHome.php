<?php


namespace WPStaging\Framework\CloningProcess\Data;


use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;

class UpdateSiteUrlAndHome extends DBCloningService
{
    /**
     * Replace "siteurl" and "home"
     * @return bool
     */
    protected function internalExecute()
    {
        if ($this->skipOptionsTable()) {
            $this->log('Skipped');
            return true;
        }

        $this->log("Updating siteurl and homeurl in {$this->dto->getPrefix()}options to " . $this->dto->getStagingSiteUrl());
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}options SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'",
                $this->dto->getStagingSiteUrl()
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update siteurl and homeurl in {$this->dto->getPrefix()}options. {$this->dto->getStagingDb()->last_error}");
        } else {
            //$this->log("Done");
        }
        return true;
    }
}
