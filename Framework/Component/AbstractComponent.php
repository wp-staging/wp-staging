<?php
// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return type hints

namespace WPStaging\Framework\Component;

use WPStaging\Framework\Security\AccessToken;

abstract class AbstractComponent implements ComponentInterface
{

    /** @var AccessToken */
    private $accessToken;

    public function __construct()
    {
        // Todo: Inject using DI
        $this->accessToken = new AccessToken;

        $this->registerHooks();
    }

    /**
     * @return bool Whether the current request should render this template.
     */
    protected function canRenderAjax()
    {
        $isAjax   = wp_doing_ajax();
        $hasToken = $this->accessToken->requestHasValidToken();

        return $isAjax && $hasToken;
    }
}
