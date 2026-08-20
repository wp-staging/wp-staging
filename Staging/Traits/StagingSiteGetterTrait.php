<?php

namespace WPStaging\Staging\Traits;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Staging\Sites;





trait StagingSiteGetterTrait
{






    protected function validateStagingSiteByIdOrName(array $options): string
    {
 
        if (empty($options['id']) && empty($options['name'])) {
            throw new Exception('The id or name parameter is required. Use: wp wpstg staging-site-reset id=<staging-site-id> or wp wpstg staging-site-reset name=<staging-site-name>');
        }

        if (!empty($options['id'])) {
            $cloneId = sanitize_text_field($options['id']);
 
            $stagingSites = WPStaging::make(Sites::class);

            try {
                $stagingSites->getStagingSiteDtoByCloneId($cloneId);
            } catch (Exception $e) {
                throw new Exception("Staging site with ID '{$cloneId}' does not exist.");
            }

            return $cloneId;
        }

        if (!empty($options['name'])) {
            $cloneName = sanitize_text_field($options['name']);
 
            $stagingSites = WPStaging::make(Sites::class);

            try {
                $stagingSite = $stagingSites->getStagingSiteDtoByCloneName($cloneName);
            } catch (Exception $e) {
                throw new Exception("Staging site with name '{$cloneName}' does not exist.");
            }

            return $stagingSite->getCloneId();
        }

        throw new Exception('Unable to determine staging site ID from provided options.');
    }
}
