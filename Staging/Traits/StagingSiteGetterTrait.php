<?php

namespace WPStaging\Staging\Traits;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Staging\Sites;

/**
 * Trait StagingSiteGetterTrait
 * This trait is used to get staging site by clone name or staging site id. It is used in multiple classes to avoid code duplication.
 */
trait StagingSiteGetterTrait
{
    /**
     * Return clone id after validating the staging site by id or name. If the staging site does not exist, an exception will be thrown.
     *
     * @param array $options
     * @return string
     */
    protected function validateStagingSiteByIdOrName(array $options): string
    {
        // Clone ID or Clone Name is required
        if (empty($options['id']) && empty($options['name'])) {
            throw new Exception('The id or name parameter is required. Use: wp wpstg staging-site-reset id=<staging-site-id> or wp wpstg staging-site-reset name=<staging-site-name>');
        }

        if (!empty($options['id'])) {
            $cloneId = sanitize_text_field($options['id']);
            /** @var Sites $stagingSites */
            $stagingSites = WPStaging::make(Sites::class);
            $stagingSite = $stagingSites->getStagingSiteDtoByCloneId($cloneId);

            if ($stagingSite === null) {
                throw new Exception("Staging site with ID '{$cloneId}' does not exist.");
            }

            return $cloneId;
        }

        if (!empty($options['name'])) {
            $cloneName = sanitize_text_field($options['name']);
            /** @var Sites $stagingSites */
            $stagingSites = WPStaging::make(Sites::class);
            $stagingSite = $stagingSites->getStagingSiteDtoByCloneName($cloneName);

            if ($stagingSite === null) {
                throw new Exception("Staging site with name '{$cloneName}' does not exist.");
            }

            return $stagingSite->getCloneId();
        }

        throw new Exception('Unable to determine staging site ID from provided options.');
    }
}
