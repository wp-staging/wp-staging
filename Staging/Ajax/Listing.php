<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Sites;

class Listing extends AbstractTemplateComponent
{
    /** @var SiteInfo */
    private $siteInfo;

    /** @var Assets */
    private $assets;

    public function __construct(TemplateEngine $templateEngine, SiteInfo $siteInfo, Assets $assets)
    {
        parent::__construct($templateEngine);
        $this->siteInfo = $siteInfo;
        $this->assets   = $assets;
    }

    /**
     * @return void
     */
    public function ajaxListing()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        // Early bailing: for multiple reasons
        if ($this->siteInfo->isHostedOnWordPressCom()) {
            wp_send_json_error(esc_html__('Staging site feature not supported on sites hosted on Wordpress.com!', 'wp-staging'));
        } elseif (!WPStaging::isPro() && is_multisite()) {
            wp_send_json_error(esc_html__('Free version not supported on multisite!', 'wp-staging'));
        } elseif (!$this->siteInfo->isCloneable()) {
            wp_send_json_error(esc_html__('This staging site is not cloneable!', 'wp-staging'));
        }

        $sites = WPStaging::make(Sites::class);
        $error = false;

        try {
            $stagingSitesArray = $sites->getSortedStagingSites();
        } catch (WPStagingException $e) {
            $stagingSitesArray = [];
            $error             = true;
        }

        $stagingSites = [];
        foreach ($stagingSitesArray as $cloneID => $data) {
            $stagingSite = new StagingSiteDto();
            $stagingSite->hydrate($data);
            $stagingSite->setCloneId($cloneID);
            $stagingSites[] = $stagingSite;
        }

        $result = $this->templateEngine->render(
            'staging/listing.php',
            [
                'stagingSites' => $stagingSites,
                'license'      => get_option('wpstg_license_status'),
                'iconPath'     => $this->assets->getAssetsUrl('svg/cloud.svg'),
                'error'       => $error,
                // TODO: check if required?
                'db'           => WPStaging::make('wpdb'),
            ]
        );

        wp_send_json($result);
    }
}
