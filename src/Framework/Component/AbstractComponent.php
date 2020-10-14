<?php
// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return type hints

namespace WPStaging\Framework\Component;

use WPStaging\Framework\Adapter\Dto\HookDto;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Security\AccessToken;

abstract class AbstractComponent implements ComponentInterface
{
    /** @var Hooks */
    private $hooks;

    /** @var AccessToken */
    private $accessToken;

    public function __construct(Hooks $hooks)
    {
        $this->hooks = $hooks;

        // Todo: Inject using DI
        $this->accessToken = new AccessToken;

        $this->registerHooks();
    }

    /**
     * @param string $action
     * @param string $method
     * @param int $acceptedArgs
     * @param int $priority
     * @noinspection PhpUnused
     */
    public function addAction($action, $method, $acceptedArgs = 0, $priority = 10)
    {
        $dto = $this->generateDto($action, $method, $acceptedArgs, $priority);
        $this->hooks->addAction($dto);
    }

    /**
     * @param string $action
     * @param string $method
     * @param int $acceptedArgs
     * @param int $priority
     * @noinspection PhpUnused
     */
    public function addFilter($action, $method, $acceptedArgs = 0, $priority = 10)
    {
        $dto = $this->generateDto($action, $method, $acceptedArgs, $priority);
        $this->hooks->addFilter($dto);
    }

    /**
     * @param string $action
     * @param string $method
     * @param int $acceptedArgs
     * @param int $priority
     *
     * @return HookDto
     */
    private function generateDto($action, $method, $acceptedArgs = 0, $priority = 10)
    {
        $dto = new HookDto;
        $dto->setHook($action);
        $dto->setComponent($this);
        $dto->setCallback($method);
        $dto->setAcceptedArgs($acceptedArgs);
        $dto->setPriority($priority);

        return $dto;
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
